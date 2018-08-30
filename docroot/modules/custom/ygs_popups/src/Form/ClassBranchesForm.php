<?php

namespace Drupal\ygs_popups\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contribute form.
 */
class ClassBranchesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_popups_class_branches_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL, $destination = '') {
    $form['destination'] = array('#type' => 'value', '#value' => $destination);

    $branches_list = $this->getBranchesList($node);
    $default = !empty($branches_list['branch']) ? key($branches_list['branch']) : 0;
    if (!$default) {
      $default = !empty($branches_list['camp']) ? key($branches_list['camp']) : 0;
    }

    $form['select']['branch'] = [
      '#type' => 'radios',
      '#title' => t('Please select a location'),
      '#default_value' => $default,
      '#options' => $branches_list['branch'] + $branches_list['camp'],
      '#branches' => $branches_list['branch'],
      '#camps' => $branches_list['camp'],
      '#attributes' => [
        'class' => ['location-select-branch'],
      ],
    ];

    $form['select']['set_location'] = [
      '#type' => 'button',
      '#value' => $this->t('Set Location'),
      '#attributes' => [
        'class' => ['location-select-set'],
      ],
    ];

    $form['post_select'] = [
      '#type' => 'container',
      '#prefix' => '<div class="location-post-select hidden">',
      '#postfix' => '</div>'
    ];

    $form['post_select']['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . t('Do you want to save') . ' <span class="location-text-replace">' . t('this location') . '</span> ' . t('as preferred?') . '</p>',
    ];

    $form['post_select']['btn'] = [
      '#type' => 'container',
      '#prefix' => '<div class="location-post-select-btn">',
      '#postfix' => '</div>'
    ];

    $form['post_select']['btn']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Yes'),
      '#attributes' => [
        'class' => ['location-select-btn location-select-yes']
      ],
    ];

    $form['post_select']['btn']['submit_no'] = [
      '#type' => 'submit',
      '#value' => $this->t('No'),
      '#attributes' => [
        'class' => ['location-select-btn location-select-no']
      ],
    ];

    $form['post_select']['disclaimer'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . t('* By saving as preferred location, all location filters on the website will automatically pre populate.') . '</p>',
      '#prefix' => '<div class="location-select-disclaimer">',
      '#postfix' => '</div>'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $destination = UrlHelper::parse($form_state->getValue('destination'));
    $destination['path'] = str_replace(base_path(), '/', $destination['path']);
    $branch = $form_state->getValue('branch');
    $destination['query']['location'] = $branch;
    $uri = \Drupal::request()->getUriForPath($destination['path']);
    $response = new RedirectResponse($uri . '?' . UrlHelper::buildQuery($destination['query']));
    $response->send();
  }

  /**
   * Get Branches list.
   */
  public static function getBranchesList($node) {
    $branches_list = [
      'branch' => [],
      'camp' => [],
    ];
    if ($node) {
      $locations = \Drupal::service('ygs_class_page.data_provider')
        ->getAvailableLocations($node->id());
      foreach ($locations as $location) {
        if (isset($branches_list[$location->bundle()][$location->id()])) {
          continue;
        }
        $branches_list[$location->bundle()][$location->id()] = $location->title->value;
      }
    }

    return $branches_list;
  }

}
