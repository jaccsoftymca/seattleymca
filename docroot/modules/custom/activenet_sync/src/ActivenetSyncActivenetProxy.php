<?php

namespace Drupal\activenet_sync;

use Drupal\Component\Utility\Crypt;
use Drupal\activenet_sync\TypedData\Definition\ActiveNetDefinition;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Symfony\Component\Serializer\Serializer;
use Drupal\ygs_sync_cache\Entity\SyncCache;

/**
 * ActivenetSyncActivenetProxy class.
 */
class ActivenetSyncActivenetProxy implements ActivenetSyncProxyInterface {

  /**
   * ActivenetSyncActivenetWrapper definition.
   *
   * @var ActivenetSyncActivenetWrapper
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
   * The serializer object.
   *
   * @var Serializer
   */
  protected $serializer;

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
   * ActiveNet data definition..
   *
   * @var ActiveNetDefinition
   */
  protected $definition;

  /**
   * ActivenetSync Repository.
   *
   * @var \Drupal\activenet_sync\ActivenetSyncRepository
   */
  protected $repository;

  /**
   * ActivenetSyncActivenetProxy constructor.
   *
   * @param ActivenetSyncActivenetWrapper $wrapper
   *   Wrapper.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Drupal Logger.
   * @param Serializer $serializer
   *   Symphony Serializer.
   * @param TypedDataManager $typed_data
   *   The typed data manager.
   * @param ActivenetSyncRepository $repository
   *   Class and Session Entity Repository.
   */
  public function __construct(
    ActivenetSyncActivenetWrapper $wrapper,
    ConfigFactory $config,
    LoggerChannelInterface $logger,
    Serializer $serializer,
    TypedDataManager $typed_data,
    ActivenetSyncRepository $repository) {

    $this->wrapper = $wrapper;
    $this->config = $config;
    $this->logger = $logger;
    $this->serializer = $serializer;
    $this->typedData = $typed_data;
    $this->definition = ActiveNetDefinition::create('active_net_data');
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
    $cache = $this->repository->checkExistingCache($item_data->get('assetGuid')->getValue());
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
        'type' => 'activenet',
        'field_raw_data_hash' => $hash,
        'status' => 'pending_import',
      ]);
    }
    $cache->set('title', $item_data->getTitle());
    $cache->set('raw_data', $this->serializer->serialize($item, 'json'));
    $cache->set('field_sync_errors', $item_data->getValidationErrors(TRUE));
    $cache->set('field_external_id', $item_data->get('assetGuid')->getValue());
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
