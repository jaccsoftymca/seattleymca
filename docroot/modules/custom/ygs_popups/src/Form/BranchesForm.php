<?php

namespace Drupal\ygs_popups\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class BranchesForm extends FormBase {

  /**
   * The ID provided from query.
   *
   * @var int
   */
  protected $nodeId;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Creates a new BranchSessionsForm.
   *
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   */
  public function __construct(
    QueryFactory $entity_query,
    EntityTypeManager $entity_type_manager
  ) {
    $query = parent::getRequest();
    $parameters = $query->query->all();
    if (!empty($parameters['node'])) {
      $this->nodeId = $parameters['node'];
    }
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_popups_branches_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $destination = '') {
    $form['destination'] = array('#type' => 'value', '#value' => $destination);

    $branches_list = $this->getBranchesList();
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
  public function getBranchesList() {
    $branches_list = [
      'branch' => [],
      'camp' => [],
    ];

    $db = \Drupal::database();
    if (!empty($this->nodeId) && $node = $this->entityTypeManager->getStorage('node')->load($this->nodeId)) {
      $query = $db->select('node_field_data', 'n');
      $query->innerJoin('node__field_session_class', 'fc', 'n.nid = fc.entity_id');
      $query->innerJoin('node_field_data', 'fdc', 'fc.field_session_class_target_id = fdc.nid');
      $query->condition('fdc.status', 1);
      $query->innerJoin('node__field_class_activity', 'fa', 'fc.field_session_class_target_id = fa.entity_id');
      $query->innerJoin('node_field_data', 'fda', 'fa.field_class_activity_target_id = fda.nid');
      $query->condition('fda.status', 1);
      $query->innerJoin('node__field_activity_category', 'fps', 'fa.field_class_activity_target_id = fps.entity_id');
      $query->innerJoin('node__field_session_location', 'fl', 'n.nid = fl.entity_id');
      $query->fields('fl', ['field_session_location_target_id']);
      $query->condition('n.type', 'session');
      $query->condition('fps.field_activity_category_target_id', $this->nodeId);
      $query->condition('n.status', 1);
      $items = $query->execute()->fetchAll();

      $locations_to_be_displayed = [];
      foreach ($items as $item) {
        $locations_to_be_displayed[$item->field_session_location_target_id] = $item->field_session_location_target_id;
      }
    }

    $query = $db->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title', 'type']);
    $query->condition('type', ['branch', 'camp'], 'IN');
    $query->condition('status', 1);
    $items = $query->execute()->fetchAll();
    foreach ($items as $item) {
      // By default we show all locations instead of showing empty popup.
      if (isset($locations_to_be_displayed) && !in_array($item->nid, $locations_to_be_displayed)) {
        continue;
      }
      $branches_list[$item->type][$item->nid] = $item->title;
    }

    return $branches_list;
  }

}
