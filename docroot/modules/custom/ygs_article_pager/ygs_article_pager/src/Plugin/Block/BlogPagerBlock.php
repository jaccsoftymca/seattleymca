<?php

namespace Drupal\ygs_article_pager\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'BlogPagerBlock' block.
 *
 * @Block(
 *  id = "blog_pager_block",
 *  admin_label = @Translation("Blog pager block"),
 *  context = {
 *    "node" = @ContextDefinition(
 *      "entity:node",
 *      label = @Translation("Current Node")
 *    )
 *  }
 * )
 */
class BlogPagerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    try {
      $node = $this->getContextValue('node');
    }
    catch (Exception $e) {
      return [];
    }

    if ($node->getType() != 'blog') {
      return [];
    }

    $items = $this->loadBlogPagerItems($node);

    $build = [
      '#theme' => 'blog_pager_items',
      '#next' => $items['next'],
      '#previous' => $items['previous'],
    ];

    return $build;
  }

  /**
   * Generates links to next and previous blog posts (if available).
   *
   * @param Node $node
   *   The current node to use as a reference for pager item determination.
   *
   * @return array
   *   Keyed array
   */
  public function loadBlogPagerItems(Node $node) {
    $items = array();
    $id = $node->id();

    $next_item_query = \Drupal::entityQuery('node');
    $next_item_query->condition('type', 'blog')
      ->condition('nid', $id, '>')
      ->sort('nid', 'asc')
      ->range(0, 1);
    $next_item = $next_item_query->execute();

    if (!empty($next_item)) {
      $next_item = array_pop($next_item);
      $items['next'] = Node::load($next_item)->link();
    }
    else {
      $items['next'] = NULL;
    }

    $previous_item_query = \Drupal::entityQuery('node');
    $previous_item_query->condition('type', 'blog')
      ->condition('nid', $id, '<')
      ->sort('nid', 'desc')
      ->range(0, 1);
    $previous_item = $previous_item_query->execute();

    if (!empty($previous_item)) {
      $previous_item = array_pop($previous_item);
      $items['previous'] = Node::load($previous_item)->link();
    }
    else {
      $items['previous'] = NULL;
    }

    return $items;
  }

}
