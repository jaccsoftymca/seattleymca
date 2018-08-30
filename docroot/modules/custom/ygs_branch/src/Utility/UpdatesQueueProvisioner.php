<?php

namespace Drupal\ygs_branch\Utility;

use Drupal\node\Entity;

/**
 * Helper class for updating Branch queue.
 *
 * @ingroup utility
 */
class UpdatesQueueProvisioner {

  /**
   * The node object.
   *
   * @var Entity
   */
  protected $entity;

  /**
   * Creates a new UpdatesQueueProvisioner.
   *
   * @param Entity $entity
   *   The Entity.
   */
  public function __construct(Entity $entity) {
    $this->Entity = $entity;
  }

  /**
   * Indicates if entity is a node of blog bundle.
   *
   * @param object $entity
   *   The entity object of node type.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates if entity is a node of blog bundle.
   */
  public static function isBlogPost($entity) {
    if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'blog') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Indicates if entity is a node of announcement bundle.
   *
   * @param object $entity
   *   The entity object of node type.
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates if entity is a node of announcement bundle.
   */
  public static function isAnnouncement($entity) {
    if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'announcement') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns all paragraph ids referenced to each branch.
   *
   * @param object $entity
   *   The entity object of node type.
   *
   * @return mixed
   *   Array with all paragraph ids referenced to the branch or boolean.
   */
  public static function getParagraphs($entity) {
    // Proceed only if "Add to Branch queue" is checked.
    $paragraphs = [];
    if ($entity->field_add_to_branch_queue->value) {
      if ($entity->hasField('field_locations')) {
        $location_field = 'field_locations';
      }
      elseif ($entity->hasField('field_blog_location')) {
        $location_field = 'field_blog_location';
      }
      if (isset($location_field) && $branch_ids = $entity->{$location_field}->getValue()) {
        foreach ($branch_ids as $id) {
          // Load referenced Branch.
          $branch = \Drupal::entityTypeManager()->getStorage('node')->load($id['target_id']);
          $paragraphs[$id['target_id']] = $branch->field_content->getValue();
        }
      }
    }
    if ($entity->field_homepage_queue->value) {
      $home_page_alias = \Drupal::config('system.site')->get('page.front');
      $path = \Drupal::service('path.alias_manager')->getPathByAlias($home_page_alias);
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $front_node = $entity->load($matches[1]);
        if ($front_node->bundle() == 'landing_page') {
          $paragraphs[$matches[1]] = $front_node->field_content->getValue();
        }
      }
    }
    return $paragraphs;
  }

  /**
   * Update all paragraphs referenced to entity.
   *
   * @param object $entity
   *   The entity object of node type.
   */
  public static function updateParagraphs($entity) {
    $branch_content_paragraph_ids = self::getParagraphs($entity);
    if (empty($branch_content_paragraph_ids)) {
      return;
    }
    foreach ($branch_content_paragraph_ids as $branch_id => $ids) {
      if (empty($ids)) {
        continue;
      }
      // Proceed with each branch paragraphs.
      foreach ($ids as $id) {
        if (isset($id['target_id'])) {
          // Try to find "Branch updates queue" paragraph type
          // and get collection ids.
          if ($queue_items_collection_ids = self::getUpdatesQueueParagraphInstance($id)) {
            $unlocked_items_keys = [];
            $queue_items = self::getQueueItems($queue_items_collection_ids);
            if (in_array($entity->id(), $queue_items)) {
              // Skip queue if item already exist in queue_items_collection_ids.
              continue;
            }
            foreach ($queue_items_collection_ids as $key => $qid) {
              $unlocked_key = self::getUnlockedItemKey($key, $qid);
              if (is_numeric($unlocked_key)) {
                $unlocked_items_keys[] = $unlocked_key;
              }
            }
            // Check if we have unlocked items, and replace first
            // by new blog post.
            if (!empty($unlocked_items_keys)) {
              self::updateQueue($entity, $unlocked_items_keys, $queue_items_collection_ids);
            }
          }
        }
      }
    }
  }

  /**
   * Delete entity from all referenced paragraphs.
   *
   * @param object $entity
   *   The entity object of node type.
   */
  public static function deleteItemFromParagraphs($entity) {
    $branch_content_paragraph_ids = self::getParagraphs($entity);
    if (empty($branch_content_paragraph_ids)) {
      return;
    }
    foreach ($branch_content_paragraph_ids as $branch_id => $ids) {
      if (empty($ids)) {
        continue;
      }

      // Proceed with each branch paragraphs.
      foreach ($ids as $id) {
        if (isset($id['target_id'])) {
          if ($queue_items_collection_ids = self::getUpdatesQueueParagraphInstance($id)) {
            $queue_items = self::getQueueItems($queue_items_collection_ids);
            if (!in_array($entity->id(), $queue_items)) {
              // Skip queue if item not exist in queue_items_collection_ids.
              continue;
            }

            // Get field collection id for updating.
            $queue_collection_id = array_search($entity->id(), $queue_items);
            self::deleteItemFromFieldCollection($id['target_id'], $queue_collection_id, $entity, $queue_items);
          }
        }
      }

    }
  }

  /**
   * Delete entity from all referenced paragraphs.
   *
   * @param int $pid
   *   The Paragraph id.
   * @param int $fcid
   *   The Field Collection id.
   * @param object $entity
   *   The entity object of node type.
   */
  public static function deleteItemFromFieldCollection($pid, $fcid, $entity, $queue_items) {
    $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($pid);
    $collection = \Drupal::entityTypeManager()->getStorage('field_collection_item')->load($fcid);
    if ($paragraph->bundle() == 'branch_updates_queue') {
      // Search new blog for this location.
      $new_blog_item = \Drupal::entityQuery('node')
        ->condition('type', 'blog')
        ->condition('status', 1)
        ->condition('nid', $queue_items, 'NOT IN')
        ->condition('field_add_to_branch_queue', 1)
        ->condition('field_blog_location', $paragraph->parent_id->value)
        ->range(0, 1)
        ->sort('changed', 'ASC')
        ->execute();
      $new_blog_item = !empty($new_blog_item) ? reset($new_blog_item) : NULL;
    }
    elseif ($paragraph->bundle() == 'frontpage_updates_queue') {
      // Search new blog for front page.
      $new_blog_item = \Drupal::entityQuery('node')
        ->condition('type', 'blog')
        ->condition('status', 1)
        ->condition('nid', $queue_items, 'NOT IN')
        ->condition('field_homepage_queue', 1)
        ->range(0, 1)
        ->sort('changed', 'ASC')
        ->execute();
      $new_blog_item = !empty($new_blog_item) ? reset($new_blog_item) : NULL;
    }
    else {
      return;
    }
    // Replace old blog item.
    $collection->set('field_updates_queue_update', $new_blog_item);
    $collection->save();
  }

  /**
   * Returns all paragraph ids referenced to the branch.
   *
   * @param int $id
   *   The entity id.
   *
   * @return mixed
   *   Array with all field collections ids referenced to the paragraph or boolean.
   */
  public static function getUpdatesQueueParagraphInstance($id) {
    if ($paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($id['target_id'])) {
      if ($paragraph->bundle() == 'branch_updates_queue') {
        return $paragraph->field_updates_queue_item_collect->getValue();
      }
      if ($paragraph->bundle() == 'frontpage_updates_queue') {
        return $paragraph->field_updates_queue_home->getValue();
      }
    }
    return FALSE;
  }

  /**
   * Returns unlocked item key.
   *
   * @param int $key
   *   Array element key.
   * @param array $qid
   *   Array with target_id.
   *
   * @return mixed
   *   Unlocked item key or boolean.
   */
  public static function getUnlockedItemKey($key, array $qid) {
    if (isset($qid['target_id'])) {
      if ($collection = \Drupal::entityTypeManager()->getStorage('field_collection_item')->load($qid['target_id'])) {
        // Get all unlocked items for further usage.
        if (!$collection->field_updates_queue_locked->value) {
          return $key;
        }
      }
    }
    return FALSE;
  }

  /**
   * Returns queue items ID's.
   *
   * @param array $collection_ids_raw
   *   Array with target_ids.
   *
   * @return array
   *   Queue items ID's (collection_id => blog_id).
   */
  public static function getQueueItems(array $collection_ids_raw) {
    $collection_ids = [];
    $queue_items = [];
    foreach ($collection_ids_raw as $qid) {
      if (isset($qid['target_id'])) {
        $collection_ids[] = $qid['target_id'];
      }
    }
    $collections = \Drupal::entityTypeManager()->getStorage('field_collection_item')->loadMultiple($collection_ids);
    if (empty($collections)) {
      return $queue_items;
    }
    foreach ($collections as $collection) {
      if ($collection->field_updates_queue_update->first()) {
        $queue_items[$collection->id()] = $collection->field_updates_queue_update->first()->get('target_id')->getValue();
      }
    }

    return $queue_items;
  }

  /**
   * Update branch updates queue.
   *
   * @param object $entity
   *   The entity object of node type.
   * @param array $unlocked_items_keys
   *   Array with not locked positions in the field collection.
   * @param array $queue_items_collection_ids
   *   Array with all field collections ids referenced to the paragraph.
   */
  public static function updateQueue($entity, array $unlocked_items_keys, array $queue_items_collection_ids) {
    $first_unlocked_position = $unlocked_items_keys[0];
    foreach ($unlocked_items_keys as $key => $old_key) {
      if (isset($unlocked_items_keys[$key + 1])) {
        $new_key = $unlocked_items_keys[$key + 1];
        $new_unlocked_items_mapping[$new_key] = $old_key;
      }
    }
    $queue_items_collection_ids = array_reverse($queue_items_collection_ids, TRUE);
    foreach ($queue_items_collection_ids as $key => $qid) {
      $collection_to = \Drupal::entityTypeManager()->getStorage('field_collection_item')->load($queue_items_collection_ids[$key]['target_id']);
      $update = $collection_to->field_updates_queue_update->target_id;
      $lock = $collection_to->field_updates_queue_locked->value;
      // Move unlocked items down.
      if (isset($new_unlocked_items_mapping[$key])) {
        $collection_from = \Drupal::entityTypeManager()->getStorage('field_collection_item')->load($queue_items_collection_ids[$new_unlocked_items_mapping[$key]]['target_id']);
        $update = $collection_from->field_updates_queue_update->target_id;
        $lock = $collection_from->field_updates_queue_locked->value;
      }
      $collection_to->field_updates_queue_update->setValue($update);
      $collection_to->field_updates_queue_locked->setValue($lock);
      $collection_to->save();
    }

    self::insertBlogOnTopPosition($entity, $queue_items_collection_ids, $first_unlocked_position);
  }

  /**
   * Returns all paragraph ids referenced to the branch.
   *
   * @param object $entity
   *   The entity object of node type.
   * @param array $queue_items_collection_ids
   *   Array with all field collections ids referenced to the paragraph.
   * @param int $first_unlocked_position
   *   Key indicates delta value.
   */
  public static function insertBlogOnTopPosition($entity, array $queue_items_collection_ids, $first_unlocked_position) {
    if ($target_collection = \Drupal::entityTypeManager()->getStorage('field_collection_item')->load($queue_items_collection_ids[$first_unlocked_position]['target_id'])) {
      $target_collection->field_updates_queue_update->setValue($entity->id());
      // Newly added blog post is unlocked by default.
      $target_collection->field_updates_queue_locked->setValue(0);
      $target_collection->save();
    }
  }

}
