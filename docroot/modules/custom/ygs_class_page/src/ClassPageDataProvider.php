<?php

namespace Drupal\ygs_class_page;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\DateHelper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Provides a data for class page related blocks/services.
 */
class ClassPageDataProvider {

  use StringTranslationTrait;

  const LONG_STRING = 34;
  const DISABLE_SPACE_AVAILABLE = TRUE;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The Session Instance Manager.
   *
   * @var \Drupal\ygs_session_instance\SessionInstanceManagerInterface
   */
  protected $sessionInstanceManager;

  /**
   * Constructs a new ClassPageDataProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
    // Intentionally. This should be converted to usual DI after a PROD release.
    // session_instance.manager is a new dependency for the service.
    // The instance of session_instance.manager doesn't exist when the service
    // is created due to ygs_session_instance module is disabled.
    $session_instance_manager = \Drupal::service('session_instance.manager');
    $this->sessionInstanceManager = $session_instance_manager;
  }

  /**
   * Retrieves upcoming sessions for the class in context of location.
   *
   * @param int $class_id
   *   Class node id.
   * @param int $location_id
   *   Location (branch)/Camp node id.
   * @param int $session_id
   *   Session node id to be set first.
   *
   * @return array
   *   Array of upcoming sessions for provided class and location.
   */
  public function getUpcomingSessions($class_id, $location_id, $session_id = 0) {
    if (!$sessions = $this->getUpcomingSessionsList($class_id, $location_id, $session_id)) {
      return [];
    }

    /** @var \Drupal\node\NodeInterface $class_node */
    $class_node = $this->entityTypeManager->getStorage('node')->load($class_id);
    $type = $class_node->field_type->value;

    if ($type == 'flexreg') {
      $summary = $this->sessionsFlexregSummary($sessions, $class_node);
      array_unshift($sessions, $summary);
    }

    return $sessions;
  }

