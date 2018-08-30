<?php

/**
 * @file
 * Provides views data for ygs_alters module.
 */

/**
 * Implements hook_views_data_alter().
 */
function ygs_alters_views_data_alter(&$data) {
  // Add media_taxonomy_index_tid_depth support to media views.
  $data['media_field_data']['term_media_tid_depth'] = array(
    'help' => t('Display content if it has the selected taxonomy terms, or children of the selected terms. Due to additional complexity, this has fewer options than the versions without depth.'),
    'real field' => 'mid',
    'argument' => array(
      'title' => t('Has taxonomy term ID (with depth)'),
      'id' => 'media_taxonomy_index_tid_depth',
      'accept depth modifier' => TRUE,
    ),
    'filter' => array(
      'title' => t('Has taxonomy terms (with depth)'),
      'id' => 'media_taxonomy_index_tid_depth',
    ),
  );
}
