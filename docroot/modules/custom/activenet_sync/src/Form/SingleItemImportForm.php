<?php

namespace Drupal\activenet_sync\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides Importers Run form.
 */
class SingleItemImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activenet_sync_single_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['syncer'] = [
      '#type' => 'select',
      '#title' => $this->t('Activity type'),
      '#options' => [
        'activenet' => t('ActiveNet'),
        'flexreg' => t('FlexReg'),
      ],
    ];
    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Import type'),
      '#options' => [
        'json' => t('JSON'),
        'id' => t('ID'),
      ],
      '#default_value' => 'json',
    ];
    $form['json_data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON data'),
      '#description' => t('Click "Load example json" to get real data mockup.'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'json'],
        ],
      ],
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => t('For activenet use assetGuid, for flexreg use DCPROGRAMSESSION_ID'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'id'],
        ],
      ],
    ];
    $form['load_json'] = [
      '#type' => 'submit',
      '#value' => t('Load example json'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'json'],
        ],
      ],
      '#ajax' => array(
        'callback' => 'Drupal\activenet_sync\Form\SingleItemImportForm::loadJson',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Loading json'),
        ),
      ),

    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run importer'),
    ];
    return $form;
  }

  /**
   * Load mockup to json_data textarea.
   */
  public function loadJson(array &$form, FormStateInterface $form_state) {
    $syncer = $form_state->getValue('syncer');
    $mockup_json = file_get_contents(drupal_get_path('module', 'activenet_sync') . '/mockups/' . $syncer . '_mockup.txt');
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new InvokeCommand('#edit-json-data', 'val', [$mockup_json]));
    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#ajax'])) {
      return;
    }

    $type = $form_state->getValue('type');
    $syncer = $form_state->getValue('syncer');

    if ($type == 'json' && !empty($form_state->getValue('json_data'))) {
      $data = ['type' => 'json', 'data' => $form_state->getValue('json_data')];
    }
    elseif ($type == 'id' && !empty($form_state->getValue('id'))) {
      $data = ['type' => 'id', 'data' => $form_state->getValue('id')];
    }
    else {
      drupal_set_message(t('Fill in the empty fields!'), 'error');
      return;
    }

    if ($syncer == 'activenet') {
      // Run activenet syncer.
      $syncer = \Drupal::service('activenet_sync.activenet.syncer');
      $syncer->modifyStep(0, 'args', $data);
      $syncer->proceed();
    }
    else {
      // Run flexreg syncer.
      $syncer = \Drupal::service('activenet_sync.flexreg.syncer');
      $syncer->modifyStep(0, 'args', $data);
      $syncer->proceed();
    }
    $url = Url::fromRoute('entity.sync_cache.collection');
    $link = \Drupal::service('link_generator')->generate(t('this page'), $url);
    drupal_set_message(t('Please visit %link and check if exist imported entity.', ['%link' => $link]));
  }

}
