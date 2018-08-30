<?php

namespace Drupal\ygs_salesforce_mc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use ET_Client;

/**
 * Provides ygs_salesforce_mc settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_salesforce_mc_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ygs_salesforce_mc.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ygs_salesforce_mc.settings');

    $form['clientid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#required' => TRUE,
      '#default_value' => $config->get('clientid'),
    ];
    $form['clientsecret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('clientsecret'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    module_load_include('php', 'ygs_salesforce_mc', 'includes/FuelSDK-PHP/ET_Client');
    try {
      $client = new ET_Client(TRUE, FALSE, [
        'appsignature' => 'none',
        'clientid' => $form_state->getValue('clientid'),
        'clientsecret' => $form_state->getValue('clientsecret'),
        'defaultwsdl' => 'https://webservice.exacttarget.com/etframework.wsdl',
        'xmlloc' => drupal_get_path('module', 'ygs_salesforce_mc') . '/includes/FuelSDK-PHP/wsdl/ExactTargetWSDL.xml',
      ]);
    }
    catch (\Exception $e) {
      $message = $e->getMessage();
      $form_state->setErrorByName('clientid', $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ygs_salesforce_mc.settings');
    $config->set('clientid', $form_state->getValue('clientid'));
    $config->set('clientsecret', $form_state->getValue('clientsecret'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
