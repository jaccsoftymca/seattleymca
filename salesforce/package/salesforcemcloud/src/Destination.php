<?php
namespace Ymca\Salesforcemcloud;

/**
 * Data destination.
 *
 * @package Ymca\Salesforcemcloud
 */
class Destination {
  /**
   * Connection to the Salesfoce MCloud.
   * @var ET_Client
   */
  private $connection;

  /**
   * Destination constructor.
   *
   * @param string $clientid
   *   Salesforce MCloud client ID.
   * @param string $clientsecret
   *   Salesforce MCloud client Secret.
   */
  public function __construct($clientid, $clientsecret)  {
    try {
      $this->connection = new \ET_Client(TRUE, TRUE, ['clientid' => $clientid, 'clientsecret' => $clientsecret]);
    }
    catch (\Exception $e) {
      throw new \Exception('Failed to connect to destination.');
    }
  }

  public function getClient() {
    return $this->connection;
  }
}
