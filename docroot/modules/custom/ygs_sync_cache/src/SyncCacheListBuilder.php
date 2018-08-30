<?php

namespace Drupal\ygs_sync_cache;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Sync Cache entities.
 *
 * @ingroup ygs_sync_cache
 */
class SyncCacheListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Sync Cache ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ygs_sync_cache\Entity\SyncCache */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.sync_cache.edit_form', array(
          'sync_cache' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
