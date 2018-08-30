<?php

namespace Drupal\ygs_class_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "class_page_schedule_block",
 *   admin_label = @Translation("YGS Class page Schedule block"),
 *   category = @Translation("YGS Class page Blocks")
 * )
 */
class ClassPageSchedule extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $class_id = 0;
    $type = 'activity';

    // Extract class node id and type.
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      $class_id = $node->id();
      if (!empty($node->field_type) && $type_items = $node->field_type->getValue()) {
        $type = reset($type_items)['value'];
      }
      else {
        return [];
      }
    }

    return [
      '#theme' => 'class_page_schedule',
      '#attached' => [
        'library' => [
          'ygs_class_page/app',
        ],
        'drupalSettings' => [
          'ygs_class_page' => [
            'class' => [
              'class_id' => $class_id,
              'type' => $type,
            ],
          ],
        ],
      ],
    ];
  }

}
