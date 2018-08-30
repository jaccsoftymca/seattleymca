<?php

namespace Drupal\ygs_sync_cache;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Sync Cache entity.
 *
 * @see \Drupal\ygs_sync_cache\Entity\SyncCache.
 */
class SyncCacheAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ygs_sync_cache\Entity\SyncCacheInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished sync cache entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published sync cache entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit sync cache entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete sync cache entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add sync cache entities');
  }

}
