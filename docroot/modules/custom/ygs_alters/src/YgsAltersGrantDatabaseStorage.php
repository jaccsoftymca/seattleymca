<?php

namespace Drupal\ygs_alters;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeGrantDatabaseStorage;

/**
 * Fix for cron errors related to content moderation.
 *
 * @ingroup node_access
 */
class YgsAltersGrantDatabaseStorage extends NodeGrantDatabaseStorage {

  /**
   * {@inheritdoc}
   */
  public function alterQuery($query, array $tables, $op, AccountInterface $account, $base_table) {
    if (!$langcode = $query->getMetaData('langcode')) {
      $langcode = FALSE;
    }

    // Find all instances of the base table being joined -- could appear
    // more than once in the query, and could be aliased. Join each one to
    // the node_access table.
    $grants = node_access_grants($op, $account);
    foreach ($tables as $nalias => $tableinfo) {
      $table = $tableinfo['table'];
      if (!($table instanceof SelectInterface) && $table == $base_table) {
        // Set the subquery.
        $subquery = $this->database->select('node_access', 'na')
          ->fields('na', array('nid'));

        // If any grant exists for the specified user, then user has access to the
        // node for the specified operation.
        $grant_conditions = static::buildGrantsQueryCondition($grants);

        // Attach conditions to the subquery for nodes.
        if (count($grant_conditions->conditions())) {
          $subquery->condition($grant_conditions);
        }
        $subquery->condition('na.grant_' . $op, 1, '>=');

        // Add langcode-based filtering if this is a multilingual site.
        if (\Drupal::languageManager()->isMultilingual()) {
          // If no specific langcode to check for is given, use the grant entry
          // which is set as a fallback.
          // If a specific langcode is given, use the grant entry for it.
          if ($langcode === FALSE) {
            $subquery->condition('na.fallback', 1, '=');
          }
          else {
            $subquery->condition('na.langcode', $langcode, '=');
          }
        }

        // Fix for cron errors related to content moderation.
        switch ($tableinfo['table']) {
          case 'node':
          case 'node_revision':
          case 'node_field_data':
          case 'node_field_revision':
            $field = 'nid';
            break;

          default:
            $field = 'entity_id';
            break;
        }

        // Now handle entities.
        $subquery->where("$nalias.$field = na.nid");

        $query->exists($subquery);
      }
    }
  }

}
