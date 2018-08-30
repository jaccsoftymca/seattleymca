<?php

namespace Drupal\activenet_client;

/**
 * Activenet Service interface.
 */
interface ActivenetClientInterface {

  /**
   * Make request to Activenet API.
   *
   * @param array $params
   *   Array of parameters.
   *
   * @return \stdClass
   *   A result.
   */
  public function call(array $params = []);

}
