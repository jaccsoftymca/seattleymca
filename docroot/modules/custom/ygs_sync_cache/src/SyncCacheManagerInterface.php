<?php

namespace Drupal\ygs_sync_cache;

/**
 * Interface SyncCacheManagerInterface.
 *
 * @package Drupal\ygs_sync_cache
 */
interface SyncCacheManagerInterface {

  /**
   * Reset the cache.
   */
  public function resetCache();

  /**
   * Reset the cache by type.
   *
   * @param string $type
   *   SyncCache entity type.
   */
  public function resetCacheByType($type);

  /**
   * Reset the cache by status.
   *
   * @param string $status
   *   SyncCache entity status.
   */
  public function resetCacheByStatus($status);

  /**
   * Reset the cache by ID's.
   *
   * @param array $ids
   *   SyncCache entity ID's.
   */
  public function deleteCacheItems(array $ids);

}
