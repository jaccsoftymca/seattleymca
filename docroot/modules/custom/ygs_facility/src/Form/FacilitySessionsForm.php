<?php

namespace Drupal\ygs_facility\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\ygs_session_instance\SessionInstanceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the Facility Sessions search Form.
 *
 * @ingroup ygs_facility
 */
class FacilitySessionsForm extends FormBase {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The node object.
   *
   * @var NodeInterface
   */
  protected $node;

  /**
   * The state.
   *
   * @var array
   */
  protected $state;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The SessionInstanceManager.
   *
   * @var \Drupal\ygs_session_instance\SessionInstanceManagerInterface
   */
  protected $sessionInstanceManager;

  /**
   * Creates a new BranchSessionsForm.
   *
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param RequestStack $request_stack
   *   The request stack.
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   * @param SessionInstanceManagerInterface $session_instance_manager
   *   The Session Instance Manager.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    QueryFactory $entity_query,
    EntityTypeManager $entity_type_manager,
    SessionInstanceManagerInterface $session_instance_manager
  ) {
    $query = parent::getRequest();
    $parameters = $query->request->all();
    $today = new DrupalDateTime('now');
    $today = $today->format('m/d/Y');
    $state = [
      'when_day' => isset($parameters['when_day']) ? $parameters['when_day'] : $today,
      'activity' => isset($parameters['activity']) && is_numeric($parameters['activity']) ? $parameters['activity'] : NULL,
    ];
    $this->logger = $logger_factory->get('ygs_branch');
    $this->setRequestStack($request_stack);
    $this->node = $this->getRequest()->get('node');
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionInstanceManager = $session_instance_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('session_instance.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_facility_sessions_form';
  }


  /**
   * Helper method retrieving program options.
   *
   * @return array
   *   Array of program options to be used in form element.
   */
  public function getActivities() {
    $options = ['all' => $this->t('All')];

    if (!$this->node) {
      return $options;
    }

    // Get the available programs using session_instances info.
    $query = \Drupal::database()->select('session_instance', 'si');
    $query->leftJoin('session_instance__field_activity', 'p', 'p.entity_id = si.id and p.deleted = :deleted', [':deleted' => 0]);
    $query->condition('si.facility', $this->node->id());
    $query->distinct();
    $query->addField('p', 'field_activity_target_id');
    $result = $query->execute();
    $activity_ids = $result->fetchAllKeyed(0, 0);

    if (!$activity_ids) {
      return $options;
    }

    $query = $this->entityQuery
      ->get('node')
      ->condition('status', 1)
      ->condition('nid', $activity_ids, 'IN')
      ->condition('type', 'activity')
      ->sort('title');
    $entity_ids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($entity_ids);
    foreach ($nodes as $id => $node) {
      $options[$id] = $node->getTitle();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      // Populate form state with state data.
      if ($this->state) {
        foreach ($this->state as $key => $value) {
          if (!$form_state->hasValue($key)) {
            $form_state->setValue($key, $value);
          }
        }
      }

      $values = $form_state->getValues();
      $formatted_results = self::buildResults($values);

      // Vary on the listed query args.
      $form['#cache'] = [
        'max-age' => 0,
        'contexts' => [
          'url.query_args:activity',
        ],
      ];

      $form['#prefix'] = '<div id="branch-sessions-form-wrapper"><div class="container">';
      $form['#suffix'] = '</div></div>';
      $form['#attributes'] = ['class' => 'ygs-facility-sessions-form ygs-branch-sessions-form'];
      $form['title'] = [
        '#prefix' => '<h1>',
        '#markup' => $this->t('What’s Happening at %location', ['%location' => $this->node ? $this->node->getTitle() : 'Y']),
        '#suffix' => '</h1>',
      ];

      $activity_options = $this->getActivities();
      $form['activity'] = [
        '#type' => 'select',
        '#title' => $this->t('Which Program?'),
        '#options' => $activity_options,
        '#default_value' => isset($values['activity']) ? $values['activity'] : 'all',
        '#attributes' => [
          'class' => [
            'form-item-program',
          ],
        ],
        '#prefix' => '<div class="selects-container"><div class="inner">',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'branch-sessions-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $form['schedule'] = [
        '#markup' => '<div class="schedule-wrapper hidden-xs">
          <a href="#" data-location-id="' . $this->node->field_session_location->target_id . '" class="full-schedule full-schedule-facility btn btn-link blue" target="_blank">' . $this->t('See All Programs') . '</a>
        </div>',
        '#suffix' => '</div></div>',
      ];

      $form['results'] = [
        '#prefix' => '<div class="results">',
        'results' => $formatted_results,
        '#suffix' => '</div>',
        '#weight' => 100,
      ];

      $form['button'] = [
        '#type' => 'button',
        '#value' => $this->t('Apply'),
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'method' => 'replace',
          'event' => 'click',
        ],
      ];

      $form['schedule_mobile'] = [
        '#markup' => '<div class="schedule-wrapper-mobile hidden-sm hidden-md hidden-lg">
          <a href="#" data-location-id="' . $this->node->field_session_location->target_id . '" class="full-schedule full-schedule-facility btn btn-link blue" target="_blank">' . $this->t('See All Programs') . '</a>
        </div>',
        '#suffix' => '</div>',
        '#weight' => 101,
      ];
    } catch (Exception $e) {
      $this->logger->error('Failed to build the form. Message: %msg', ['%msg' => $e->getMessage()]);
    }

