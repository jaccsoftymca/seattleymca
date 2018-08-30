<?php

namespace Drupal\activenet_sync;

/**
 * Interface ActivenetSyncSyncerInterface.
 *
 * @package Drupal\activenet_sync
 */
interface ActivenetSyncSyncerInterface {

  /**
   * Sync entities.
   */
  public function sync();

}
