<?php

namespace Drupal\ygs_sync_cache;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ygs_sync_cache\Entity\SyncCache;

/**
 * Sync Cache Manager.
 */
class SyncCacheManager implements SyncCacheManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'ygs_sync_cache';
  /**
   * Collection name.
   */
  const STORAGE = 'ygs_sync_cache';
  /**
   * QueryFactory definition.
   *
   * @var QueryFactory
   */
  protected $entityQuery;
  /**
   * EntityTypeManager definition.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $result = $this->entityQuery->get('sync_cache')->execute();
    if (empty($result)) {
      return;
    }
    $this->deleteCacheItems($result);
    $this->logger->info('The cache was cleared.');
    \Drupal::moduleHandler()->invokeAll('ygs_sync_cache_reset_cache');
  }

  /**
   * {@inheritdoc}
   */
  public function resetCacheByType($type) {
    $result = $this->entityQuery->get('sync_cache')
      ->condition('type', $type)
      ->execute();
    if (empty($result)) {
      return;
    }
    $this->deleteCacheItems($result);
    $this->logger->info("The cache with type $type was cleared.");
    \Drupal::moduleHandler()->invokeAll('sync_cache_proxy_reset_cache');
  }

  /**
   * {@inheritdoc}
   */
  public function resetCacheByStatus($status = 'pending_delete') {
    $result = $this->entityQuery->get('sync_cache')
      ->condition('status', $status)
      ->execute();
    if (empty($result)) {
      return;
    }
    $this->deleteCacheItems($result);
    $this->logger->info("The cache with status $status was cleared.");
    \Drupal::moduleHandler()->invokeAll('sync_cache_proxy_reset_cache');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCacheItems(array $ids) {
    $storage = $this->entityTypeManager->getStorage('sync_cache');
    $chunks = array_chunk($ids, 10);
    foreach ($chunks as $chunk) {
      $entities = SyncCache::loadMultiple($chunk);
      $storage->delete($entities);
    }
  }

}
