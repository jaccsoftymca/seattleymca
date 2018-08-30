<?php

namespace Drupal\datawarehouse_client;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Datawarehouse Service interface.
 */
class DatawarehouseClient implements DatawarehouseClientInterface {

  /**
   * Config Factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $config;

  /**
   * Credentials.
   *
   * @var array
   */
  protected $credentials;

  /**
   * Logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * Client.
   *
   * @var MsSqlWrapper
   */
  protected $client;

  /**
   * Datawarehouse Service Manager constructor.
   *
   * @param ConfigFactoryInterface $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Logger factory.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelInterface $logger) {
    $this->config = $config;
    $this->logger = $logger;
    $this->setCredentials();
    $this->setUpClient();
  }

  /**
   * Set credentials.
   */
  protected function setCredentials() {
    $settings = $this->config->get('datawarehouse_client.settings');
    // Check whether the module is configured.
    foreach ($settings->getRawData() as $item_name => $value) {
      if (empty($value)) {
        $message = "Datawarehouse DB credentials are not configured. \"$item_name\" is empty.";
        $this->logger->error($message);
        throw new \Exception($message);
      }
    }
    $this->credentials = [
      'server' => $settings->get('dw_server'),
      'port' => $settings->get('dw_port'),
      'db' => $settings->get('dw_db'),
      'user' => $settings->get('dw_user'),
      'pass' => $settings->get('dw_pass'),
    ];
  }

  /**
   * Set up a client.
   */
  protected function setUpClient() {
    $this->client = new MsSqlWrapper(
      $this->credentials['server'] . ':' . $this->credentials['port'],
      $this->credentials['user'],
      $this->credentials['pass'],
      $this->credentials['db']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function call($query = '') {
    $this->client->query($query);
    $result = $this->client->extract();
    // Fix DW encoding to UTF-8.
    array_walk_recursive($result, function (&$item, $key) {
      if (!mb_detect_encoding($item, 'utf-8', TRUE)) {
        $item = utf8_encode($item);
      }
    });
    return $result;
  }

}
