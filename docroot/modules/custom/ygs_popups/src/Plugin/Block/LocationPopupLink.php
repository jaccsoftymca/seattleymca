<?php

namespace Drupal\ygs_popups\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Block with popup link.
 *
 * @Block(
 *   id = "location_popup_link_block",
 *   admin_label = @Translation("YGS location popup link"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class LocationPopupLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'filter' => 'all',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['filter'] = [
      '#type' => 'select',
      '#title' => t('Locations filter'),
      '#options' => array(
        'all' => t('Show all locations'),
        'by_class' => t('Show locations by class'),
      ),
      '#default_value' => $config['filter'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['filter'] = $form_state->getValue('filter');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $nid = 0;
    $type = '';
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($config['filter'] == 'by_class') {
      if ($node && $node->getType() == 'class') {
        $type = 'class';
        $nid = $node->id();
      }
    }
    if ($node && $node->getType() == 'program_subcategory') {
      $type = 'category';
      $nid = $node->id();
    }

    $block = [
      'location_popup_link' => [
        '#lazy_builder' => [
          // @see \Drupal\ygs_popups\PopupLinkGenerator
          'ygs_popups.popup_link_generator:generateLink',
          [$type, $nid],
        ],
        '#create_placeholder' => TRUE,
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args:location',
        ],
      ],
      '#attached' => [
        'library' => [
          'ygs_popups/ygs_popups.autoload',
        ],
      ],
    ];

    return $block;
  }

}
