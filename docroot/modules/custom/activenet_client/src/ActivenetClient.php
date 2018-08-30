<?php

namespace Drupal\activenet_client;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;

/**
 * Activenet Service Manager.
 */
class ActivenetClient implements ActivenetClientInterface {

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
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Activenet Service Manager constructor.
   *
   * @param ConfigFactoryInterface $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Logger factory.
   * @param Client $client
   *   GuzzleHttp Client.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelInterface $logger, Client $client) {
    $this->config = $config;
    $this->logger = $logger;
    $this->client = $client;
  }

  /**
   * Set credentials.
   */
  protected function setCredentials() {
    $settings = $this->config->get('activenet_client.settings');
    // Check whether the module is configured.
    foreach ($settings->getRawData() as $item_name => $value) {
      if (empty($value)) {
        $message = "Activenet API credentials are not configured. \"$item_name\" is empty.";
        $this->logger->error($message);
        throw new \Exception($message);
      }
    }
    $this->credentials = [
      'endpoint_url' => $settings->get('endpoint_url'),
      'api_key' => $settings->get('api_key'),
      'org_guid' => $settings->get('org_guid'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function call(array $params = []) {
    $this->setCredentials();
    // Add credentials to query.
    $params['api_key'] = $this->credentials['api_key'];
    $params['organization.organizationGuid'] = $this->credentials['org_guid'];
    // Add sort parameters to ensure results are always in the same order.
    $params['sort'] = 'date_asc';
    $url = $this->credentials['endpoint_url'] . '?' . UrlHelper::buildQuery($params);
    try {
      $response = $this->client->request('GET', $url);
      return json_decode($response->getBody(), TRUE);
    }
    catch (\Exception $e) {
      $message = $e->getMessage();
      $msg = "Failed to call Activenet with message: $message";
      $this->logger->error("$msg for the endpoint $url");
      throw new \Exception($msg);
    }
  }

}
