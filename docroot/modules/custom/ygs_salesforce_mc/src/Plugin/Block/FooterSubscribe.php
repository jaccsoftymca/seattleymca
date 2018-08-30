<?php

namespace Drupal\ygs_salesforce_mc\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Block with footer subscribe form .
 *
 * @Block(
 *   id = "footer_subscribe_block",
 *   admin_label = @Translation("YGS Footer subscribe form"),
 *   category = @Translation("Custom")
 * )
 */
class FooterSubscribe extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $block = \Drupal::formBuilder()->getForm('Drupal\ygs_salesforce_mc\Form\FooterSubscribeForm');
    return $block;
  }

}
