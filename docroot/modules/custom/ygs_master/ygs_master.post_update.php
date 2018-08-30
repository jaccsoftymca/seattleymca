<?php

/**
 * @file
 * Contains hook_post_update_NAME() implementations.
 */

use Drupal\activenet_sync\TypedData\Definition\ActiveNetDefinition;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Fix activity nodes moderation state.
 */
function ygs_master_post_update_fix_activities_moderation_state(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['updated'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->count()
      ->execute();
  }

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 10)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $node) {
    if (!$node->moderation_state->entity->isPublishedState() && $node->isPublished()) {
      $node->moderation_state->target_id = 'published';
      $node->save();
      $sandbox['updated']++;
    }
    $sandbox['progress']++;
    $sandbox['current'] = $node->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  return t('@count activity nodes moderation state have been fixed', ['@count' => $sandbox['updated']]);
}

/**
 * Fix session nodes non-deleted FC items references.
 */
function ygs_master_post_update_fix_uneditable_sessions(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;

    $query = \Drupal::database()->select('node__field_session_time_collection', 'f');
    $query->fields('f', ['entity_id']);
    $query->leftJoin('field_collection_item', 'fci', 'fci.item_id = f.field_session_time_collection_target_id');
    $query->isNull('item_id');
    $query->distinct(TRUE);
    $query = $query->countQuery();
    $sandbox['max'] = $query->execute()->fetchField();
  }

  $query = \Drupal::database()->select('node__field_session_time_collection', 'f');
  $query->fields('f', ['entity_id']);
  $query->leftJoin('field_collection_item', 'fci', 'fci.item_id = f.field_session_time_collection_target_id');
  $query->isNull('item_id');
  $query->condition('entity_id', $sandbox['current'], '>');
  $query->distinct(TRUE);
  $query->orderBy('entity_id');
  $query->range(0, 10);
  $ids = $query->execute()->fetchAllKeyed(0, 0);

  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $node) {
    $session_time_collection = [];
    foreach ($node->field_session_time_collection->referencedEntities() as $entity) {
      $session_time_collection[] = [
        'target_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
      ];
    };
    $node->set('field_session_time_collection', $session_time_collection);
    $node->save();

    $sandbox['progress']++;
    $sandbox['current'] = $node->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  return t('@count session nodes have been fixed', ['@count' => $sandbox['progress']]);
}

/**
 * Remove content moderation states for Session CT.
 */
function ygs_master_post_update_remove_sessions_content_moderation(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;

    $query = \Drupal::database()->select('node', 'n');
    $query->innerJoin('content_moderation_state_field_data', 'c', 'n.nid = c.content_entity_id AND c.content_entity_type_id = :type', [':type' => 'node']);
    $query->fields('c', ['id']);
    $query->condition('n.type', 'session');
    $query = $query->countQuery();
    $sandbox['max'] = $query->execute()->fetchField();
  }

  $query = \Drupal::database()->select('node', 'n');
  $query->innerJoin('content_moderation_state_field_data', 'c', 'n.nid = c.content_entity_id AND c.content_entity_type_id = :type', [':type' => 'node']);
  $query->fields('c', ['id']);
  $query->condition('n.type', 'session');
  $query->condition('id', $sandbox['current'], '>');
  $query->orderBy('id');
  $query->range(0, 500);
  $ids = $query->execute()->fetchAllKeyed(0, 0);

  $content_moderation_states = \Drupal::entityTypeManager()->getStorage(ygs_moderation_wrapper_active_module() . '_state')->loadMultiple($ids);
  foreach ($content_moderation_states as $content_moderation_state) {
    $content_moderation_state->delete();
    $sandbox['progress']++;
    $sandbox['current'] = $content_moderation_state->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  return t('@count content moderation states have been removed', ['@count' => $sandbox['progress']]);
}

/**
 * Clean up classes node revisions.
 */
