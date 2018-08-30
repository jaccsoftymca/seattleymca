<?php

namespace Drupal\activenet_sync;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityRepository;

/**
 * Class ActivenetSyncRepository.
 */
class ActivenetSyncRepository extends EntityRepository {

  /**
   * Get node by field_external_id.
   *
   * @param string $external_id
   *   'API:assetTags:tag:tagName' for class or 'API:assetGuid' for session.
   * @param string $node_type
   *   Node type (class|session).
   *
   * @return object|null
   *   Node.
   */
  public function getNodeByExternalId($external_id, $node_type) {
    if (!in_array($node_type, ['class', 'session'])) {
      return NULL;
    }
    return $this->getNodeByProperty([
      'type' => $node_type,
      'field_external_id' => $external_id,
    ]);
  }

  /**
   * Get Node by assetName.
   *
   * @param string $name
   *   'API:assetName'.
   * @param string $node_type
   *   Node type (class|session).
   *
   * @return object|null
   *   Node.
   */
  public function getNodeByAssetName($name, $node_type) {
    if (!in_array($node_type, ['class', 'session'])) {
      return NULL;
    }
    return $this->getNodeByProperty([
      'type' => $node_type,
      'title' => $name,
    ]);
  }

  /**
   * Get Node by PlaceName.
   *
   * @param string $name
   *   'API:place:placeName'.
   * @param array $node_types
   *   Node types (branch|camp).
   *
   * @return object|null
   *   Node.
   */
  public function getLocationByPlaceName($name, array $node_types) {
    return $this->getNodeByProperty([
      'type' => $node_types,
      'title' => $name,
    ]);
  }

  /**
   * Get Facility by FacilityName.
   *
   * @param string $name
   *   'dw:FACILITY:FACILITYNAME'.
   *
   * @return object|null
   *   Node.
   */
  public function getFacilityByName($name) {
    return $this->getNodeByProperty([
      'type' => 'facility',
      'title' => $name,
    ]);
  }

  /**
   * Get Node by properties.
   *
   * @param array $properties
   *   Array of properties (property => value).
   *
   * @return object|null
   *   Node.
   */
  public function getNodeByProperty(array $properties) {
    $manager = $this->entityTypeManager->getStorage('node');
    $entities = $manager->loadByProperties($properties);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * Check SyncCache existing by field_external_id.
   *
   * @param string $external_id
   *   'API:assetGuid'.
   *
   * @return object|null
   *   SyncCache entity.
   */
  public function checkExistingCache($external_id) {
    $manager = $this->entityTypeManager->getStorage('sync_cache');
    $entities = $manager->loadByProperties([
      'field_external_id' => $external_id,
    ]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * Touch Sync Cache entities.
   *
   * @param array $external_ids
   *   External ids list.
   */
  public function touchSyncCaches(array $external_ids, $type) {
    // Get Sync Cache ID's by External ids.
    $ids = \Drupal::entityQuery('sync_cache')
      ->condition('field_external_id', $external_ids, 'IN')
      ->condition('type', $type)
      ->execute();
    if (!empty($ids)) {
      $time = time();
      // Update touched field value in Sync Cache entities.
      $query_in_values = "'" . implode("', '", $ids) . "'";
      $query = "
      UPDATE {sync_cache}
      SET touched = $time
      WHERE id IN ($query_in_values);
    ";
      Database::getConnection('default')->query($query, [], []);
    }
  }

}
