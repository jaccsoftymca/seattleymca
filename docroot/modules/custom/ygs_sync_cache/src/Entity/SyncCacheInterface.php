<?php

namespace Drupal\ygs_sync_cache\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Sync Cache entities.
 *
 * @ingroup ygs_sync_cache
 */
interface SyncCacheInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Sync Cache name.
   *
   * @return string
   *   Name of the Sync Cache.
   */
  public function getName();

  /**
   * Sets the Sync Cache name.
   *
   * @param string $name
   *   The Sync Cache name.
   *
   * @return \Drupal\ygs_sync_cache\Entity\SyncCacheInterface
   *   The called Sync Cache entity.
   */
  public function setName($name);

  /**
   * Gets the Sync Cache creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Sync Cache.
   */
  public function getCreatedTime();

  /**
   * Sets the Sync Cache creation timestamp.
   *
   * @param int $timestamp
   *   The Sync Cache creation timestamp.
   *
   * @return \Drupal\ygs_sync_cache\Entity\SyncCacheInterface
   *   The called Sync Cache entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Sync Cache published status indicator.
   *
   * Unpublished Sync Cache are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Sync Cache is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Sync Cache.
   *
   * @param bool $published
   *   TRUE to set this Sync Cache to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ygs_sync_cache\Entity\SyncCacheInterface
   *   The called Sync Cache entity.
   */
  public function setPublished($published);

}
