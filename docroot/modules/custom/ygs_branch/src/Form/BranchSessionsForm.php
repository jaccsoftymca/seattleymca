<?php

namespace Drupal\ygs_branch\Form;

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
 * Provides the Branch Sessions search Form.
 *
 * @ingroup ygs_branch
 */
class BranchSessionsForm extends FormBase {

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
      'when_hours' => isset($parameters['when_hours']) ? $parameters['when_hours'] : '',
      'program' => isset($parameters['program']) && is_numeric($parameters['program']) ? $parameters['program'] : NULL,
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
    return 'ygs_branch_sessions_form';
  }

  /**
   * Helper method retrieving day options.
   *
   * @return array
   *   Array of day options to be used in form element.
   */
  public function getWhenDays() {
    $options = [];
    for ($i = 0; $i < 13; $i++) {
      $date = new DrupalDateTime('now + ' . $i . ' day');
      $date_title = $date->format('m/d/Y');
      if ($i == 0) {
        $date_title = $this->t('Today');
      }
      $options[$date->format('m/d/Y')] = $date_title;
    }
    return $options;
  }

  /**
   * Helper method retrieving time options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getWhenHours() {
    $options = [
      'all' => $this->t('All'),
      '00:00:00' => '12:00 AM',
      '00:30:00' => '12:30 AM',
      '01:00:00' => '1:00 AM',
      '01:30:00' => '1:30 AM',
      '02:00:00' => '2:00 AM',
      '02:30:00' => '2:30 AM',
      '03:00:00' => '3:00 AM',
      '03:30:00' => '3:30 AM',
      '04:00:00' => '4:00 AM',
      '04:30:00' => '4:30 AM',
      '05:00:00' => '5:00 AM',
      '05:30:00' => '5:30 AM',
      '06:00:00' => '6:00 AM',
      '06:30:00' => '6:30 AM',
      '07:00:00' => '7:00 AM',
      '07:30:00' => '7:30 AM',
      '08:00:00' => '8:00 AM',
      '08:30:00' => '8:30 AM',
      '09:00:00' => '9:00 AM',
      '09:30:00' => '9:30 AM',
      '10:00:00' => '10:00 AM',
      '10:30:00' => '10:30 AM',
      '11:00:00' => '11:00 AM',
      '11:30:00' => '11:30 AM',
      '12:00:00' => '12:00 PM',
      '12:30:00' => '12:30 PM',
      '13:00:00' => '1:00 PM',
      '13:30:00' => '1:30 PM',
      '14:00:00' => '2:00 PM',
      '14:30:00' => '2:30 PM',
      '15:00:00' => '3:00 PM',
      '15:30:00' => '3:30 PM',
      '16:00:00' => '4:00 PM',
      '16:30:00' => '4:30 PM',
      '17:00:00' => '5:00 PM',
      '17:30:00' => '5:30 PM',
      '18:00:00' => '6:00 PM',
      '18:30:00' => '6:30 PM',
      '19:00:00' => '7:00 PM',
      '19:30:00' => '7:30 PM',
      '20:00:00' => '8:00 PM',
      '20:30:00' => '8:30 PM',
      '21:00:00' => '9:00 PM',
      '21:30:00' => '9:30 PM',
      '22:00:00' => '10:00 PM',
      '22:30:00' => '10:30 PM',
      '23:00:00' => '11:00 PM',
      '23:30:00' => '11:30 PM',
    ];
    return $options;
  }

  /**
   * Helper method retrieving program options.
   *
   * @return array
   *   Array of program options to be used in form element.
   */
  public function getPrograms() {
    $options = ['all' => $this->t('All')];
    $query = $this->entityQuery
      ->get('node')
      ->condition('status', 1)
      ->condition('type', 'program');
    $entity_ids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($entity_ids);
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
          'url.query_args:when_day',
          'url.query_args:when_hours',
          'url.query_args:program',
        ],
      ];

      $form['#prefix'] = '<div id="branch-sessions-form-wrapper"><div class="container">';
      $form['#suffix'] = '</div></div>';

      $form['title'] = [
        '#prefix' => '<h1>',
        '#markup' => $this->t('What’s Happening at %location', ['%location' => $this->node ? $this->node->getTitle() : 'Y']),
        '#suffix' => '</h1>',
      ];

      $form['filter_controls'] = [
        '#markup' => '
          <div class="controls-wrapper hidden-sm hidden-md hidden-lg">
          <a href="#" class="btn btn-link transparent-blue add-filters">' . $this->t('Add filters') . '</a>
          <a href="#" class="btn btn-link transparent-blue close-filters hidden">' . $this->t('Close filters') . '</a>
          </div>',
      ];

      $when_day_options = $this->getWhenDays();
      $form['when_day'] = [
        '#type' => 'select',
        '#title' => $this->t('When'),
        '#options' => $when_day_options,
        '#prefix' => '<div class="selects-container js-form-wrapper form-wrapper hidden-xs"><div class="inner">',
        '#suffix' => '<div class="at">' . $this->t('AT') . '</div>',
        '#default_value' => isset($values['when_day']) ? $values['when_day'] : 'all',
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

      $when_hours_options = $this->getWhenHours();
      $form['when_hours'] = [
        '#type' => 'select',
        '#title' => '',
        '#options' => $when_hours_options,
        '#default_value' => isset($values['when_hours']) ? $values['when_hours'] : 'all',
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

      $program_options = $this->getPrograms();
      $form['program'] = [
        '#type' => 'select',
        '#title' => $this->t('Which Schedule?'),
        '#options' => $program_options,
        '#default_value' => isset($values['program']) ? $values['program'] : 'all',
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

      $full_schedule_link = Url::fromUri('internal:/schedules', [
        'query' => [
          'location' => ($this->node) ? $this->node->id() : NULL,
          'program' => !empty($values['program']) ? $values['program'] : 'all',
          'date' => $values['when_day'],
          'time' => !empty($values['when_hours']) ? $values['when_hours'] : 'all',
        ],
      ])->toString();

      $form['schedule'] = [
        '#markup' => '<div class="schedule-wrapper hidden-xs">
          <a href="' . $full_schedule_link . '" class="full-schedule btn btn-link blue" target="_blank">' . $this->t('VIEW FULL SCHEDULE') . '</a>
        </div>',
        '#suffix' => '</div></div>',
      ];

      $form['results'] = [
        '#prefix' => '<div class="results">',
        '#markup' => $formatted_results,
        '#suffix' => '</div>',
        '#weight' => 100,
      ];

      $form['filters'] = [
        '#type' => 'container',
        '#prefix' => '<div class="filters-main-wrapper hidden-sm">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => [
            'container',
            'filters-container',
          ],
        ],
        '#markup' => '',
        '#weight' => 99,
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
          <a href="' . $full_schedule_link . '" class="full-schedule btn btn-link blue" target="_blank">' . $this->t('VIEW FULL SCHEDULE') . '</a>
        </div>',
        '#suffix' => '</div>',
        '#weight' => 101,
      ];
    }
    catch (Exception $e) {
      $this->logger->error('Failed to build the form. Message: %msg', ['%msg' => $e->getMessage()]);
    }

    return $form;
  }

  /**
   * Build results.
   */
  public function getSessions($parameters) {
    $programOptions = $this->getPrograms();

    if (!$this->node) {
      return [];
    }

    $conditions = [
      'actual' => 1,
      'class_type' => 'activity',
      'location' => $this->node->id(),
    ];

    if ($parameters['program'] !== 'all' && !empty($programOptions[$parameters['program']])) {
      $conditions['field_program'] = $parameters['program'];
    }

    $date_string = $parameters['when_day'] . ' 00:00:00';
    if (!empty($parameters['when_hours']) && $parameters['when_hours'] !== 'all') {
      $date_string = $parameters['when_day'] . ' ' . $parameters['when_hours'];
    }
    $conditions['from'] = max(strtotime($date_string), time());
    $conditions['to'] = strtotime($parameters['when_day'] . ' next day');

    // Fetch session occurrences.
    $session_instances = $this->sessionInstanceManager->getSessionInstancesByParams($conditions);

    return $session_instances;
  }

  /**
   * Build results.
   */
  public function buildResults($parameters) {
    $session_instances = $this->getSessions($parameters);

    $extended_teasers = [];
    $previous_rounded_time = '';
    foreach ($session_instances as $id => $session_instance) {
      $timestamp = $session_instance->getTimestamp();
      $date = DrupalDateTime::createFromTimestamp($timestamp);
      $hour = $date->format('g');
      $minutes = $date->format('i');
      $minute = '00';
      if ($minutes >= 15) {
        $minute = '15';
      }
      if ($minutes >= 30) {
        $minute = '30';
      }
      if ($minutes >= 45 && $minutes <= 59) {
        $minute = '45';
      }
      $rounded_time = $hour . ':' . $minute . ' ' . $date->format('a');
      if ($rounded_time !== $previous_rounded_time) {
        $previous_rounded_time = $rounded_time;
      }
      else {
        $rounded_time = '';
      }
      $time = $date->format('g:i a');
      $description = $class_title = '';
      if ($class = $this->entityTypeManager->getStorage('node')->load($session_instance->class->target_id)) {
        $class_title = $class->label();
        if (!empty($class->field_class_description->value)) {
          $description = $class->field_class_description->view('teaser');
        }
      }
      $program_title = '';
      // TODO: Modify this, field_program have multiple values.
      $program_id = $session_instance->get('field_program')->first()->target_id;
      if ($program = $this->entityTypeManager->getStorage('node')->load($program_id)) {
        $program_title = $program->label();
      }
      $included_in_membership = TRUE;
      $session = current($session_instance->session->referencedEntities());
      if ($member_price_items = $session->field_session_mbr_price->getValue()) {
        $member_price = (float) reset($member_price_items)['value'];
        if ($member_price) {
          $included_in_membership = FALSE;
        }
      }
      // Ticket required.
      $ticket_required = FALSE;
      if ($ticket_required_items = $session->field_session_ticket->getValue()) {
        $ticket_required = (int) reset($ticket_required_items)['value'];
      }
      $extended_teasers[$id] = [
        'program_title' => $program_title,
        'class_title' => $class_title,
        'rounded_time' => $rounded_time,
        'time' => $time,
        'description' => $description,
        'ticket_required' => $ticket_required,
        'included_in_membership' => $included_in_membership,
        'url' => Url::fromUri('internal:/node/' . $class->id(), [
          'query' => [
            'location' => $session_instance->location->target_id,
            'session' => $session_instance->session->target_id,
          ],
        ]),
      ];
    }

    $formatted_results = [
      '#theme' => 'ygs_branch_sessions',
      '#teasers' => $extended_teasers,
    ];

    $formatted_results = render($formatted_results);

    return $formatted_results;
  }

  /**
   * Build filters.
   */
  public function buildFilters($parameters) {
    $filters_markup = '';
    $when_day_options = $this->getWhenDays();
    if (!empty($parameters['when_day']) && key($when_day_options) !== $parameters['when_day']) {
      $filters[$parameters['when_day']] = $parameters['when_day'];
    }
    if (!empty($parameters['when_hours']) && $parameters['when_hours'] !== 'all') {
      $filters[$parameters['when_hours']] = $parameters['when_hours'];
      $when_hours = $this->getWhenHours();
      $filters[$parameters['when_hours']] = $when_hours[$parameters['when_hours']];
    }
    if (!empty($parameters['program']) && $parameters['program'] !== 'all') {
      $programs = $this->getPrograms();
      $filters[$parameters['program']] = $programs[$parameters['program']];
    }
    if (!empty($filters)) {
      $filters_markup = [
        '#theme' => 'subcategory_filters',
        '#filters' => $filters,
      ];
      $filters_markup = render($filters_markup);
    }
    return $filters_markup;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    $parameters = [];
    if (!empty($values['program'])) {
      $parameters['program'] = $values['program'];
    }
    if (!empty($values['when_day'])) {
      $parameters['when_day'] = $values['when_day'];
    }
    if (!empty($values['when_hours'])) {
      $parameters['when_hours'] = $values['when_hours'];
    }
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
  }

}