function ygs_master_post_update_zcleanup_classes_revisions(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;

    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->innerJoin('node_revision', 'r', 'r.nid = n.nid');
    $query->addField('n', 'nid');
    $query->addExpression('COUNT(*)', 'cnt');
    $query->condition('n.type', 'class');
    $query->groupBy('n.nid');
    $query->having('COUNT(*) > :threshold', [':threshold' => 10]);
    $query = $query->countQuery();
    $sandbox['max'] = $query->execute()->fetchField();
  }

  $threshold = 50;

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $cm_storage = \Drupal::entityTypeManager()->getStorage('content_moderation_state');

  // Clean up upto 10 mostly revisioned classes a time.
  $query = \Drupal::database()->select('node_field_data', 'n');
  $query->innerJoin('node_revision', 'r', 'r.nid = n.nid');
  $query->addField('n', 'nid');
  $query->addExpression('COUNT(*)', 'cnt');
  $query->condition('n.type', 'class');
  $query->groupBy('n.nid');
  $query->having('COUNT(*) > :threshold', [':threshold' => $threshold]);
  $query->orderBy('cnt', 'DESC');
  $query->range(0, 10);
  $node_ids = $query->execute()->fetchAllKeyed(0, 0);

  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_ids);
  $_timer = microtime(1);
  foreach ($nodes as $node) {
    // Do not proceed if it's already spent more than 30 seconds.
    if (microtime(1) - $_timer > 30) {
      break;
    }

    // Get revisions ids.
    $node_revision_ids = $node_storage->revisionIds($node);
    arsort($node_revision_ids);
    // Leave only $threshold of the latest revisions.
    $node_revisions_to_delete = array_slice($node_revision_ids, $threshold);
    // Delete the latest node revisions.
    foreach ($node_revisions_to_delete as $revision_id) {
      $node_storage->deleteRevision($revision_id);
    }

    // Retrieve all Content moderation state revisions ids.
    $cms_revisions = $cm_storage->getQuery()
      ->allRevisions()
      ->condition('content_entity_type_id', 'node')
      ->condition('content_entity_revision_id', $node_revisions_to_delete, 'IN')
      ->accessCheck(FALSE)
      ->execute();
    $cms_revision_ids = array_keys($cms_revisions);

    // Delete the Content moderation state revisions.
    \Drupal::database()->delete('content_moderation_state_field_revision')
      ->condition('revision_id', $cms_revision_ids, 'IN')
      ->execute();
    \Drupal::database()->delete('content_moderation_state_revision')
      ->condition('revision_id', $cms_revision_ids, 'IN')
      ->execute();

    $sandbox['progress']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  if (!$node_ids) {
    $sandbox['#finished'] = 1;
  }

  return t('@count classes have been cleaned up', ['@count' => $sandbox['progress']]);
}

/**
 * Update sales_date field value in activenet sessions.
 */
