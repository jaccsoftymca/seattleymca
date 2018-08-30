<?php

/**
 * @file
 * YMCA Sync drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function ymca_sync_drush_command() {
  $items = [];
  $items['enable-syncers'] = [
    'description' => 'Enable syncers.',
    'examples' => [
      'drush enable-syncers' => dt('Enable syncers.'),
    ],
    'core' => ['8+'],
  ];
  $items['disable-syncers'] = [
    'description' => 'Disabled syncers.',
    'examples' => [
      'drush disable-syncers' => dt('Disable syncers.'),
    ],
    'core' => ['8+'],
  ];
  return $items;
}

/**
 * Enable all syncers.
 */
function drush_ymca_sync_enable_syncers() {
  $syncers = ['activenet_sync.activenet.syncer', 'activenet_sync.flexreg.syncer'];
  \Drupal::configFactory()
    ->getEditable('ymca_sync.settings')
    ->set('active_syncers', $syncers)
    ->save(TRUE);
  drush_log(dt('All syncers have been enabled.'), 'ok');
}

/**
 * Disable all syncers.
 */
function drush_ymca_sync_disable_syncers() {
  \Drupal::configFactory()
    ->getEditable('ymca_sync.settings')
    ->set('active_syncers', [])
    ->save(TRUE);
  drush_log(dt('All syncers have been disabled.'), 'ok');
}
