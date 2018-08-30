<?php

namespace Drupal\datawarehouse_client\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides datawarehouse_client settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'datawarehouse_client_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'datawarehouse_client.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('datawarehouse_client.settings');

    $form['dw_server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Datawarehouse server'),
      '#required' => TRUE,
      '#default_value' => ($config->get('dw_server')) ? $config->get('dw_server') : '',
    ];
    $form['dw_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Datawarehouse port'),
      '#required' => TRUE,
      '#default_value' => ($config->get('dw_port')) ? $config->get('dw_port') : '',
    ];
    $form['dw_db'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Datawarehouse database'),
      '#required' => TRUE,
      '#default_value' => ($config->get('dw_db')) ? $config->get('dw_db') : '',
    ];
    $form['dw_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Datawarehouse user'),
      '#required' => TRUE,
      '#default_value' => ($config->get('dw_user')) ? $config->get('dw_user') : '',
    ];
    $form['dw_pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Datawarehouse pass'),
      '#required' => TRUE,
      '#default_value' => ($config->get('dw_pass')) ? $config->get('dw_pass') : '',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('datawarehouse_client.settings');
    $config->set('dw_server', $form_state->getValue('dw_server'))->save();
    $config->set('dw_pass', $form_state->getValue('dw_pass'))->save();
    $config->set('dw_db', $form_state->getValue('dw_db'))->save();
    $config->set('dw_port', $form_state->getValue('dw_port'))->save();
    $config->set('dw_user', $form_state->getValue('dw_user'))->save();
    parent::submitForm($form, $form_state);
  }

}