  /**
   * Calculates Flexreg class sessions summary.
   *
   * @param array $sessions
   *   Array of nodes.
   * @param NodeInterface $class_node
   *   The class node.
   *
   * @return array
   *   Flexreg session summary.
   */
  public function sessionsFlexregSummary(array $sessions, NodeInterface $class_node) {
    $from = '';
    $to = '';
    $ticket_required = FALSE;
    $std_reg_earliest = $std_reg_latest = NULL;
    $online_reg_earliest = $online_reg_latest = NULL;

    foreach ($sessions as $session) {
      $_s = $this->sessionInstanceManager->loadSessionSchedule($session);
      $session_data = $this->sessionInstanceManager->getSessionData($session);

      if (empty($from)) {
        $from = $_s['from'];
      }
      else {
        $from = min($_s['from'], $from);
      }
      if (empty($to)) {
        $to = $_s['to'];
      }
      else {
        $to = max($_s['to'], $to);
      }

      // Standard registration date.
      if ($session->hasField('field_standard_registration_date')) {
        if ($items = $session->field_standard_registration_date->getValue()) {
          if (!$std_reg_earliest || $std_reg_earliest > $session->field_standard_registration_date->first()->get('value')->getDateTime()->getTimestamp()) {
            // Set earliest standard registration date.
            $std_reg_earliest = $session->field_standard_registration_date->first()->get('value')->getDateTime()->getTimestamp();
          }
          if (!$std_reg_latest || $std_reg_latest < $session->field_standard_registration_date->first()->get('end_value')->getDateTime()->getTimestamp()) {
            // Set latest standard registration date.
            $std_reg_latest = $session->field_standard_registration_date->first()->get('end_value')->getDateTime()->getTimestamp();
          }
        }
      }

      // Online registration date.
      if ($session->hasField('field_online_registration_date')) {
        if ($items = $session->field_session_online->getValue()) {
          if ($_online_registration_value = $session->field_online_registration_date->first()) {
            if (!$online_reg_earliest || $online_reg_earliest > $_online_registration_value->get('value')->getDateTime()->getTimestamp()) {
              // Set earliest online registration date.
              $online_reg_earliest = $_online_registration_value->get('value')->getDateTime()->getTimestamp();
            }
            if (!$online_reg_latest || $online_reg_latest < $_online_registration_value->get('end_value')->getDateTime()->getTimestamp()) {
              // Set latest online registration date.
              $online_reg_latest = $_online_registration_value->get('end_value')->getDateTime()->getTimestamp();
            }
          }
        }
      }

      if ($session->field_session_ticket->value) {
        $ticket_required = TRUE;
      }
    }

    // Close registration if sales not started or closed.
    $online_registration = 1;
    if (($std_reg_earliest && $std_reg_earliest > time()) || ($std_reg_latest && $std_reg_latest < time())) {
      $online_registration = 0;
    }
    if (($online_reg_earliest && $online_reg_earliest > time()) || ($online_reg_latest && $online_reg_latest < time())) {
      $online_registration = 0;
    }

    $schedule = [
      'from' => $from,
      'to' => $to,
    ];

    $formatted_from = date('M j, Y', strtotime($schedule['from']));
    $formatted_to = date('M j, Y', strtotime($schedule['to']));
    if ($formatted_from == $formatted_to) {
      $schedule['dates'][0]['deadline'] = t('@date', [
        '@date' => $formatted_to,
      ]);
    }
    else {
      $schedule['dates'][0]['deadline'] = t('@from - @to', [
        '@from' => $formatted_from,
        '@to' => $formatted_to,
      ]);
    }

    $summary = [
      'name' => 'Summary',
      'nid' => -1,
      'schedule' => $schedule,
      'location' => [],
      'facility' => [],
      'online_registration' => $online_registration,
      'spots_available' => '',
      'spots_available_value' => 0,
      'member_price' => '',
      'prerequisites' => 0,
      'non_member_price' => '',
      'ticket_required' => $ticket_required,
      'register_url' => '',
      'price' => 0,
      'std_reg_begins' => ($std_reg_earliest) ? DrupalDateTime::createFromTimestamp($std_reg_earliest)->format('m/d/Y') : '',
      'std_reg_ends' => ($std_reg_latest) ? DrupalDateTime::createFromTimestamp($std_reg_latest)->format('m/d/Y') : '',
      'online_reg_begins' => ($online_reg_earliest) ? DrupalDateTime::createFromTimestamp($online_reg_earliest)->format('m/d/Y') : '',
      'online_reg_ends' => ($online_reg_latest) ? DrupalDateTime::createFromTimestamp($online_reg_latest)->format('m/d/Y') : '',
      'age_categories' => '',
    ];

    // Flexreg registration url.
    if ($class_node->hasField('field_url')) {
      if ($items = $class_node->field_url->getValue()) {
        $summary['register_url'] = (Url::fromUri(reset($items)['uri'])->toString(TRUE)->getGeneratedUrl());
      }
    }

    // Tier 1 price.
    if ($class_node->hasField('field_tier_1')) {
      if ($items = $class_node->field_tier_1->getValue()) {
        $summary['tier_1'] = '$' . number_format(reset($items)['value'], 2);
      }
    }

    // Tier 2 price.
    if ($class_node->hasField('field_tier_2')) {
      if ($items = $class_node->field_tier_2->getValue()) {
        $summary['tier_2'] = '$' . number_format(reset($items)['value'], 2);
      }
    }

    // Tier 3 price.
    if ($class_node->hasField('field_tier_3')) {
      if ($items = $class_node->field_tier_3->getValue()) {
        $summary['tier_3'] = '$' . number_format(reset($items)['value'], 2);
      }
    }

    // Flexreg price.
    if ($class_node->hasField('field_price')) {
      if ($items = $class_node->field_price->getValue()) {
        $summary['price'] = '$' . number_format(reset($items)['value'], 2);
      }
    }

    // Add Location data for FlexReg from 1st session.
    $_session = reset($sessions);

    // Location.
    if ($location = $_session->field_session_location->entity) {
      $summary['location'] = [
        'node' => $location,
        'name' => $location->label(),
        'id' => $location->id(),
        'ticket_required_info' => $location->hasField('field_ticket_required_info') ? $location->field_ticket_required_info->value : '',
        'phone' => '',
      ];
      if ($phone_items = $location->field_location_phone->getValue()) {
        // TODO: format the phone number.
        $summary['location']['phone'] = reset($phone_items)['value'];
      }
    }

    // Facility.
    if ($physical_location_field_items = $_session->field_session_plocation->getValue()) {
      $nid = reset($physical_location_field_items)['target_id'];
      $physical_location = $this->entityTypeManager->getStorage('node')->load($nid);
      $summary['facility'] = [
        'name' => $physical_location->label(),
        'id' => $nid,
        'phone' => $physical_location->field_location_phone->value,
      ];
    }

    if (!empty($session_data['field_program_subcategory'][0])) {
      // Similar offerings url.
      $summary['similar_offerings_url'] = Url::fromUri('internal:/node/' . $session_data['field_program_subcategory'][0], [
        'query' => [
          'location' => $session_data['location'],
        ],
      ])->toString(TRUE)->getGeneratedUrl();
    }

    // Age categories.
    $age_categories_labels = [];
    if (!empty($session_data['field_age'])) {
      $age_categories = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadMultiple($session_data['field_age']);
      foreach ($age_categories as $age_category) {
        $age_categories_labels[] = $age_category->label();
      }
      $summary['age_categories'] = implode(', ', $age_categories_labels);
    }

    return $summary;
  }

