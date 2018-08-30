<?php

namespace Drupal\ygs_schedules\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\ygs_session_instance\SessionInstanceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the Schedules Sessions search Form.
 *
 * @ingroup ygs_branch
 */
class SchedulesSearchForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

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
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param RequestStack $request_stack
   *   The request stack.
   * @param QueryFactory $entity_query
   *   The entity query factory.
   * @param EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   * @param SessionInstanceManager $session_instance_manager
   *   The SessionInstanceManager.
   */
  public function __construct(
    Connection $connection,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    QueryFactory $entity_query,
    EntityTypeManager $entity_type_manager,
    SessionInstanceManager $session_instance_manager
  ) {
    $this->connection = $connection;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionInstanceManager = $session_instance_manager;

    $query = parent::getRequest();
    $parameters = $query->query->all();
    $today = new DrupalDateTime('now');
    $today = $today->format('m/d/Y');
    $state = [
      'location' => isset($parameters['location']) ? $parameters['location'] : '',
      'room' => isset($parameters['room']) ? $parameters['room'] : 'all',
      'program' => isset($parameters['program']) ? $parameters['program'] : 'all',
      'category' => isset($parameters['category']) ? $parameters['category'] : 'all',
      'age' => isset($parameters['age']) ? $parameters['age'] : 'all',
      'class' => isset($parameters['class']) ? $parameters['class'] : 'all',
      'date' => isset($parameters['date']) ? $parameters['date'] : $today,
      'time' => isset($parameters['time']) ? $parameters['time'] : 'all',
      'display' => isset($parameters['display']) ? $parameters['display'] : 0,
    ];
    $this->logger = $logger_factory->get('ygs_schedules');
    $this->setRequestStack($request_stack);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
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
    return 'ygs_schedules_search_form';
  }

  /**
   * Helper method retrieving location options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getLocationOptions() {
    static $options = [];

    if (!$options) {
      $options = [
        'branches' => [],
        'camps' => [],
      ];
      $map = [
        'branch' => 'branches',
        'camp' => 'camps',
      ];
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', ['branch', 'camp'], 'IN');
      $entity_ids = $query->execute();
      $nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($entity_ids);
      foreach ($nodes as $id => $node) {
        $options[$map[$node->bundle()]][$id] = $node->getTitle();
      }
      // Remove any "Empty" option categories.
      foreach ($options as $k => $op) {
        if (empty($op)) {
          unset($options[$k]);
        }
      }
    }

    $options = array('_none' => 'Select Location') + $options;
    return $options;
  }

  /**
   * Helper method retrieving "Physical Location" options.
   *
   * @return array
   *   Array of "Physical Location" options to be used in form element.
   */
  public function getPhysicalLocationOptions() {
    static $options = [];
    $location = $this->state['location'];
    if (!$options) {
      $options = ['all' => $this->t('All')];
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', 'session')
        ->condition('field_physical_location_text', '', '<>')
        ->sort('title');
      if ($location && !empty($location)) {
        $query->condition('field_session_location', $location);
      }

      $entity_ids = $query->execute();

      $physical_locations = [];
      if (!empty($entity_ids)) {
        $field_query = $this->connection->select('node__field_physical_location_text', 'loc')
          ->distinct()
          ->fields('loc', ['field_physical_location_text_value'])
          ->condition('entity_id', $entity_ids, 'IN')
          ->condition('deleted', 0);
        $field_query->join('node_field_data', 'fd', 'fd.nid = loc.entity_id AND fd.status = 1');
        $result = $field_query->execute();
        $physical_locations = array_keys($result->fetchAllKeyed());
      }
      $physical_locations = array_combine($physical_locations, $physical_locations);
      // Sort list alphabetically.
      asort($physical_locations);
      $options += $physical_locations;
    }
    return $options;
  }

  /**
   * Helper method retrieving program options.
   *
   * @return array
   *   Array of program options to be used in form element.
   */
  public function getProgramOptions() {
    static $options = [];

    if (!$options) {
      $options = ['all' => $this->t('All')];
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', 'program');
      $entity_ids = $query->execute();
      $nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($entity_ids);
      foreach ($nodes as $id => $node) {
        $options[$id] = $node->getTitle();
      }
    }

    return $options;
  }

  /**
   * Helper method retrieving category options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getCategoryOptions() {
    static $options = [];
    $program = $this->state['program'];

    if (!$options) {
      $options = ['all' => $this->t('All')];
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', 'program_subcategory')
        ->sort('title');
      if ($program && $program !== 'all') {
        $query->condition('field_category_program', $program);
      }
      $entity_ids = $query->execute();

      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($entity_ids);
      foreach ($entity_ids as $id) {
        $options[$id] = $nodes[$id]->getTitle();
      }
    }

    return $options;
  }

  /**
   * Helper method retrieving class options.
   *
   * @return array
   *   Array of class to be used in form element.
   */
  public function getClassOptions() {
    static $options = [];

    $category = $this->state['category'];
    if (!$options) {
      $options = ['all' => $this->t('All')];

      // Get activities ids.
      if ($category == 'all') {
        $categories_ids = array_keys($this->getCategoryOptions());
      }
      else {
        $categories_ids = [$category];
      }
      $query = $this->entityQuery
        ->get('node')
        ->condition('status', 1)
        ->condition('type', 'activity')
        ->condition('field_activity_category', $categories_ids, 'IN');
      $activities_ids = $query->execute();

      if ($activities_ids) {
        // Get classes.
        $query = $this->entityQuery
          ->get('node')
          ->condition('status', 1)
          ->condition('type', 'class')
          ->condition('field_type', 'flexreg', '!=')
          ->condition('field_class_activity', $activities_ids, 'IN')
          ->sort('title');
        $entity_ids = $query->execute();

        if ($entity_ids) {
          $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($entity_ids);
          foreach ($entity_ids as $id) {
            $options[$id] = $nodes[$id]->getTitle();
          }
        }
      }
    }

    return $options;
  }

  /**
   * Helper method retrieving age options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getAgeOptions() {
    $options = ['all' => t('All')];
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'age');
    $query->sort('weight');
    $entity_ids = $query->execute();
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple($entity_ids);
    if (!empty($terms)) {
      foreach ($terms as $id => $term) {
        $options[$id] = $term->getName();
      }
    }
    return $options;
  }

  /**
   * Helper method retrieving time options.
   *
   * @return array
   *   Array of time options to be used in form element.
   */
  public function getTimeOptions() {
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
   * Helper method retrieving the display theme.
   *
   * @return string
   *   The theme name to use.
   */
  public function getDisplay() {
    $display = $this->state['display'];
    switch ($display) {
      case 1:
        $theme = 'ygs_schedules_main_class';
        break;

      default:
        $theme = 'ygs_schedules_main';
        break;

    }
    return $theme;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      // Populate state data with user input, if exists.
      if ($form_state->getUserInput()) {
        foreach ($form_state->getUserInput() as $key => $value) {
          if (array_key_exists($key, $this->state)) {
            $this->state[$key] = $value;
          }
        }
      }
      // Populate form state with state data.
      if ($this->state) {
        foreach ($this->state as $key => $value) {
          if (!$form_state->hasValue($key)) {
            $form_state->setValue($key, $value);
          }
        }
      }

      $values = $form_state->getValues();

      // Vary on the listed query args.
      $form['#cache'] = [
        'max-age' => 0,
        'contexts' => [
          'url.query_args:location',
          'url.query_args:room',
          'url.query_args:program',
          'url.query_args:category',
          'url.query_args:age',
          'url.query_args:date',
          'url.query_args:time',
          'url.query_args:display',
        ],
      ];

      $form['#attached'] = [
        'library' => [
          'ygs_schedules/ygs_schedules',
        ],
      ];

      $form['filter_controls'] = [
        '#markup' => '
          <div class="container controls-wrapper hidden-sm hidden-md hidden-lg">
          <a href="#" class="btn btn-link transparent-blue add-filters">' . $this->t('Add filters') . '</a>
          <a href="#" class="btn btn-link transparent-blue close-filters hidden">' . $this->t('Close filters') . '</a>
          </div>',
      ];

      $form['selects'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'container',
            'selects-container',
            'hidden-xs',
          ],
        ],
      ];

      $locationOptions = $this->getLocationOptions();
      $form['selects']['location'] = [
        '#type' => 'select',
        '#title' => $this->t('Location'),
        '#options' => $locationOptions,
        '#prefix' => '<hr/>',
        '#default_value' => isset($values['location']) ? $values['location'] : '',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $room_options = $this->getPhysicalLocationOptions();
      $form['selects']['room'] = [
        '#type' => 'select',
        '#title' => $this->t('Room / Area'),
        '#options' => $room_options,
        '#default_value' => isset($values['room']) ? $values['room'] : '',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $programOptions = $this->getProgramOptions();
      $form['selects']['program'] = [
        '#type' => 'select',
        '#title' => $this->t('Program'),
        '#options' => $programOptions,
        '#default_value' => isset($values['program']) ? $values['program'] : 'all',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $categoryOptions = $this->getCategoryOptions();
      $form['selects']['category'] = [
        '#type' => 'select',
        '#title' => $this->t('Sub-Program'),
        '#options' => $categoryOptions,
        '#default_value' => isset($values['category']) ? $values['category'] : 'all',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $classOptions = $this->getClassOptions();
      $form['selects']['class'] = [
        '#type' => 'select',
        '#title' => $this->t('Class'),
        '#options' => $classOptions,
        '#default_value' => isset($values['class']) ? $values['class'] : 'all',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $ageOptions = $this->getAgeOptions();
      $form['selects']['age'] = [
        '#type' => 'select',
        '#title' => $this->t('Age'),
        '#options' => $ageOptions,
        '#default_value' => isset($values['age']) ? $values['age'] : 'all',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $form['selects']['date'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Date'),
        '#default_value' => isset($values['date']) ? $values['date'] : '',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'keyup',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $timeOptions = $this->getTimeOptions();
      $form['selects']['time'] = [
        '#type' => 'select',
        '#title' => $this->t('Start Time:'),
        '#options' => $timeOptions,
        '#default_value' => isset($values['time']) ? $values['time'] : 'all',
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      $form['selects']['display'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Weekly View'),
        '#default_value' => isset($values['display']) ? $values['display'] : 0,
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'wrapper' => 'schedules-search-form-wrapper',
          'event' => 'change',
          'method' => 'replace',
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      // If on Weekly View, default and disable time field.
      if (isset($values['display']) && $values['display']) {
        $form['selects']['time']['#disabled'] = TRUE;
        $form['selects']['time']['#default_value'] = 'all';
      }

      $form['selects']['button'] = [
        '#type' => 'button',
        '#prefix' => '<div class="actions-wrapper">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => [
            'btn',
            'blue',
            'hidden-xs',
            'hidden-sm',
            'hidden-md',
            'hidden-lg',
          ],
        ],
        '#value' => $this->t('Apply filters'),
        '#ajax' => [
          'callback' => [$this, 'rebuildAjaxCallback'],
          'method' => 'replace',
          'event' => 'click',
        ],
      ];

      $form['filters'] = [
        '#type' => 'container',
        '#prefix' => '<div class="filters-main-wrapper hidden-sm"><div class="container filters-container">',
        '#suffix' => '</div></div>',
        '#attributes' => [
          'class' => [
            'container',
            'filters-container',
          ],
        ],
        '#markup' => '',
        '#weight' => 99,
        'filters' => self::buildFilters($values),
      ];

      $form['alerts'] = [
        views_embed_view('header_alerts', 'specific_alerts_block', $values['location']),
      ];
      $form['alerts']['#prefix'] = '<div class="alerts-wrapper cleafix"><div class="container"><div class="row"><div class="col-xs-12">';
      $form['alerts']['#suffix'] = '</div></div></div></div>';
      $form['alerts']['#weight'] = 100;

      $user_input = $form_state->getUserInput();
      if (!empty($user_input['location'])) {
        $user_input['location'] = isset($locationOptions['branches'][$values['location']]) || isset($locationOptions['camps'][$values['location']]) ? $values['location'] : '';
      }
      if (!empty($user_input['room'])) {
        $user_input['room'] = isset($room_options[$values['room']]) ? $values['room'] : 'all';
      }
      if (!empty($user_input['program'])) {
        $user_input['program'] = isset($programOptions[$values['program']]) ? $values['program'] : 'all';
      }
      if (!empty($user_input['category'])) {
        $user_input['category'] = isset($categoryOptions[$values['category']]) ? $values['category'] : 'all';
      }
      if (!empty($user_input['class'])) {
        $user_input['class'] = isset($classOptions[$values['class']]) ? $values['class'] : 'all';
      }
      if (!empty($user_input['age'])) {
        $user_input['age'] = isset($ageOptions[$values['age']]) ? $values['age'] : 'all';
      }
      if (!empty($user_input['time'])) {
        $user_input['time'] = isset($timeOptions[$values['time']]) ? $values['time'] : 'all';
      }
      if (!empty($user_input['display'])) {
        $user_input['display'] = !empty($values['display']) ? $values['display'] : 0;
      }
      $form_state->setUserInput($user_input);

      $formatted_results = '';
      $branch_hours = '';
      if (!$form_state->getTriggeringElement()) {
        $renderer = \Drupal::service('renderer');
        $branch_hours = self::buildBranchHours($values);
        $branch_hours = $renderer->renderRoot($branch_hours);
        $formatted_results = self::buildResults($values);
        $formatted_results = $renderer->renderRoot($formatted_results);
      }

      // TODO: replace with render arrays.
      $form['#prefix'] = '<div id="schedules-search-form-wrapper">';
      $form['#suffix'] = '<div class="branch-hours-wrapper clearfix">' . $branch_hours . '</div>
        <div class="results clearfix">' . $formatted_results . '</div>
      </div>';
    }
    catch (Exception $e) {
      $this->logger->error('Failed to build the form. Message: %msg', ['%msg' => $e->getMessage()]);
    }

    return $form;
  }

  /**
   * Build filters.
   */
  public function buildFilters($parameters) {
    $filters_markup = '';

    $locationOptions = $this->getLocationOptions();
    $room_options = $this->getPhysicalLocationOptions();
    $programOptions = $this->getProgramOptions();
    $categoryOptions = $this->getCategoryOptions();
    $classOptions = $this->getClassOptions();
    $ageOptions = $this->getAgeOptions();
    $timeOptions = $this->getTimeOptions();
    $required_filters = ['all' => 'all'];

    if ($parameters['location'] !== 'all') {
      if (!empty($locationOptions['branches'][$parameters['location']])) {
        $filters[$parameters['location']] = $locationOptions['branches'][$parameters['location']];
      }
      if (!empty($locationOptions['camps'][$parameters['location']])) {
        $filters[$parameters['location']] = $locationOptions['camps'][$parameters['location']];
      }
      $required_filters = [$parameters['location'] => $parameters['location']];
    }
    if ($parameters['room'] !== 'all' && !empty($room_options[$parameters['room']])) {
      $filters[$parameters['room']] = $room_options[$parameters['room']];
    }
    if ($parameters['program'] !== 'all' && !empty($programOptions[$parameters['program']])) {
      $filters[$parameters['program']] = $programOptions[$parameters['program']];
    }
    if ($parameters['category'] !== 'all' && !empty($categoryOptions[$parameters['category']])) {
      $filters[$parameters['category']] = $categoryOptions[$parameters['category']];
    }
    if ($parameters['class'] !== 'all' && !empty($classOptions[$parameters['class']])) {
      $filters[$parameters['class']] = $classOptions[$parameters['class']];
    }
    if ($parameters['age'] !== 'all' && !empty($ageOptions[$parameters['age']])) {
      $filters[$parameters['age']] = $ageOptions[$parameters['age']];
    }
    if (!empty($parameters['date'])) {
      $filters[$parameters['date']] = $parameters['date'];
      $required_filters[$parameters['date']] = $parameters['date'];
    }
    if ($parameters['time'] !== 'all' && !empty($timeOptions[$parameters['time']])) {
      $filters[$parameters['time']] = $timeOptions[$parameters['time']];
    }
    if (!empty($filters)) {
      $filters_markup = [
        '#theme' => 'subcategory_filters',
        '#filters' => $filters,
        '#required_filters' => $required_filters,
      ];
    }

    return $filters_markup;
  }

  /**
   * Build Branch Hours.
   */
  public function buildBranchHours($parameters) {
    $markup = '';
    if (!$parameters['location']) {
      return $markup;
    }

    $locationOptions = $this->getLocationOptions();
    if (!empty($locationOptions['branches'][$parameters['location']])) {
      $id = $parameters['location'];
    }
    if (!empty($locationOptions['camps'][$parameters['location']])) {
      $id = $parameters['location'];
    }
    if (isset($id)) {
      $branch_hours = [];
      $timezone = drupal_get_user_timezone();
      $date = DrupalDateTime::createFromFormat('m/d/Y', $parameters['date'], $timezone);
      $date_short = strtolower($date->format('D'));
      $date = strtolower($date->format('l'));
      if ($location = $this->entityTypeManager->getStorage('node')->load($id)) {
        if ($location->hasField('field_branch_hours') && $location->field_branch_hours->first()) {
          $branch_hours['main']['hours'][] = $location->field_branch_hours->first()->get('hours_' . $date_short)->getValue();
        }
      }
      if ($location->hasField('field_collection_activity_group')) {
        $activity_group = $location->field_collection_activity_group->referencedEntities();
        foreach ($activity_group as $collection) {
          if ($collection->field_headline->value) {
            $collection_hours = $collection->field_collection_activity_hours->referencedEntities();
            foreach ($collection_hours as $collection_hour) {
              if ($collection_hour->field_day_of_the_week->value == $date && $collection_hour->field_start_end_time->value) {
                $branch_hours[$collection->field_headline->value]['hours'][] = $collection_hour->field_start_end_time->value;
              }
            }
          }
        }
      }
    }
    if (!empty($branch_hours)) {
      $markup = [
        '#theme' => 'ygs_branch_hours_block',
        '#branch_hours' => $branch_hours,
      ];
    }

    return $markup;
  }

  /**
   * Build Alerts.
   */
  public function buildAlerts($parameters) {
    $build = '';
    if (!$parameters['location']) {
      return $build;
    }

    $locationOptions = $this->getLocationOptions();
    if (!empty($locationOptions['branches'][$parameters['location']])) {
      $id = $parameters['location'];
    }
    if (!empty($locationOptions['camps'][$parameters['location']])) {
      $id = $parameters['location'];
    }
    if (!isset($id)) {
      return $build;
    }
    $build = [
      '#theme' => 'ygs_schedules_alerts_block',
      '#alerts' => views_embed_view('header_alerts', 'specific_alerts_block', $id),
    ];

    return $build;
  }

  /**
   * Build results.
   */
  public function getSessions($parameters) {
    $locationOptions = $this->getLocationOptions();
    $room_options = $this->getPhysicalLocationOptions();
    $programOptions = $this->getProgramOptions();
    $categoryOptions = $this->getCategoryOptions();
    $classOptions = $this->getClassOptions();

    $conditions = [
      'actual' => 1,
      'class_type' => 'activity',
    ];
    $location = $parameters['location'];
    if (!$location) {
      return [];
    }

    if (isset($locationOptions['branches'][$location]) || isset($locationOptions['camps'][$location])) {
      $conditions['location'] = $location;
    }
    if ($parameters['class'] !== 'all' && !empty($classOptions[$parameters['class']])) {
      $conditions['class'] = $parameters['class'];
    }
    if ($parameters['program'] !== 'all' && !empty($programOptions[$parameters['program']])) {
      $conditions['field_program'] = $parameters['program'];
    }
    if ($parameters['category'] !== 'all' && !empty($categoryOptions[$parameters['category']])) {
      $conditions['field_program_subcategory'] = $parameters['category'];
    }
    if ($parameters['age'] !== 'all') {
      $conditions['field_age'] = $parameters['age'];
    }

    // Format for weekly view.
    if ($parameters['display']) {
      $conditions['from'] = strtotime($parameters['date'] . 'T00:00:00');
      $conditions['to'] = strtotime($parameters['date'] . 'T24:00:00 + 6 days');
    }
    else {
      $date_string = $parameters['date'] . ' 00:00:00';
      if (!empty($parameters['time']) && $parameters['time'] !== 'all') {
        $date_string = $parameters['date'] . ' ' . $parameters['time'];
      }
      $conditions['from'] = strtotime($date_string);
      $conditions['to'] = strtotime($parameters['date'] . ' next day');
    }

    // Fetch session occurrences.
    $session_instances = $this->sessionInstanceManager->getSessionInstancesByParams($conditions);

    return $session_instances;
  }

  /**
   * Build results.
   */
  public function buildResults($parameters) {
    $session_instances = $this->getSessions($parameters);
    $content = [];

    $room_options = $this->getPhysicalLocationOptions();
    $room = FALSE;
    if ($parameters['room'] !== 'all' && !empty($room_options[$parameters['room']])) {
      $room = $room_options[$parameters['room']];
    }
    $title_date = DrupalDateTime::createFromFormat('m/d/Y', $parameters['date']);
    $title_date = $title_date->format('F j, Y');
    // Default results title.
    $title_results = $this->t('Classes and Activities for %date', ['%date' => $title_date]);

    foreach ($session_instances as $session_instance) {
      $session = $session_instance->session->referencedEntities();
      $session = reset($session);
      // Physical Location text for Room / Area.
      $physical_location_text = FALSE;
      if ($physical_location_text_items = $session->field_physical_location_text->getValue()) {
        $physical_location_text = reset($physical_location_text_items)['value'];
      }
      // Filter out sessions by room.
      if ($room && $room !== $physical_location_text) {
        continue;
      }
      // Check for class arg.
      $classOptions = $this->getClassOptions();
      if ($parameters['class'] !== 'all' && !empty($classOptions[$parameters['class']])) {
        $class = $this->entityTypeManager->getStorage('node')->load($parameters['class']);
      }
      else {
        $class = $session_instance->class->referencedEntities();
        $class = reset($class);
      }
      // Included in membership logic.
      $included_in_membership = TRUE;
      if ($member_price_items = $session->field_session_mbr_price->getValue()) {
        $member_price = (float) reset($member_price_items)['value'];
        if ($member_price) {
          $included_in_membership = FALSE;
        }
      }
      // Ticket required logic.
      $ticket_required = FALSE;
      if ($ticket_required_items = $session->field_session_ticket->getValue()) {
        $ticket_required = (int) reset($ticket_required_items)['value'];
      }
      if ($parameters['display'] && !is_null($parameters['display'])) {
        $timestamp = DrupalDateTime::createFromTimestamp($session_instance->getTimestamp());
        $day = $timestamp->format('D n/j/Y');
        $time_from = $timestamp->format('g:i a');

        $timestamp_to = DrupalDateTime::createFromTimestamp($session_instance->getTimestampTo());
        $day_to = $timestamp_to->format('n/j/Y');
        $time_to = $timestamp_to->format('g:i a');

        $time = $time_from;
        if ($time_from !== $time_to) {
          $time .= ' - ' . $time_to;
        }

        // Set day from on first session.
        if (!isset($day_from)) {
          $day_from = $timestamp->format('n/j/Y');
        }

        $title_results = $this->t('Classes and Activities from %from to %to', [
          '%from' => $day_from,
          '%to' => $day_to,
        ]);

        $content[$day][$time] = [
          'label' => $class->getTitle(),
          'time' => $time,
          'time_from' => $session_instance->getTimestamp(),
          'room' => $physical_location_text,
          'description' => strip_tags(text_summary($class->field_class_description->value, $class->field_class_description->format, 140)),
          'included_in_membership' => $included_in_membership,
          'ticket_required' => $ticket_required,
          'url' => Url::fromUri('internal:/node/' . $class->id(), [
            'query' => [
              'location' => $session_instance->location->target_id,
              'session' => $session_instance->session->target_id,
            ],
          ]),
        ];
      }
      else {
        $timestamp = DrupalDateTime::createFromTimestamp($session_instance->getTimestamp());
        $day = $timestamp->format('D n/j/Y');
        $time_from = $timestamp->format('g:i a');
        $timestamp_to = DrupalDateTime::createFromTimestamp($session_instance->getTimestampTo());
        $time_to = $timestamp_to->format('g:i a');
        $time = $time_from;
        if ($time_from !== $time_to) {
          $time .= ' - ' . $time_to;
        }

        $hour = $timestamp->format('g');
        $minutes = $timestamp->format('i');
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
        $rounded_time = $hour . ':' . $minute . ' ' . $timestamp->format('a');
        $content[$rounded_time][$session_instance->session->target_id] = [
          'label' => $class->getTitle(),
          'description' => strip_tags(text_summary($class->field_class_description->value, $class->field_class_description->format, 140)),
          'included_in_membership' => $included_in_membership,
          'room' => $physical_location_text,
          'ticket_required' => $ticket_required,
          'url' => Url::fromUri('internal:/node/' . $class->id(), [
            'query' => [
              'location' => $session_instance->location->target_id,
              'session' => $session_instance->session->target_id,
            ],
          ]),
        ];
      }
    }

    $formatted_results = [
      '#theme' => $this->getDisplay(),
      '#title' => $title_results,
      '#content' => $content,
    ];

    return $formatted_results;
  }

  /**
   * Custom ajax callback.
   */
  public function rebuildAjaxCallback(array &$form, FormStateInterface $form_state) {
    $parameters = $form_state->getUserInput();
    // Remove empty/NULL display.
    if (empty($parameters['display'])) {
      unset($parameters['display']);
    }
    else {
      unset($parameters['time']);
    }
    $formatted_results = self::buildResults($parameters);
    $filters = self::buildFilters($parameters);
    $alerts = self::buildAlerts($parameters);
    $branch_hours = self::buildBranchHours($parameters);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#schedules-search-form-wrapper #edit-selects', $form['selects']));
    $response->addCommand(new HtmlCommand('#schedules-search-form-wrapper .results', $formatted_results));
    $response->addCommand(new HtmlCommand('#schedules-search-form-wrapper .filters-container', $filters));
    $response->addCommand(new HtmlCommand('#schedules-search-form-wrapper .alerts-wrapper', $alerts));
    $response->addCommand(new HtmlCommand('#schedules-search-form-wrapper .branch-hours-wrapper', $branch_hours));
    $response->addCommand(new InvokeCommand(NULL, 'schedulesAjaxAction', [$parameters]));
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
