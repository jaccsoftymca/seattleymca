<?php

namespace Drupal\activenet_sync;

/**
 * Interface ActivenetSyncPusherInterface.
 *
 * @package Drupal\activenet_sync
 */
interface ActivenetSyncPusherInterface {

  /**
   * Push entities.
   */
  public function push();

}
