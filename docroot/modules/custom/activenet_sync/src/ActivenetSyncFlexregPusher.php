<?php

namespace Drupal\activenet_sync;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\ProxyClass\Lock\DatabaseLockBackend;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * ActivenetSyncFlexregPusher class.
 */
class ActivenetSyncFlexregPusher implements ActivenetSyncPusherInterface {

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
   * Database Lock.
   *
   * @var DatabaseLockBackend
   */
  protected $lock;

  /**
   * Created classes/sessions.
   *
   * @var array
   */
  protected $referencedEntities;

  /**
   * ActivenetSyncFlexregPusher constructor.
   *
   * @param ActivenetSyncFlexregWrapper $wrapper
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
    ActivenetSyncFlexregWrapper $wrapper,
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
    foreach ($proxy_data as $cache_id => $dw_item) {
      $class = $this->repository->getNodeByProperty([
        'type' => 'class',
        'title' => $dw_item->get('program_name')->getValue(),
        'field_type' => 'flexreg',
      ]);
      $session = $this->repository->getNodeByExternalId($dw_item->getSessionExternalId(), 'session');
      $class_id = $dw_item->generateClass($class);
      $session_id = $dw_item->generateSession($class_id, $session);
      // Update sync_cache status.
      $sync_cache = $this->storage->load($cache_id);
      $sync_cache->set('class', $class_id);
      $sync_cache->set('session', $session_id);
      $sync_cache->set('field_sync_errors', $dw_item->getValidationErrors(TRUE));
      $sync_cache->set('status', $dw_item->getValidationStatus());
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
