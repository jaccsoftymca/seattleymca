<?php

namespace Drupal\ygs_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Form' block.
 *
 * @Block(
 *   id = "branch_sessions_search_block",
 *   admin_label = @Translation("Branch Sessions Search Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class BranchSessionsSearchBlock extends BlockBase {

  /**
   * Branch Sessions Form class name.
   *
   * @var string
   */
  protected $formName = '\Drupal\ygs_branch\Form\BranchSessionsForm';

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = \Drupal::request()->query->all();
    $form = \Drupal::formBuilder()->getForm($this->formName, $query);
    $render = \Drupal::service('renderer')->render($form, FALSE);
    return [
      '#markup' => $render,
      '#attached' => [
        'library' => [
          'slick/slick',
          'slick/slick.theme',
          'slick/slick.arrow.down',
        ]
      ]
    ];
  }

}
