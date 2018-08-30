<?php

namespace Drupal\activenet_sync;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ProxyClass\Lock\DatabaseLockBackend;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * ActivenetSyncActivenetPusher class.
 */
class ActivenetSyncActivenetPusher implements ActivenetSyncPusherInterface {

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
   * Sync Cache storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * ActivenetSync Repository.
   *
   * @var \Drupal\activenet_sync\ActivenetSyncRepository
   */
  protected $repository;

  /**
   * Queue Factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Created classes/sessions.
   *
   * @var array
   */
  protected $referencedEntities;

  /**
   * Database Lock.
   *
   * @var DatabaseLockBackend
   */
  protected $lock;

  /**
   * ActivenetSyncActivenetPusher constructor.
   *
   * @param ActivenetSyncActivenetWrapper $wrapper
   *   Wrapper.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Drupal Logger.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param ActivenetSyncRepository $repository
   *   Class and Session Entity Repository.
   * @param QueueFactory $queue
   *   Queue Factory.
   * @param DatabaseLockBackend $lock
   *   Lock.
   */
  public function __construct(
    ActivenetSyncActivenetWrapper $wrapper,
    ConfigFactory $config,
    LoggerChannelInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    ActivenetSyncRepository $repository,
    QueueFactory $queue,
    DatabaseLockBackend $lock) {

    $this->wrapper = $wrapper;
    $this->config = $config;
    $this->logger = $logger;
    $this->storage = $entity_type_manager->getStorage('sync_cache');
    $this->repository = $repository;
    $this->queue = $queue;
    $this->lock = $lock;
    $this->referencedEntities = ['classes' => [], 'sessions' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function push() {
    $proxy_data = $this->wrapper->getProxyData();
    foreach ($proxy_data as $cache_id => $active_net_item) {
      $class = $this->repository->getNodeByProperty([
        'type' => 'class',
        'title' => $active_net_item->getTitle(),
        'field_type' => 'activity',
      ]);
      $session = $this->repository->getNodeByExternalId($active_net_item->get('assetGuid')->getValue(), 'session');
      $class_id = $active_net_item->generateClass($class);
      $session_id = $active_net_item->generateSession($class_id, $session);
      // Update sync_cache status.
      $sync_cache = $this->storage->load($cache_id);
      $sync_cache->set('class', $class_id);
      $sync_cache->set('session', $session_id);
      $sync_cache->set('field_sync_errors', $active_net_item->getValidationErrors(TRUE));
      $sync_cache->set('status', $active_net_item->getValidationStatus());
      $sync_cache->save();

      $this->referencedEntities['classes'][] = $class_id;
      $this->referencedEntities['sessions'][] = $session_id;
    }
    $this->addReferencedEntitiesToQueue();
    // Delete activenet_sync lock.
    $this->lock->release('activenet_sync');
  }

  /**
   * Add created entities to activenet_sync_proceed_imported_nodes queue.
   */
  public function addReferencedEntitiesToQueue() {
    $queue = $this->queue->get('activenet_sync_proceed_imported_nodes');
    foreach (array_unique($this->referencedEntities['classes']) as $class_id) {
      $queue->createItem([
        'nid' => $class_id,
        'type' => 'class',
      ]);
    }
    foreach (array_unique($this->referencedEntities['sessions']) as $session_id) {
      $queue->createItem([
        'nid' => $session_id,
        'type' => 'session',
      ]);
    }
  }

}
