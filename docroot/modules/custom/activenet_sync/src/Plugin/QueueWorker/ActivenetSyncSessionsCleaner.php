<?php

namespace Drupal\activenet_sync\Plugin\QueueWorker;

use Drupal\activenet_client\ActivenetClient;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\datawarehouse_client\DatawarehouseClient;
use Drupal\ygs_sync_cache\SyncCacheManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deleted from API sessions cleaner.
 *
 * @QueueWorker(
 *   id = "activenet_sync_sessions_cleaner",
 *   title = @Translation("Activenet Sync Sessions Cleaner Worker"),
 * )
 */
class ActivenetSyncSessionsCleaner extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Activenet Client.
   *
   * @var ActivenetClient
   */
  protected $activenetClient;

  /**
   * The Datawarehouse Client.
   *
   * @var DatawarehouseClient
   */
  protected $datawarehouseClient;

  /**
   * Sync Cache Manager.
   *
   * @var SyncCacheManagerInterface
   */
  protected $syncCacheManager;

  /**
   * Entity Type Manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new ActivenetSyncSessionsCleaner object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param LoggerChannelInterface $logger
   *   Drupal Logger.
   * @param ActivenetClient $activenet_client
   *   Activenet Client.
   * @param DatawarehouseClient $dw_client
   *   Datawarehouse Client.
   * @param SyncCacheManagerInterface $sync_cache_manager
   *   Sync Cache Manager.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    LoggerChannelInterface $logger,
    ActivenetClient $activenet_client,
    DatawarehouseClient $dw_client,
    SyncCacheManagerInterface $sync_cache_manager,
    EntityTypeManagerInterface $entity_type_manager) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->activenetClient = $activenet_client;
    $this->datawarehouseClient = $dw_client;
    $this->syncCacheManager = $sync_cache_manager;
    $this->entityTypeManager = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.activenet_sync'),
      $container->get('activenet.client'),
      $container->get('datawarehouse.client'),
      $container->get('sync_cache.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $exist = TRUE;
    $type = $data->type;
    $external_id = $data->field_external_id_value;
    if ($type == 'activenet') {
      $exist = $this->checkItemInActivenet($external_id);
    }
    elseif ($type == 'flexreg') {
      $exist = $this->checkItemInDatawarehouse($external_id);
    }

    if ($exist) {
      return;
    }
    // Delete session.
    $entity = $this->entityTypeManager->load($data->session);
    if ($entity) {
      $entity->delete();
    }
    // Delete sync cache entity.
    $this->syncCacheManager->deleteCacheItems([$data->id]);
    $this->logger->info("The $type cache entity and related session with external ID $external_id was deleted. This ID not exist in API.");
  }

  /**
   * Load item by assetGuid from ActiveNet.
   */
  protected function checkItemInActivenet($asset_guid) {
    $end_date = date("Y-m-d", time() - 60 * 60 * 24) . '..';
    $params = [
      'current_page' => 1,
      'per_page' => 1,
      'assetGuid' => $asset_guid,
      'end_date' => $end_date,
    ];
    $data = $this->activenetClient->call($params);
    if (!empty($data['results'])) {
      $asset = reset($data['results']);
      if (empty($asset['assetComponents']) && $asset['salesStatus'] != 'registration-closed' && strpos($asset['assetLegacyData']['substitutionUrl'], 'dcprogram_id') === FALSE) {
        // Item with this assetGuid exist in ActiveNet and valid for import.
        // @see Drupal\activenet_sync\ActivenetSyncActivenetFetcher::validateAsset().
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Load item by DCPROGRAMSESSION_ID from Datawarehouse.
   */
  protected function checkItemInDatawarehouse($ps_id) {
    $query = "SELECT DCPROGRAMSESSION_ID FROM DCPROGRAMSESSIONS WHERE DCPROGRAMSESSION_ID=$ps_id;";
    $result = $this->datawarehouseClient->call($query);
    if (!empty($result)) {
      // Item with this DCPROGRAMSESSION_ID exist in DCPROGRAMSESSIONS table.
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
