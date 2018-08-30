<?php

namespace Drupal\ygs_test_content\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;

/**
 * Create YGS test content.
 */
class YgsTestContentCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_test_content_create';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Create test content'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'ygs_test_content', 'includes/test_content');

    $branches = ygs_test_content_branch();
    foreach ($branches as $values) {
      $node = Node::create($values);
      $node->save();
    }

    $facilities = ygs_test_content_facility();
    foreach ($facilities as $values) {
      $node = Node::create($values);
      $node->save();
    }

    drupal_set_message($this->t('Test content has been created.'));
  }

}