function ygs_master_post_update_add_sessions_sales_date(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('sync_cache')
      ->condition('type', 'activenet')
      ->count()
      ->execute();

    // Limit updates for non-acquia environments.
    if (empty($_ENV['AH_SITE_ENVIRONMENT'])) {
      $sandbox['max'] = min($sandbox['max'], 20);
    }
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  // Update sync_cache session in chunks of 20 entities.
  $ids = \Drupal::entityQuery('sync_cache')
    ->condition('type', 'activenet')
    ->condition('id', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('id')
    ->execute();
  $sync_caches = \Drupal::entityTypeManager()->getStorage('sync_cache')->loadMultiple($ids);
  $definition = ActiveNetDefinition::create('active_net_data');
  foreach ($sync_caches as $sync_cache) {
    $sessions = $sync_cache->get('session')->referencedEntities();
    if (!empty($sessions)) {
      $session = reset($sessions);
      /* @var $typed_data \Drupal\activenet_sync\Plugin\DataType\ActiveNetData */
      $typed_data = \Drupal::typedDataManager()->create($definition);
      $raw_data = json_decode($sync_cache->raw_data->value, TRUE);
      $typed_data->setValue($raw_data);
      $typed_data->validate();

      $sales_func = function ($key) {
        $sales_date = NULL;
        if (!empty($sd = $this->get('dwData')->get($key))) {
          if (!is_null($sd = $sd->getDateTime())) {
            $sales_date = $sd->setTimezone(new DateTimeZone('UTC'))
              ->format('Y-m-d\TH:i:s');
          }
        }
        return $sales_date;
      };
      $sales_start_date = $sales_func('sales_start_date');
      $sales_end_date = $sales_func('sales_end_date');
      if (!is_null($sales_start_date) && !is_null($sales_end_date)) {
        $session->set('field_sales_date', [
          'value' => $sales_start_date,
          'end_value' => $sales_end_date,
        ]);
      }
      $session->save();
    }
    $sandbox['progress']++;
    $sandbox['current'] = $sync_cache->id();
  }

  $_activenet_sync_disable_entity_hooks = FALSE;

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  return t('Cache were updated for @count entities', ['@count' => $sandbox['max']]);
}

/**
 * Migrate grid_content paragraph data from column_in_a_grid.
 */
function ygs_master_post_update_migrate_grid_content_data2(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('paragraph')
      ->condition('type', 'column_in_a_grid')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  // Migrate grid_content paragraph data in chunks of 20 entities.
  $paragraph_ids = \Drupal::entityQuery('paragraph')
    ->condition('type', 'column_in_a_grid')
    ->condition('id', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('id')
    ->execute();
  $entity_type_manager = \Drupal::entityTypeManager()->getStorage('node');
  $search_fields = [
    'field_content',
    'field_header_content',
    'field_sidebar_content',
  ];

  foreach ($paragraph_ids as $pid) {
    $sandbox['progress']++;
    $sandbox['current'] = $pid;
    // Get node ID's with reference to this paragraph ID.
    $query = \Drupal::entityQuery('node');
    $or_condition = $query->orConditionGroup()
      ->condition('field_content', $pid)
      ->condition('field_header_content', $pid)
      ->condition('field_sidebar_content', $pid);
    $node_ids = $query
      ->condition($or_condition)
      ->execute();
    if (!$node_ids) {
      continue;
    }
    foreach ($node_ids as $nid) {
      // Load node with reference to column_in_a_grid paragraph ID.
      $node = $entity_type_manager->load($nid);
      $columns_in_a_grid_for_delete = [];
      foreach ($search_fields as $search_field) {
        if (!$node->{$search_field}) {
          continue;
        }
        foreach ($node->{$search_field} as $delta => $field) {
          if ($field->get('target_id')->getValue() == $pid) {
            // Get paragraph if node field contains column_in_a_grid paragraph.
            $column_in_a_grid = $field->get('entity')->getTarget()->getValue();
            // Create grid_content paragraph with values from column_in_a_grid.
            $grid_content = Paragraph::create([
              'type' => 'grid_content',
              'parent_id' => $column_in_a_grid->parent_id->value,
              'parent_type' => $column_in_a_grid->parent_type->value,
              'parent_field_name' => $column_in_a_grid->parent_field_name->value,
              'status' => 1,
              'field_prgf_grid_style' => $column_in_a_grid->field_style->value,
            ]);
            $grid_content->save();
            $created_grid_columns = [];
            foreach ($column_in_a_grid->field_collection_content->referencedEntities() as $fc_content) {
              // Create grid_columns paragraph with values from
              // field_collection_content.
              $grid_column = Paragraph::create([
                'type' => 'grid_columns',
                'parent_id' => $grid_content->id(),
                'parent_type' => 'paragraph',
                'parent_field_name' => 'field_grid_columns',
                'status' => 1,
                'field_prgf_grid_clm_description' => [
                  'value' => $fc_content->field_column_description->value,
                  'format' => 'full_html',
                ],
                'field_prgf_clm_headline' => $fc_content->field_column_headline->value,
                'field_prgf_clm_icon' => $fc_content->field_icon->first() ? $fc_content->field_icon->first()->get('target_id')->getValue() : NULL,
                'field_prgf_clm_class' => $fc_content->field_icon_class->value,
                'field_prgf_clm_link' => [
                  'uri' => $fc_content->field_column_link->first() ? $fc_content->field_column_link->first()->uri : NULL,
                  'title' => $fc_content->field_column_link->first() ? $fc_content->field_column_link->first()->title : NULL,
                  'options' => $fc_content->field_column_link->first() ? $fc_content->field_column_link->first()->options : NULL,
                ],
              ]);
              $grid_column->save();
              $created_grid_columns[] = $grid_column;
            }
            // Add created grid_columns to grid_content paragraph.
            $grid_content->set('field_grid_columns', $created_grid_columns);
            $grid_content->save();
            $columns_in_a_grid_for_delete[] = $column_in_a_grid;
            // Replace column_in_a_grid paragraph by grid_content paragraph.
            $node->{$search_field}->set($delta, $grid_content);
          }
        }
      }
      $node->save();
      // Delete old column_in_a_grid paragraphs.
      foreach ($columns_in_a_grid_for_delete as $column_for_delete) {
        $column_for_delete->delete();
      }
    }
  }

  $_activenet_sync_disable_entity_hooks = FALSE;

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  if ($sandbox['#finished'] == 1) {
    // Delete column_in_a_grid paragraphs (when data was migrated).
    $paragraph_ids = \Drupal::entityQuery('paragraph')
      ->condition('type', 'column_in_a_grid')
      ->sort('id')
      ->execute();
    entity_delete_multiple('paragraph', $paragraph_ids);

    $paragraph_type = \Drupal::entityTypeManager()->getStorage('paragraphs_type')->load('column_in_a_grid');
    $paragraph_type->delete();
  }
  return t('Fields data were migrated for @count entities', ['@count' => $sandbox['max']]);
}

/**
 * Migrate locations CT's complex fields.
 */
function ygs_master_post_update_migrate_branch_complex_fields(&$sandbox) {
  $loc_types = ['branch', 'camp', 'facility'];
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', $loc_types, 'IN')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $mapping = [
    'monday' => 'hours_mon',
    'tuesday' => 'hours_tue',
    'wednesday' => 'hours_wed',
    'thursday' => 'hours_thu',
    'friday' => 'hours_fri',
    'saturday' => 'hours_sat',
    'sunday' => 'hours_sun',
  ];
  $to_delete = ['field_latitude', 'field_longitude', 'field_collection_hours'];
  $ids = \Drupal::entityQuery('node')
    ->condition('type', $loc_types, 'IN')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $location) {
    // Migrate locations.
    $location->set('field_location_coordinates', [[
      'lat' => $location->field_latitude ? $location->field_latitude->value : NULL,
      'lng' => $location->field_longitude ? $location->field_longitude->value : NULL,
    ]]);
    if ($location->getType() == 'branch') {
      // Migrate branch hours.
      $hours = $location->field_collection_hours->referencedEntities();
      $new_hours = ['hours_label' => 'Branch Hours'];
      foreach ($hours as $hour) {
        $day = $hour->field_day_of_the_week->value;
        $new_hours[$mapping[$day]] = $hour->field_start_end_time->value;
      }
      $location->set('field_branch_hours', [$new_hours]);
    }
    $location->save();
    $sandbox['progress']++;
    $sandbox['current'] = $location->id();
  }

  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  if ($sandbox['#finished'] == 1) {
    foreach ($loc_types as $loc_type) {
      // Delete locations old fields.
      $properties = array(
        'entity_type' => 'node',
        'bundle' => $loc_type,
        'include_deleted' => TRUE,
      );
      $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties($properties);
      foreach ($fields as $field) {
        $entity_type = $field->getTargetEntityTypeId();
        if ($loc_type == 'facility' && $field->getName() == 'field_collection_hours') {
          // Skip facility field_collection_hours.
          continue;
        }
        if (in_array($field->getName(), $to_delete)) {
          Drupal::entityTypeManager()->getStorage($entity_type)->purgeFieldData($field, 100);
          $field->delete();
          field_purge_field($field);
        }
      }
    }
  }
}

