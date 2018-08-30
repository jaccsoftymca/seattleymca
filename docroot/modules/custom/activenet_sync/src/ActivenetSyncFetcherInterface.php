<?php

namespace Drupal\activenet_sync;

/**
 * Interface ActivenetSyncFetcherInterface.
 *
 * @package Drupal\activenet_sync
 */
interface ActivenetSyncFetcherInterface {

  /**
   * Fetch data.
   *
   * @param array $data
   *   Data for import.
   */
  public function fetch(array $data);

}
