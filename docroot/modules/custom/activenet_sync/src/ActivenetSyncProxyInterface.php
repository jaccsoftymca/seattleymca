<?php

namespace Drupal\activenet_sync;

/**
 * Interface ActivenetSyncProxyInterface.
 *
 * @package Drupal\activenet_sync
 */
interface ActivenetSyncProxyInterface {

  /**
   * Save entities to Drupal database and push them to data wrapper.
   */
  public function saveEntities();

}