/**
 * Migrate membership_info data from field collection to paragraph.
 */
function ygs_master_post_update_migrate_membership_info(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', 'membership')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'membership')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $membership) {
    $new_mbrshp_info_prgfs = [];
    foreach ($membership->field_membership_info->referencedEntities() as $membership_info_fc) {
      // Create paragraph from field collection data.
      $new_mbrshp_info_prgfs[] = Paragraph::create([
        'type' => 'membership_info',
        'parent_id' => $membership->id(),
        'parent_type' => 'node',
        'parent_field_name' => 'field_mbrshp_info',
        'status' => 1,
        'field_mbrshp_join_fee' => $membership_info_fc->field_join_fee->value,
        'field_mbrshp_link' => [
          'uri' => $membership_info_fc->field_link->first() ? $membership_info_fc->field_link->first()->uri : NULL,
          'title' => $membership_info_fc->field_link->first() ? $membership_info_fc->field_link->first()->title : NULL,
          'options' => $membership_info_fc->field_link->first() ? $membership_info_fc->field_link->first()->options : NULL,
        ],
        'field_mbrshp_location' => $membership_info_fc->field_location->first() ? $membership_info_fc->field_location->first()
          ->get('target_id')
          ->getValue() : NULL,
        'field_mbrshp_monthly_rate' => $membership_info_fc->field_monthly_rate->value,
      ]);
    }

    $membership->set('field_mbrshp_info', $new_mbrshp_info_prgfs);
    $membership->save();
    $sandbox['progress']++;
    $sandbox['current'] = $membership->id();
  }

  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  if ($sandbox['#finished'] == 1) {
    // Delete field_membership_info.
    $properties = array(
      'entity_type' => 'node',
      'bundle' => 'membership',
      'include_deleted' => TRUE,
    );
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties($properties);
    foreach ($fields as $field) {
      $entity_type = $field->getTargetEntityTypeId();
      if ($field->getName() == 'field_membership_info') {
        Drupal::entityTypeManager()->getStorage($entity_type)->purgeFieldData($field, 100);
        $field->delete();
        field_purge_field($field);
      }
    }
  }

}

