<?php

namespace Drupal\activenet_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides activenet_client settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activenet_client_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'activenet_client.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('activenet_client.settings');

    $form['endpoint_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint URL'),
      '#required' => TRUE,
      '#default_value' => ($config->get('endpoint_url')) ? $config->get('endpoint_url') : '',
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#required' => TRUE,
      '#default_value' => ($config->get('api_key')) ? $config->get('api_key') : '',
    ];
    $form['org_guid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization Guid'),
      '#required' => TRUE,
      '#default_value' => ($config->get('org_guid')) ? $config->get('org_guid') : '',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('activenet_client.settings');
    $config->set('endpoint_url', $form_state->getValue('endpoint_url'))->save();
    $config->set('api_key', $form_state->getValue('api_key'))->save();
    $config->set('org_guid', $form_state->getValue('org_guid'))->save();
    parent::submitForm($form, $form_state);
  }

}