  /**
   * Retrieves an array of session nodes with actual dates for given class and location.
   *
   * @param int $class_id
   *   The class node id.
   * @param int $location_id
   *   The location node id.
   * @param int $session_id
   *   The session node id.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   The array of upcoming sessions node.
   */
  public function getUpcomingSessionsList($class_id, $location_id = 0, $session_id = 0) {
    $sessions = [];

    $conditions = [
      'class' => $class_id,
      'from' => time(),
    ];
    if ($location_id) {
      $conditions['location'] = $location_id;
    }

    $upcoming_session_instances = $this->sessionInstanceManager->getSessionInstancesByParams($conditions);
    foreach ($upcoming_session_instances as $session_instance) {
      $nid = $session_instance->session->target_id;
      if (!isset($sessions[$nid])) {
        $sessions[$nid] = $nid;
      }
    }

    if ($sessions) {
      $sessions = $this->entityTypeManager->getStorage('node')->loadMultiple($sessions);
      $sessions = $this->sortSessions($sessions, $session_id);
    }

    return $sessions;
  }

  /**
   * Retrieves all session occurrence for provided time range.
   *
   * @param array $session_schedule
   *   The session schedule array.
   * @param int $from_timestamp
   *   The timestamp.
   * @param int $to_timestamp
   *   The timestamp.
   *
   * @return array
   *   Data for all sessions occurrences.
   */
  public function getSessionOccurrence(array $session_schedule, $from_timestamp, $to_timestamp) {
    $session_occurrences = [];
    $from_date = new \DateTime();
    $from_date->setTimestamp($from_timestamp);
    $to_date = new \DateTime();
    $to_date->setTimestamp($to_timestamp);
    $datePeriod = new \DatePeriod(
      $from_date,
      new \DateInterval('P1D'),
      $to_date
    );
    foreach ($session_schedule['dates'] as $schedule_item) {
      // Skip expired schedule items.
      $end_timestamp = strtotime($schedule_item['period']['to'] . ' +1day');
      if ($end_timestamp < $to_timestamp) {
        continue;
      }
      // Skip non-actual schedule items.
      if (!$schedule_item['actual']) {
        continue;
      }
      // Check if each day from provided period match some day when session available.
      foreach ($datePeriod as $date) {
        // Check against exclusions.
        $exclude = FALSE;
        foreach ($session_schedule['exclusions'] as $exclusion) {
          if (
            strtotime($date->format('Y/m/d') . 'T' . $schedule_item['time']['from']) >= strtotime($exclusion['from']) &&
            strtotime($date->format('Y/m/d') . 'T' . $schedule_item['time']['to']) <= strtotime($exclusion['to'])) {
            $exclude = TRUE;
          }
        }
        if (!$exclude && in_array(strtolower($date->format('l')), $schedule_item['days'])) {
          if ($date->getTimestamp() >= strtotime($schedule_item['period']['from']) && $date->getTimestamp() <= strtotime($schedule_item['period']['to'])) {
            $session_occurrences[] = [
              'timestamp' => $date->getTimestamp(),
              'time_from' => $schedule_item['time']['from'],
              'time_to' => $schedule_item['time']['from'],
            ];
          }
        }
      }
    }
    return $session_occurrences;
  }

  /**
   * Sorts sessions by their recurring rules.
   *
   * @param array $sessions
   *   Array of nodes.
   * @param int $session_id
   *   The id of the session, that should be set first.
   *
   * @return array
   *   Sorted array of nodes.
   */
  public function sortSessions(array $sessions, $session_id = 0) {
    $sorted_sessions = $sessions;

    array_walk($sorted_sessions, function (&$session) {
      $session_instance = $this
        ->sessionInstanceManager
        ->getClosestUpcomingSessionInstanceBySession($session, time());
      $session = [
        'session' => $session,
        'id' => $session->id(),
        'timestamp' => $session_instance->getTimestamp(),
      ];
    });

    uasort($sorted_sessions, function ($a, $b) use ($session_id) {
      if ($session_id && in_array($session_id, [$a['id'], $b['id']])) {
        return $a['id'] == $session_id ? -1 : 1;
      }
      return $a['timestamp'] - $b['timestamp'];
    });

    array_walk($sorted_sessions, function (&$session) {
      $session = $session['session'];
    });

    return $sorted_sessions;
  }