/**
 * Update blog CT color fields values based on old style fields value.
 */
function ygs_master_post_update_blog_color_fields(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', ['blog', 'announcement'], 'IN')
      ->condition('field_blog_style', ['green', 'fuchsia'], 'IN')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', ['blog', 'announcement'], 'IN')
    ->condition('nid', $sandbox['current'], '>')
    ->condition('field_blog_style', ['green', 'fuchsia'], 'IN')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  // Get colors.
  $manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $fuchsia = $manager->loadByProperties(['name' => 'Fuchsia']);
  $fuchsia = reset($fuchsia);
  $white = $manager->loadByProperties(['name' => 'White']);
  $white = reset($white);
  $turquoise = $manager->loadByProperties(['name' => 'Turquoise']);
  $turquoise = reset($turquoise);
  // Update color fields values based on old style fields value.
  foreach ($nodes as $node) {
    $node->set('field_blog_text_color', ['target_id' => $white->id()]);
    if ($node->field_blog_style->value == 'green') {
      $node->set('field_blog_color', ['target_id' => $turquoise->id()]);
    }
    elseif ($node->field_blog_style->value == 'fuchsia') {
      $node->set('field_blog_color', ['target_id' => $fuchsia->id()]);
    }
    $node->set('field_blog_style', 'color');
    $node->save();
    $sandbox['progress']++;
    $sandbox['current'] = $node->id();
  }
  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  if ($sandbox['#finished'] == 1) {
    // Delete blog_post node type.
    $content_type = \Drupal::entityTypeManager()->getStorage('node_type')->load('blog_post');
    $content_type->delete();
  }
}

/**
 * Update program CT color fields values based on old style fields value.
 */
function ygs_master_post_update_program_color_fields(&$sandbox) {
  $bundles = ['program', 'program_subcategory'];
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', $bundles, 'IN')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', $bundles, 'IN')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  // Get colors.
  $manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $purple = $manager->loadByProperties(['name' => 'Purple']);
  $purple = reset($purple);
  $green = $manager->loadByProperties(['name' => 'Green']);
  $green = reset($green);
  $dark_green = $manager->loadByProperties(['name' => 'Dark Green']);
  $dark_green = reset($dark_green);
  // Update color fields values based on old style fields value.
  foreach ($nodes as $node) {
    $color = $green->id();
    switch ($node->field_color_program->value) {
      case 'green':
        $color = $green->id();
        break;

      case 'purple':
        $color = $purple->id();
        break;

      case 'dark_green':
        $color = $dark_green->id();
        break;
    }
    if ($node->getType() == 'program') {
      $node->set('field_program_color', ['target_id' => $color]);
    }
    elseif ($node->getType() == 'program_subcategory') {
      $node->set('field_category_color', ['target_id' => $color]);
    }
    $node->save();
    $sandbox['progress']++;
    $sandbox['current'] = $node->id();
  }
  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}

/**
 * Delete program, program_subcategory color fields.
 */
function ygs_master_post_update_program_color_fields_delete() {
  $bundles = ['program', 'program_subcategory'];
  foreach ($bundles as $bundle) {
    // Delete old fields.
    $properties = [
      'entity_type' => 'node',
      'bundle' => $bundle,
      'include_deleted' => TRUE,
    ];
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties($properties);
    foreach ($fields as $field) {
      $entity_type = $field->getTargetEntityTypeId();
      if ($field->getName() == 'field_color_program') {
        \Drupal::entityTypeManager()->getStorage($entity_type)->purgeFieldData($field, 50);
        $field->delete();
        field_purge_field($field);
      }
    }
  }
}

/**
 * Migrate field_session_time_collection from field collection to paragraph.
 */
