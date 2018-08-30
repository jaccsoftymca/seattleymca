<?php

/**
 * @file
 * Contains sync_cache.page.inc.
 *
 * Page callback for Sync Cache entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Sync Cache templates.
 *
 * Default template: sync_cache.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_sync_cache(array &$variables) {
  // Fetch SyncCache Entity Object.
  $sync_cache = $variables['elements']['#sync_cache'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
