<?php

namespace Drupal\ymca_sync;

/**
 * Class Syncer.
 *
 * @package Drupal\ymca_sync
 */
class Syncer implements SyncerInterface {

  /**
   * Array of steps.
   *
   * @var array
   */
  protected $steps;

  /**
   * {@inheritdoc}
   */
  public function proceed() {
    foreach ($this->steps as $id => $step) {
      $step['plugin']->$step['method']($step['args']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addStep($plugin, $method = 'run', array $args = []) {
    $this->steps[] = ['plugin' => $plugin, 'method' => $method, 'args' => $args];
  }

  /**
   * Modify existing step.
   *
   * @param int $step
   *   Step number.
   * @param string $param
   *   Step param.
   * @param mixed $value
   *   Step param value.
   */
  public function modifyStep($step, $param, $value) {
    $this->steps[$step][$param] = $value;
  }

}
