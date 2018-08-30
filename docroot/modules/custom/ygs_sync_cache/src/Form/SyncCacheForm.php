<?php

namespace Drupal\ygs_sync_cache\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Sync Cache edit forms.
 *
 * @ingroup ygs_sync_cache
 */
class SyncCacheForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\ygs_sync_cache\Entity\SyncCache */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Sync Cache.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Sync Cache.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.sync_cache.canonical', ['sync_cache' => $entity->id()]);
  }

}
