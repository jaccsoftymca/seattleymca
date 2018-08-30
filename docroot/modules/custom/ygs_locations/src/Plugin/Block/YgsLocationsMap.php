<?php

namespace Drupal\ygs_locations\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with locations map.
 *
 * @Block(
 *   id = "ygs_locations_map",
 *   admin_label = @Translation("YGS Locations map block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class YgsLocationsMap extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ygs_locations_map',
      '#attached' => [
        'library' => [
          'ygs_locations/ygs_locations_map',
        ],
      ],
      '#cache' => [
        'tags' => ['taxonomy_term_list'],
      ],
    ];
  }

}
