<?php

namespace Drupal\ygs_salesforce_mc;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelInterface;
use ET_Client;
use ET_List;
use ET_Post;
use ET_Info;
use ET_ProfileAttribute;

/**
 * SalesForce Mc Service Manager.
 */
class SalesForceMcClient {

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
   * @var \ET_Client
   */
  protected $client;

  /**
   * FileSystem service.
   *
   * @var FileSystem
   */
  protected $fileSystem;

  /**
   * SalesForce Mc Service constructor.
   *
   * @param ConfigFactoryInterface $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Logger factory.
   * @param FileSystem $file_system
   *   FileSystem factory.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelInterface $logger, FileSystem $file_system) {
    $this->config = $config;
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->setClient();
  }

  /**
   * Get ET_Client object.
   */
  public function setClient() {
    $settings = $this->config->get('ygs_salesforce_mc.settings');
    module_load_include('php', 'ygs_salesforce_mc', 'includes/FuelSDK-PHP/ET_Client');
    try {
      $client = new ET_Client(TRUE, FALSE, [
        'appsignature' => 'none',
        'clientid' => $settings->get('clientid'),
        'clientsecret' => $settings->get('clientsecret'),
        'defaultwsdl' => 'https://webservice.exacttarget.com/etframework.wsdl',
        'xmlloc' => $this->fileSystem->realpath(file_default_scheme() . '://ExactTargetWSDL.xml'),
      ]);
      $this->client = $client;
    }
    catch (\Exception $e) {
      $message = $e->getMessage();
      $this->logger->error($message);
      return;
    }
  }

  /**
   * Get SalesForce MC Lists.
   *
   * @param bool $named_keys
   *   If TRUE for array key will used ListName.
   *
   * @return array
   *   Lists.
   */
  public function getLists($named_keys = FALSE) {
    $get_list = new ET_List();
    $get_list->authStub = $this->client;
    $get_list->props = array('ID', 'ListName');
    $lists = $get_list->get();
    $result = [];
    if (!$lists->status && empty($lists->results)) {
      $this->logger->error('Lists not found!');
      return $result;
    }
    else {
      foreach ($lists->results as $list) {
        if ($named_keys) {
          $result[$list->ListName] = $list->ListName;
        }
        else {
          $result[$list->ID] = $list->ListName;
        }
      }
      return $result;
    }
  }

  /**
   * Get list ID by list name.
   *
   * @param string $list_name
   *   List name.
   *
   * @return int
   *   List ID.
   */
  public function getListId($list_name) {
    return array_search($list_name, $this->getLists());
  }

  /**
   * Add Subscriber To List.
   *
   * @param array $props
   *   Parameters.
   * @param string $list_name
   *   List name.
   *
   * @return \ET_Post
   *   Response from SalesForce MC.
   */
  public function addSubscriberToList(array $props, $list_name) {
    $props['Lists'] = ['ID' => $this->getListId($list_name)];
    $response = new ET_Post($this->client, 'Subscriber', $props);
    return $response;
  }

  /**
   * Add Subscriber To List.
   *
   * @param array $props
   *   Parameters.
   * @param string $triggered_send_definition
   *   Definition name.
   *
   * @return \ET_Post
   *   Response from SalesForce MC.
   */
  public function triggeredSend(array $props, $triggered_send_definition) {
    $triggered_props = [
      'TriggeredSendDefinition' => ['CustomerKey' => $triggered_send_definition],
      'Subscribers' => [$props],
    ];
    $triggered_response = new ET_Post($this->client, 'TriggeredSend', $triggered_props);
    return $triggered_response;
  }

  /**
   * Get SalesForce MC Lists.
   *
   * @return array
   *   Subscriber attributes and properties.
   */
  public function getProperties() {
    $attributes = new ET_ProfileAttribute();
    $attributes->authStub = $this->client;
    $props = $attributes->get();
    $result = ['attributes' => [], 'properties' => []];
    if (is_array($props->results)) {
      foreach ($props->results as $attribute) {
        $result['attributes'][] = $attribute->Name;
      }
    }

    $properties = new ET_Info($this->client, 'Subscriber', FALSE);
    if (is_array($properties->results)) {
      foreach ($properties->results as $property) {
        $result['properties'][] = $property->Name;
      }
    }
    return $result;
  }

}