  /**
   * Retrieves locations for the class which have upcoming sessions.
   *
   * @param int $class_id
   *   ID of Class node.
   *
   * @return array
   *   Array of branch/camp nodes.
   */
  public function getAvailableLocations($class_id) {
    $locations = [];

    $conditions = [
      'class' => $class_id,
      'from' => time(),
    ];
    $upcoming_session_instances = $this->sessionInstanceManager->getSessionInstancesByParams($conditions);

    $nids = [];
    foreach ($upcoming_session_instances as $session_instance) {
      $location_id = $session_instance->location->target_id;
      $nids[$location_id] = $location_id;
    }

    if ($nids) {
      $locations = $this->entityTypeManager
        ->getStorage('node')
        ->loadMultiple($nids);
    }

    return $locations;
  }

  /**
   * Formats sessions.
   *
   * @param array $sessions
   *   Array of nodes.
   *
   * @return array
   *   Array of formatted sessions
   */
  public function formatSessions(array $sessions) {
    $formattedSession = [];
    foreach ($sessions as $_session) {
      if (!$_session instanceof NodeInterface) {
        $formattedSession[] = $_session;
        continue;
      }
      $session = [
        'name' => html_entity_decode($_session->label()),
        'nid' => $_session->id(),
        'schedule' => $this->sessionInstanceManager->loadSessionSchedule($_session),
        'location' => [],
        'facility' => [],
        'online_registration' => 1,
        'spots_available' => '',
        'spots_available_value' => 0,
        'member_price' => '',
        'member_price_value' => 0,
        'prerequisites' => 0,
        'non_member_price' => '',
        'ticket_required' => 0,
        'register_url' => '',
        'age_categories' => '',
        'single_day' => 0,
      ];

      $online_registration = FALSE;

      // Online registration.
      if ($online_registration_items = $_session->field_session_online->getValue()) {
        $online_registration = (boolean) reset($online_registration_items)['value'];
      }

      // Ticket required.
      if ($ticket_required_items = $_session->field_session_ticket->getValue()) {
        $session['ticket_required'] = (int) reset($ticket_required_items)['value'];
        if ($session['ticket_required']) {
          $online_registration = FALSE;
        }
      }

      // Allow waitlist.
      if ($allow_waitlist_items = $_session->field_allow_waitlist->getValue()) {
        $allow_waitlist = (boolean) reset($allow_waitlist_items)['value'];
      }

      // Spots allowed.
      $spots_allowed = 0;
      if ($spots_allowed_items = $_session->field_spots_allowed->getValue()) {
        $spots_allowed = (int) reset($spots_allowed_items)['value'];
        $session['spots_allowed_value'] = $spots_allowed;
      }

      // Spots available.
      $session['join_waitlist'] = FALSE;
      if (isset($spots_allowed) && isset($allow_waitlist) && $spots_available_items = $_session->field_spots_available->getValue()) {
        $spots_available = (int) reset($spots_available_items)['value'];
        $session['spots_available_value'] = $spots_available;
        if ($spots_allowed > 0) {
          if ($spots_available > 0) {
            $session['spots_available'] = $this->formatPlural($spots_available, '1 spot available', '@count spots available');
          }
          if ($spots_available <= 0) {
            $session['spots_available'] = t('No spots currently available');
            if ($allow_waitlist) {
              $session['join_waitlist'] = TRUE;
            }
            else {
              $online_registration = FALSE;
            }
          }
        }
        if ($spots_allowed == 0) {
          $session['spots_available'] = t('Space available');
        }
      }

      if (self::DISABLE_SPACE_AVAILABLE) {
        // Remove Space Available info.
        $session['spots_available'] = NULL;
      }

      // Close registration if sales not started or closed.
      if ($_session->field_sales_date->getValue() && $online_registration) {
        $sales_start = strtotime($_session->field_sales_date->first()->get('value')->getValue() . 'Z');
        $sales_end = strtotime($_session->field_sales_date->first()->get('end_value')->getValue() . 'Z');
        if ($sales_start > time() || $sales_end < time()) {
          $online_registration = FALSE;
        }
      }

      // Close registration if online registration not started or closed.
      if ($_session->field_online_registration_date->getValue() && $online_registration) {
        $online_start = strtotime($_session->field_online_registration_date->first()->get('value')->getValue() . 'Z');
        $online_end = strtotime($_session->field_online_registration_date->first()->get('end_value')->getValue() . 'Z');
        if ($online_start > time() || $online_end < time()) {
          $online_registration = FALSE;
        }
      }

      // Prerequisites.
      if ($prerequisites_items = $_session->field_prerequisite->getValue()) {
        $session['prerequisites'] = (int) reset($prerequisites_items)['value'];
      }

      // Member price.
      $session['member_price'] = $this->t('Included');
      if ($member_price_items = $_session->field_session_mbr_price->getValue()) {
        $member_price = (float) reset($member_price_items)['value'];
        $session['member_price_value'] = $member_price;
        if ($member_price) {
          $session['member_price'] = '$' . number_format($member_price, 2);
        }
      }
      $session['online_registration'] = $online_registration;

      // Non-member price.
      if ($non_member_price_items = $_session->field_session_nmbr_price->getValue()) {
        $non_member_price = (float) reset($non_member_price_items)['value'];
        $session['non_member_price'] = '$' . number_format($non_member_price, 2);
      }

      // Location.
      if ($location = $_session->field_session_location->entity) {
        $session['location'] = [
          'bundle' => $location->bundle(),
          'name' => $location->label(),
          'id' => $location->id(),
          'ticket_required_info' => $location->hasField('field_ticket_required_info') ? $location->field_ticket_required_info->value : '',
          'phone' => '',
        ];
        if ($phone_items = $location->field_location_phone->getValue()) {
          // TODO: format the phone number.
          $session['location']['phone'] = reset($phone_items)['value'];
        }
      }

      // Facility.
      if ($physical_location_field_items = $_session->field_session_plocation->getValue()) {
        $nid = reset($physical_location_field_items)['target_id'];
        $physical_location = $this->entityTypeManager->getStorage('node')->load($nid);
        $session['facility'] = [
          'name' => $physical_location->label(),
          'id' => $nid,
          'phone' => $physical_location->field_location_phone->value,
        ];
      }

      // Facility Text.
      if ($physical_location_text_items = $_session->field_physical_location_text->getValue()) {
        $session['facility']['text'] = $physical_location_text_items[0]['value'];
      }

      // Description.
      if ($field_body_no_summary = $_session->field_session_description->getValue()) {
        $session['description'] = html_entity_decode(strip_tags(text_summary($field_body_no_summary[0]['value'], $field_body_no_summary[0]['format'])));
      }

      // Registration url.
      if ($_session->hasField('field_session_reg_link')) {
        if ($items = $_session->field_session_reg_link->getValue()) {
          $title = !empty($items[0]['title']) ? $items[0]['title'] : t('Register now');
          $title = $session['join_waitlist'] ? t('Join Waitlist') : $title;
          $session['register_url'] = [
            'url' => Url::fromUri(reset($items)['uri'])->toString(TRUE)->getGeneratedUrl(),
            'title' => $title,
          ];
        }
      }

      // All schedules url.
      $session_data = $this->sessionInstanceManager->getSessionData($_session);
      $session['similar_offerings_url'] = Url::fromUri('internal:/schedules', [
        'query' => [
          'location' => $session_data['location'],
          'program' => $session_data['field_program'][0],
          'category' => $session_data['field_program_subcategory'][0],
        ],
      ])->toString(TRUE)->getGeneratedUrl();

      // More information URL.
      if ($class_items = $_session->field_session_class->getValue()) {
        $nid = reset($class_items)['target_id'];
        $class_node = $this->entityTypeManager->getStorage('node')->load($nid);
        $session['more_info_url'] = Url::fromUri('internal:/node/' . $class_node->id(), [
          'query' => [
            'session' => $_session->id(),
            'location' => $_session->field_session_location->target_id,
          ],
        ])->toString(TRUE)->getGeneratedUrl();
      }

      foreach ($session['schedule']['dates'] as &$date) {
        $date['days_formatted'] = self::formatDays($date['days']);

        foreach ($date['time'] as &$time) {
          $date['time_formatted'][] = date('g:iA', strtotime('2000-01-01T' . $time));
        }
        $date['time_formatted'] = implode(' – ', $date['time_formatted']);

        $formatted_from = date('M j, Y', strtotime($date['period']['from']));
        $formatted_to = date('M j, Y', strtotime($date['period']['to']));

        if ($formatted_from == $formatted_to) {
          $date['deadline'] = t('@date', ['@date' => $formatted_to]);
          $date['deadline_short'] = $date['deadline'];
          $session['single_day'] = 1;
        }
        else {
          $date['deadline'] = t('@from - @to', [
            '@from' => $formatted_from,
            '@to' => $formatted_to,
          ]);
        }
      }

      // Age categories.
      $age_categories_labels = [];
      if (!empty($session_data['field_age'])) {
        $age_categories = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->loadMultiple($session_data['field_age']);
        foreach ($age_categories as $age_category) {
          $age_categories_labels[] = $age_category->label();
        }
        $session['age_categories'] = implode(', ', $age_categories_labels);
      }

      $formattedSession[] = $session;
    }

    return $formattedSession;
  }

