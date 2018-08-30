<?php

namespace Drupal\ygs_sync_cache\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Sync Cache entities.
 */
class SyncCacheViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['sync_cache']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Sync Cache'),
      'help' => $this->t('The Sync Cache ID.'),
    );

    return $data;
  }

}