function ygs_master_post_update_migrate_session_time(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', 'session')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'session')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $session) {
    $session_time_prgfs = [];
    foreach ($session->field_session_time_collection->referencedEntities() as $session_time_fc) {
      // Create paragraph from field collection data.
      $days = [];
      foreach ($session_time_fc->field_session_days->getValue() as $value) {
        $days[] = $value['value'];
      }
      $session_time_prgfs[] = Paragraph::create([
        'type' => 'session_time',
        'parent_id' => $session->id(),
        'parent_type' => 'node',
        'parent_field_name' => 'field_session_time',
        'status' => 1,
        'field_session_time_actual' => $session_time_fc->field_session_actual->value,
        'field_session_time_days' => $days,
        'field_session_time_frequency' => $session_time_fc->field_session_frequency->value,
        'field_session_time_date' => [
          'value' => $session_time_fc->field_session_date->value,
          'end_value' => $session_time_fc->field_session_date->end_value,
        ],
      ]);
      $session_time_fc->delete();
    }

    $session->set('field_session_time', $session_time_prgfs);
    $session->save();
    $sandbox['progress']++;
    $sandbox['current'] = $session->id();
  }

  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}

/**
 * Delete field_session_time_collection.
 */
function ygs_master_post_update_migrate_session_time_delete() {
  $properties = array(
    'entity_type' => 'node',
    'bundle' => 'session',
    'include_deleted' => TRUE,
  );
  $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties($properties);
  foreach ($fields as $field) {
    $entity_type = $field->getTargetEntityTypeId();
    if ($field->getName() == 'field_session_time_collection') {
      Drupal::entityTypeManager()->getStorage($entity_type)->purgeFieldData($field, 50);
      $field->delete();
      field_purge_field($field);
    }
  }
}

/**
 * Add classes listing to program subcategory.
 */
function ygs_master_post_update_add_classes_listing_to_program_subcategory(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', 'program_subcategory')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'program_subcategory')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $program_subcategory) {
    $classes_listing = Paragraph::create([
      'type' => 'ygs_classes_listing',
      'parent_id' => $program_subcategory->id(),
      'parent_type' => 'node',
      'parent_field_name' => 'field_content',
      'status' => 1,
    ]);
    $program_subcategory->field_header_content->appendItem($classes_listing);
    $program_subcategory->save();
    $sandbox['progress']++;
    $sandbox['current'] = $program_subcategory->id();
  }
  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}

/**
 * Add openy_prgf_categories_listing to program.
 */
function ygs_master_post_update_add_openy_prgf_categories_listing(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', 'program')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'program')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $program) {
    $categories_listing = Paragraph::create([
      'type' => 'categories_listing',
      'parent_id' => $program->id(),
      'parent_type' => 'node',
      'parent_field_name' => 'field_content',
      'status' => 1,
    ]);
    $program->field_content->appendItem($categories_listing);
    $program->save();
    $sandbox['progress']++;
    $sandbox['current'] = $program->id();
  }
  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}

/**
 * Add camp menu to node pages.
 */
function ygs_master_post_update_add_camp_menu_to_node_pages(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', 'camp')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'camp')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 1)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  foreach ($nodes as $camp) {
    $l_ids = [];
    if ($camp->field_camp_menu_links) {
      // Create camp menu paragraph for camp node.
      $camp_menu = Paragraph::create([
        'type' => 'camp_menu',
        'parent_id' => $camp->id(),
        'parent_type' => 'node',
        'parent_field_name' => 'field_header_content',
        'status' => 1,
      ]);
      $camp->field_header_content->appendItem($camp_menu);
      $camp->save();

      // Get landing pages id's.
      foreach ($camp->field_camp_menu_links as $link) {
        $url = $link->getUrl();
        if ($url->isExternal()) {
          $external_uri = $url->getUri();
          if (strpos($external_uri, 'http://www.seattleymca.org/node/') !== FALSE) {
            $l_ids[] = preg_replace('/[^0-9]/', '', $external_uri);
          }
        }
        else {
          // Get node parametr.
          $params = $url->getRouteParameters();
          if ($params['node']) {
            $l_ids[] = $params['node'];
          }
        }
      }
    }
    $landings = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($l_ids);
    foreach ($landings as $landing) {
      if ($landing->getType() != 'landing_page') {
        continue;
      }
      // Create camp menu paragraph for landing_page node.
      $camp_menu = Paragraph::create([
        'type' => 'camp_menu',
        'parent_id' => $camp->id(),
        'parent_type' => 'node',
        'parent_field_name' => 'field_header_content',
        'status' => 1,
      ]);
      $landing->field_header_content->appendItem($camp_menu);
      $landing->save();
    }

    $sandbox['progress']++;
    $sandbox['current'] = $camp->id();
  }
  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}

