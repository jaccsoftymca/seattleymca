<?php

namespace Drupal\activenet_sync;

/**
 * ActivenetSyncFlexregWrapper class.
 */
class ActivenetSyncFlexregWrapper implements ActivenetSyncWrapperInterface {

  /**
   * Source data fetched from DW.
   *
   * @var array
   */
  private $sourceData = [];

  /**
   * Data fetched and saved to Drupal database.
   *
   * @var array
   */
  private $proxyData = [];

  /**
   * {@inheritdoc}
   */
  public function getSourceData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceData(array $data) {
    $this->sourceData = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getProxyData() {
    return $this->proxyData;
  }

  /**
   * {@inheritdoc}
   */
  public function setProxyData(array $data) {
    $this->proxyData = $data;
  }

}
