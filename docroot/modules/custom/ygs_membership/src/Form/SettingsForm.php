<?php

namespace Drupal\ygs_membership\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Settings Form for ygs_membership.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_membership_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ygs_membership.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ygs_membership.settings');

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => ($config->get('title')) ? $config->get('title') : '',
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => ($config->get('description')) ? $config->get('description') : '',
    ];
    $form['tab1_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tab1 Title'),
      '#default_value' => ($config->get('tab1_title')) ? $config->get('tab1_title') : '',
    ];
    $form['tab2_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tab2 Title'),
      '#default_value' => ($config->get('tab2_title')) ? $config->get('tab2_title') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('ygs_membership.settings');

    // Set configuration.
    $config->set('description', $form_state->getValue('description'))->save();
    $config->set('title', $form_state->getValue('title'))->save();
    $config->set('tab1_title', $form_state->getValue('tab1_title'))->save();
    $config->set('tab2_title', $form_state->getValue('tab2_title'))->save();
    Cache::invalidateTags(array('config:core.entity_view_display.node.membership.teaser'));

    parent::submitForm($form, $form_state);
  }

}