/**
 * Upgrade path tools - create logger entities for modified configs.
 */
function ygs_master_post_update_create_upgrade_path_tools_changed_configs(&$sandbox) {
  $updated_configs = [
    'core.entity_form_display.node.branch.default',
    'core.entity_view_display.node.branch.default',
    'core.entity_view_display.node.branch.header_branch',
    'core.entity_view_display.node.branch.full',
    'core.entity_view_display.node.branch.teaser',
    'core.entity_form_display.node.camp.default',
    'core.entity_view_display.node.camp.default',
    'core.entity_view_display.node.camp.header_camp',
    'core.entity_view_display.node.camp.teaser',
    'core.entity_form_display.node.facility.default',
    'core.entity_view_display.node.facility.default',
    'core.entity_view_display.node.facility.full',
    'core.entity_view_display.node.facility.sidebar',
    'core.entity_view_display.node.facility.teaser',
    'node.type.branch',
    'node.type.camp',
    'node.type.facility',
    'core.entity_form_display.node.membership.default',
    'core.entity_view_display.node.membership.default',
    'core.entity_view_display.node.membership.registration',
    'core.entity_view_display.node.membership.full',
    'core.entity_view_display.node.membership.teaser',
    'core.entity_form_display.paragraph.membership_info.default',
    'node.type.membership',
    'core.entity_form_display.node.blog.default',
    'core.entity_view_display.node.blog.default',
    'core.entity_view_display.node.blog.full',
    'core.entity_view_display.node.blog.teaser',
    'node.type.blog',
    'field.field.node.blog.field_blog_location',
    'field.field.paragraph.featured_blog_posts.field_blog_posts',
    'field.field.node.branch.field_bottom_content',
    'field.field.node.branch.field_content',
    'field.field.node.branch.field_header_content',
    'field.field.node.camp.field_bottom_content',
    'field.field.node.camp.field_content',
    'field.field.node.camp.field_header_content',
    'field.field.node.class.field_content',
    'field.field.node.class.field_sidebar_content',
    'field.field.node.facility.field_content',
    'field.field.node.facility.field_sidebar_content',
    'field.field.node.program.field_content',
    'field.field.node.program.field_sidebar_content',
    'field.field.node.program_subcategory.field_content',
    'field.field.node.landing_page.field_header_content',
    'core.entity_form_display.node.landing_page.default',
    'core.entity_view_display.node.landing_page.default',
    'core.entity_view_display.node.landing_page.full',
    'core.entity_view_display.node.landing_page.header_program',
    'core.entity_view_display.node.landing_page.sidebar',
    'core.entity_view_display.node.landing_page.teaser',
    'node.type.landing_page',
    'core.entity_form_display.node.program.default',
    'core.entity_view_display.node.program.default',
    'core.entity_view_display.node.program.header_program',
    'core.entity_view_display.node.program.sidebar',
    'core.entity_view_display.node.program.teaser',
    'node.type.program',
    'image.style.node_program_header',
    'core.entity_form_display.node.program_subcategory.default',
    'core.entity_view_display.node.program_subcategory.default',
    'core.entity_view_display.node.program_subcategory.header_program',
    'node.type.program_subcategory',
    'core.entity_form_display.node.activity.default',
    'node.type.activity',
    'core.entity_form_display.node.class.default',
    'core.entity_view_display.node.class.default',
    'core.entity_view_display.node.class.sidebar',
    'core.entity_view_display.node.class.teaser',
    'core.entity_view_display.node.class.title',
    'node.type.class',
    'field.storage.node.field_class_activity',
    'pathauto.pattern.class',
    'core.entity_form_display.node.session.default',
    'core.entity_view_display.node.session.default',
    'core.entity_view_display.node.session.teaser',
    'core.entity_view_display.node.session.registration',
    'core.entity_view_display.node.session.schedule',
    'core.entity_view_display.node.session.session_teaser',
    'node.type.session',
    'core.entity_form_display.paragraph.session_time.default',
    'field.field.paragraph.promo_card.field_prgf_headline',
  ];
  $chunks = array_chunk($updated_configs, 5);

  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = count($chunks);
  }

  $logger_entity_storage = \Drupal::service('entity_type.manager')->getStorage('logger_entity');

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  foreach ($chunks[$sandbox['current']] as $config_name) {
    $logger_entity = $logger_entity_storage->create([
      'type' => 'openy_config_upgrade_logs',
      'name' => $config_name,
      'data' => serialize($config_name),
    ]);
    $logger_entity->save();
  }
  $sandbox['progress']++;
  $sandbox['current']++;

  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}