    return $form;
  }

  /**
   * Build results.
   */
  public function getSessions($parameters) {
    $activity_options = $this->getActivities();

    if (!$this->node) {
      return [];
    }

    // Get the available programs using session_instances info.
    $query = \Drupal::database()->select('session_instance', 'si');
    $query->condition('si.facility', $this->node->id());
    $query->condition('si.class_type', 'flexreg');
    $query->condition('si.actual', 1);
    $query->addField('si', 'class');
    $query->distinct();
    if ($parameters['activity'] !== 'all' && !empty($activity_options[$parameters['activity']])) {
      $query->leftJoin('session_instance__field_activity', 'p', 'p.entity_id = si.id and p.deleted = :deleted', [':deleted' => 0]);
      $query->condition('p.field_activity_target_id', $parameters['activity']);
    }
    $result = $query->execute();
    $classes_ids = $result->fetchAllKeyed(0, 0);

    if (!$classes_ids) {
      return [];
    }
    $query = $this->entityQuery
      ->get('node')
      ->condition('status', 1)
      ->condition('nid', $classes_ids, 'IN')
      ->condition('type', 'class')
      ->sort('title');
    $entity_ids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($entity_ids);

    return $nodes;
  }

  /**
   * Build results.
   */
  public function buildResults($parameters) {
    $classes = $this->getSessions($parameters);

    $extended_teasers = [];
    foreach ($classes as $id => $class) {
      $description = $class_title = '';
      $class_title = $class->label();
      if (!empty($class->field_class_description->value)) {
        $description = $class->field_class_description->view('teaser');
      }
      // TODO: Modify this, field_class_activity have multiple values.
      $activities = $class->get('field_class_activity')->referencedEntities();
      $activity = reset($activities);
      $subprogram = $activity->get('field_activity_category')->entity;
      $program = $subprogram->get('field_category_program')->entity;
      $program_title = $program->label();
      $extended_teasers[$id] = [
        'program_title' => $program_title,
        'class_title' => $class_title,
        'description' => $description,
        'url' => Url::fromUri('internal:/node/' . $class->id(), [
          'query' => [
            'location' => $this->node->field_session_location->target_id,
          ],
        ]),
      ];
    }

    $formatted_results = [
      '#theme' => 'ygs_facility_sessions',
      '#teasers' => $extended_teasers,
    ];

    return $formatted_results;
  }

  /**
   * Build filters.
   */
  public function buildFilters($parameters) {
    $filters_markup = '';
    if (!empty($parameters['activity']) && $parameters['activity'] !== 'all') {
      $activities = $this->getActivities();
      $filters[$parameters['activity']] = $activities[$parameters['activity']];
    }
    if (!empty($filters)) {
      $filters_markup = [
        '#theme' => 'subcategory_filters',
        '#filters' => $filters,
      ];
    }
    return $filters_markup;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    $parameters = [];
    if (!empty($values['activity'])) {
      $parameters['activity'] = $values['activity'];
    }
    $today = new DrupalDateTime('now');
    $parameters['when_day'] = $today->format('m/d/Y');

    $formatted_results = self::buildResults($parameters);
    $filters = self::buildFilters($parameters);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#branch-sessions-form-wrapper .results', $formatted_results));
    $response->addCommand(new HtmlCommand('#branch-sessions-form-wrapper .filters-main-wrapper', $filters));
    $form_state->setRebuild();
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

}
