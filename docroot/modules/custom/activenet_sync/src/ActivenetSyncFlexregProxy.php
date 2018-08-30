<?php

namespace Drupal\activenet_sync;

use Drupal\activenet_sync\TypedData\Definition\FlexregDwDataDefinition;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\ygs_sync_cache\Entity\SyncCache;

/**
 * ActivenetSyncFlexregProxy class.
 */
class ActivenetSyncFlexregProxy implements ActivenetSyncProxyInterface {

  /**
   * ActivenetSyncFlexregWrapper definition.
   *
   * @var ActivenetSyncFlexregWrapper
   */
  protected $wrapper;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $config;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The typed data manager.
   *
   * @var TypedDataManager
   */
  protected $typedData;

  /**
   * The processed data.
   *
   * @var array
   */
  protected $proxyData = array();

  /**
   * Flexreg data definition.
   *
   * @var FlexregDwDataDefinition
   */
  protected $definition;

  /**
   * ActivenetSync Repository.
   *
   * @var \Drupal\activenet_sync\ActivenetSyncRepository
   */
  protected $repository;

  /**
   * ActivenetSyncFlexregProxy constructor.
   *
   * @param ActivenetSyncFlexregWrapper $wrapper
   *   Wrapper.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Drupal Logger.
   * @param TypedDataManager $typed_data
   *   The typed data manager.
   * @param ActivenetSyncRepository $repository
   *   Class and Session Entity Repository.
   */
  public function __construct(
    ActivenetSyncFlexregWrapper $wrapper,
    ConfigFactory $config,
    LoggerChannelInterface $logger,
    TypedDataManager $typed_data,
    ActivenetSyncRepository $repository) {

    $this->wrapper = $wrapper;
    $this->config = $config;
    $this->logger = $logger;
    $this->typedData = $typed_data;
    $this->definition = FlexregDwDataDefinition::create('flexreg_dw_data');
    $this->repository = $repository;
  }

  /**
   * {@inheritdoc}
   */
  public function saveEntities() {
    $sourceData_chunks = array_chunk($this->wrapper->getSourceData(), 10, TRUE);
    foreach ($sourceData_chunks as $chunk) {
      foreach ($chunk as $item) {
        $this->createCache($item);
      }
    }

    $this->wrapper->setProxyData($this->proxyData);
  }

  /**
   * Create SyncCache entity.
   *
   * @param array $item
   *   Raw data from ActiveNet.
   */
  public function createCache(array $item) {
    // Create and validate Typed Data.
    $item_data = $this->typedData->create($this->definition);
    $item_data->setValue($item);
    $item_data->validate();
    $activities_id = json_encode($item_data->getActivitiesId(TRUE));
    $cache = $this->repository->checkExistingCache($item_data->get('ps_id')->getValue());
    $item_json_data = json_encode($item);
    $hash = Crypt::hashBase64($item_json_data . $activities_id);
    if ($cache) {
      $in_terminal_status = in_array($cache->status->value, ['ok', 'detached']);
      if ($cache->field_raw_data_hash->value == $hash && $in_terminal_status) {
        // Skip if asset not changed.
        return;
      }
      else {
        // Set 'pending_update' status if asset changed.
        $cache->set('status', 'pending_update');
        $cache->set('field_raw_data_hash', $hash);
      }
    }
    elseif (!$cache) {
      // Create cache.
      $cache = SyncCache::create([
        'user_id' => 1,
        'type' => 'flexreg',
        'field_external_id' => $item_data->get('ps_id')->getValue(),
        'field_raw_data_hash' => $hash,
      ]);
    }
    $cache->set('title', $item_data->get('program_name')->getValue());
    $cache->set('status', 'pending_import');
    $cache->set('raw_data', $item_json_data);
    $cache->set('field_sync_errors', $item_data->getValidationErrors(TRUE));

    $cache->save();
    $this->addItemToProxyData($cache->id(), $item_data);
  }

  /**
   * Add item to proxyData array.
   *
   * @param int $cache_id
   *   SyncCache entity ID for item.
   * @param object $item_data
   *   Validated TypedData.
   */
  public function addItemToProxyData($cache_id, $item_data) {
    $this->proxyData[$cache_id] = $item_data;
  }

}
