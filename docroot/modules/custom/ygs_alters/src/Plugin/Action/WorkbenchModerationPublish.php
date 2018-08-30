<?php

namespace Drupal\ygs_alters\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Publishes a node.
 *
 * @Action(
 *   id = "workbench_moderation_node_publish_action",
 *   label = @Translation("Move to Publish though Workbench Moderation workflow"),
 *   type = "node"
 * )
 */
class WorkbenchModerationPublish extends ActionBase {

  /**
   * Checks object access.
   *
   * @param mixed $object
   *   The object to execute the action on.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $account->hasPermission("use published_published transition");
  }

  /**
   * Executes the plugin.
   */
  public function execute($entity = NULL) {
    $entity->moderation_state->target_id = 'published';
    $entity->save();
  }

}