/**
 * Add to membership_info prices.
 */
function ygs_master_post_update_add_membership_prices(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('node')
      ->condition('type', 'membership')
      ->count()
      ->execute();
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  $ids = \Drupal::entityQuery('node')
    ->condition('type', 'membership')
    ->condition('nid', $sandbox['current'], '>')
    ->range(0, 1)
    ->sort('nid')
    ->execute();
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
  $prices = [
    1581 => ['join' => 125, 'monthly' => 120],
    1596 => ['join' => 25, 'monthly' => 42],
    1566 => ['join' => 100, 'monthly' => 73],
    1571 => ['join' => 50, 'monthly' => 52],
    1491 => ['join' => 125, 'monthly' => 129],
    1516 => ['join' => 100, 'monthly' => 109],
    1506 => ['join' => 75, 'monthly' => 66],
  ];
  foreach ($nodes as $id => $membership) {
    if (!isset($prices[$id])) {
      continue;
    }
    foreach ($membership->field_mbrshp_info->referencedEntities() as $membership_info) {
      $membership_info->set('field_mbrshp_join_fee', $prices[$id]['join']);
      $membership_info->set('field_mbrshp_monthly_rate', $prices[$id]['monthly']);
      $membership_info->save();
    }
    $sandbox['progress']++;
    $sandbox['current'] = $id;
  }
  $_activenet_sync_disable_entity_hooks = FALSE;
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}


/**
 * Update sales_date and online_registration fields in activenet sessions.
 */
function ygs_master_post_update_fix_sessions_online_registration(&$sandbox) {
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current'] = 0;
    $sandbox['max'] = \Drupal::entityQuery('sync_cache')
      ->condition('type', 'activenet')
      ->count()
      ->execute();

    // Limit updates for non-acquia environments.
    if (empty($_ENV['AH_SITE_ENVIRONMENT'])) {
      $sandbox['max'] = min($sandbox['max'], 20);
    }
  }

  global $_activenet_sync_disable_entity_hooks;
  $_activenet_sync_disable_entity_hooks = TRUE;

  // Update sync_cache session in chunks of 20 entities.
  $ids = \Drupal::entityQuery('sync_cache')
    ->condition('type', 'activenet')
    ->condition('id', $sandbox['current'], '>')
    ->range(0, 20)
    ->sort('id')
    ->execute();
  $sync_caches = \Drupal::entityTypeManager()->getStorage('sync_cache')->loadMultiple($ids);
  $definition = ActiveNetDefinition::create('active_net_data');
  foreach ($sync_caches as $sync_cache) {
    $sessions = $sync_cache->get('session')->referencedEntities();
    if (!empty($sessions)) {
      $session = reset($sessions);
      /* @var $typed_data \Drupal\activenet_sync\Plugin\DataType\ActiveNetData */
      $typed_data = \Drupal::typedDataManager()->create($definition);
      $raw_data = json_decode($sync_cache->raw_data->value, TRUE);
      $typed_data->setValue($raw_data);
      $typed_data->validate();
      $sales_func = function ($key) use ($typed_data) {
        $sales_date = NULL;
        if (!empty($sd = $typed_data->get('dwData')->get($key))) {
          if (!is_null($sd = $sd->getDateTime())) {
            $sales_date = $sd->setTimezone(new DateTimeZone('UTC'))
              ->format('Y-m-d\TH:i:s');
          }
        }
        return $sales_date;
      };
      $sales_start_date = $sales_func('sales_start_date');
      $sales_end_date = $sales_func('sales_end_date');
      $session->set('field_sales_date', []);
      if (!is_null($sales_start_date) && !is_null($sales_end_date)) {
        $session->set('field_sales_date', [
          'value' => $sales_start_date,
          'end_value' => $sales_end_date,
        ]);
      }
      $session->set('field_session_online', $typed_data->getSessionOnlineRegistration());
      $session->save();
    }
    $sandbox['progress']++;
    $sandbox['current'] = $sync_cache->id();
  }

  $_activenet_sync_disable_entity_hooks = FALSE;

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  return t('Caches were updated for @count entities', ['@count' => $sandbox['max']]);
}
