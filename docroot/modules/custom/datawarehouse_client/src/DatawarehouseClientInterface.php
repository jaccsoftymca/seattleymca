<?php

namespace Drupal\datawarehouse_client;

/**
 * Datawarehouse Service interface.
 */
interface DatawarehouseClientInterface {

  /**
   * Make request to Datawarehouse database.
   *
   * @param string $query
   *   SQL query.
   *
   * @return array
   *   A result.
   */
  public function call($query = '');

}
