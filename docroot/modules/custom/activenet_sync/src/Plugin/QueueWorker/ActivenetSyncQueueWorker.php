<?php

namespace Drupal\activenet_sync\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Sessions/class nodes additional calculation.
 *
 * @QueueWorker(
 *   id = "activenet_sync_queue_worker",
 *   title = @Translation("Activenet Sync Queue Worker"),
 * )
 */
class ActivenetSyncQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (isset($data['nid']) && isset($data['type'])) {
      $nodes = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple([$data['nid']]);
      if (empty($nodes)) {
        return;
      }
      $node = reset($nodes);
      if ($data['type'] == 'session') {
        // Recreate Session Instances.
        // @see ygs_session_instance/src/SessionInstanceManager.php.
        \Drupal::service('session_instance.manager')->recreateSessionInstances($node);
        // Update ages in class related to session.
        _ygs_alters_class_ages_update($node);
      }
      elseif ($data['type'] == 'class') {
        // Actualize session instances.
        ygs_session_instance_actualize_sessions($node, 'class');
      }
    }
  }

}
