<?php

namespace Drupal\activenet_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides activenet_sync settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activenet_sync_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'activenet_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('activenet_sync.settings');
    $form['activenet_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Activenet page'),
      '#default_value' => ($config->get('activenet_page')) ? $config->get('activenet_page') : '',
    ];
    $form['dw_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DataWarehouse query offset'),
      '#default_value' => ($config->get('dw_offset')) ? $config->get('dw_offset') : '',
    ];
    $form['is_production'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is production'),
      '#default_value' => ($config->get('is_production')) ? $config->get('is_production') : '',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('activenet_sync.settings');
    $config->set('activenet_page', $form_state->getValue('activenet_page'))->save();
    $config->set('dw_offset', $form_state->getValue('dw_offset'))->save();
    $config->set('is_production', $form_state->getValue('is_production'))->save();
    parent::submitForm($form, $form_state);
  }

}