  /**
   * Formats schedule weekdays.
   *
   * 1. Single day just shows the day.
   * 2. If there are three or more consecutive days use first day “thru” last
   *    day.
   * 3. Use commas to separate days that aren’t three or more consecutive days.
   *    E.G. one possible example is "Monday thru Thursday, Saturday"
   * 4. Week days should be shortened in case results string is too long.
   *    Monday - Mon, Tuesday - Tue and so on.
   *
   * @param array $days
   *   Array of weekdays.
   *
   * @return string
   *   Formatted weekdays.
   */
  public function formatDays(array $days) {
    array_walk($days, function (&$item) {
      $item = Unicode::ucfirst(Unicode::strtolower($item));
    });
    $weekdays_unt = DateHelper::weekDaysUntranslated();
    $week = array_combine($weekdays_unt, array_fill(0, 7, 0));

    foreach ($days as $day) {
      $week[$day] = 1;
    }

    $series = self::getDaysSeries(array_values($week));

    ksort($series);
    $output = self::formatDaysSeries($series);
    if (Unicode::strlen($output) > self::LONG_STRING) {
      // String is too long.
      $output = self::formatDaysSeries($series, TRUE);
    }

    return $output;
  }

  /**
   * Calculates days series on given weekdays.
   *
   * @param array $week
   *   Array, representing the choosen weekdays.
   *
   * @return array
   *   Days series, associative array (key is the first day, value is a length
   *   of the serie).
   */
  private function getDaysSeries(array $week) {
    $series = [];

    $longest = 1;
    while ($longest) {
      $longest = 0;
      $longest_start = 0;
      $start = 0;
      $length = 1;

      // Search for longest sequence of '1'.
      for ($i = 1; $i < 14; $i++) {
        $curr = $week[$i % 7];
        $prev = $week[($i - 1) % 7];

        if ($curr && $prev) {
          $length++;
          if ($length > $longest) {
            $longest_start = $start;
            $longest = $length;
          }
        }
        elseif ($prev) {
          if ($length > $longest) {
            $longest_start = $start;
            $longest = $length;
          }
        }
        elseif ($curr) {
          $start = $i;
          $length = 1;
        }
      }

      // Sequence found.
      if ($longest) {
        // Remove form the haystack.
        foreach (range($longest_start, $longest_start + $longest - 1) as $i) {
          $week[$i] = 0;
          $week[($i + 7) % 14] = 0;
        }
        if ($longest > 2) {
          $series[$longest_start] = $longest;
        }
        else {
          // It breaks sequences of 2 items in 2 series of 1 item.
          foreach (range($longest_start, $longest_start + $longest - 1) as $i) {
            $series[$i % 7] = 1;
          }
        }

      }
    }

    return $series;
  }

  /**
   * Formats days series.
   *
   * @param array $series
   *   Days series, associative array (keys are start days, values - lengths).
   * @param bool $shorten
   *   Flag, indicating a need to shorten weekday names.
   *
   * @return string
   *   Formatted days series.
   */
  private function formatDaysSeries(array $series, $shorten = FALSE) {

    if ($shorten) {
      $weekdays = DateHelper::weekDaysAbbr(TRUE);
    }
    else {
      $weekdays = DateHelper::weekDays(TRUE);
    }

    $output = [];
    foreach ($series as $start => $length) {
      if ($length >= 3) {
        $output[] = $this->t('@from - @to', [
          '@from' => $weekdays[$start],
          '@to' => $weekdays[($start + $length - 1) % 7],
        ]);
      }
      else {
        for ($i = 0; $i < $length; $i++) {
          $output[] = $weekdays[($start + $i) % 7];
        }
      }
    }

    return implode(', ', $output);
  }

}
