<?php

namespace Drupal\ygs_blog\Plugin\views\filter;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of related content title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("ygs_blog_related_content_titles")
 */
class RelatedContentTitles extends ManyToOne {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Allowed related content titles');
    $this->definition['options callback'] = array($this, 'generateOptions');
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   Return list of options.
   */
  public function generateOptions() {
    $map = [
      'branch' => $this->t('Branches')->render(),
      'camp' => $this->t('Camps')->render(),
    ];

    $storage = \Drupal::entityManager()->getStorage('node');
    $relatedContentIds = \Drupal::entityQuery('node')
      ->condition('type', ['branch', 'camp'], 'IN')
      ->condition('status', 1)
      ->execute();
    $nodes = $storage->loadMultiple($relatedContentIds);
    $res = array();
    /** @var Node $node */
    foreach ($nodes as $nid => $node) {
      // Building an array with nid as key and title as value.
      $res[$map[$node->bundle()]][$nid] = $node->getTitle();
    }
    return $res;
  }

}
