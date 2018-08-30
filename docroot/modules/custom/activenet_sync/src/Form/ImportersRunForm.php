<?php

namespace Drupal\activenet_sync\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Importers Run form.
 */
class ImportersRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activenet_sync_importer_run';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['syncer'] = [
      '#type' => 'select',
      '#title' => $this->t('Syncer'),
      '#options' => [
        'activenet_sync.activenet.syncer' => t('ActiveNet'),
        'activenet_sync.flexreg.syncer' => t('FlexReg'),
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run importer'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ymca_sync_run($form_state->getValue('syncer'), 'proceed');
  }

}
