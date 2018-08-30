<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use DateTimeZone;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\node\Entity\Node;
use Drupal\activenet_sync\Utility\ActivityReference;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Flexreg DataWarehouse data type.
 *
 * @DataType(
 * id = "flexreg_dw_data",
 * label = @Translation("Flexreg Data Warehouse data"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\FlexregDwDataDefinition"
 * )
 */
class FlexregDwData extends Map {

  use ActivityReference;

  /**
   * Validation status.
   *
   * Default - 'ok'
   * If session or class not created - 'failed'
   * If has any validation errors - 'detached'
   *
   * @var string
   */
  protected $status = 'ok';

  protected $validationErrors = [];

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
    }

    // Clean data for non-required fields when provided data is an empty string.
    $PropertyDef = $this->getDataDefinition()->getPropertyDefinitions();
    $values = array_filter($values,
      function ($key) use ($values, $PropertyDef) {
        if (isset($PropertyDef[$key]) && !$PropertyDef[$key]->isRequired() && $values[$key] === '') {
          return FALSE;
        }
        return TRUE;
      },
      ARRAY_FILTER_USE_KEY
    );

    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $validatation = $this->getTypedDataManager()->getValidator()->validate($this);
    if ($validatation->has(0)) {
      foreach ($validatation->getIterator() as $violation) {
        $this->setValidationError([
          'propertyPath' => $violation->getPropertyPath(),
          'invalidValue' => $violation->getInvalidValue(),
          'message' => $violation->getMessage()->render(),
        ], NULL);
      }
    }
    return $this->getValidationStatus();
  }

  /**
   * Change Validation Status.
   *
   * @param bool $status
   *   Valid or not.
   */
  public function setValidationStatus($status) {
    $this->status = $status;
  }

  /**
   * Get Validation Status.
   */
  public function getValidationStatus() {
    return $this->status;
  }

  /**
   * Change Validation Status.
   *
   * @param string $error
   *   Error message.
   * @param string $status
   *   Validation status.
   */
  public function setValidationError($error, $status = 'detached') {
    $this->validationErrors[] = $error;
    if ($status) {
      $this->setValidationStatus($status);
    }
  }

  /**
   * Get errors.
   *
   * @param bool $json
   *   Return result as json.
   *
   * @return array|string|null
   *   Errors
   */
  public function getValidationErrors($json = FALSE) {
    $errors = $this->validationErrors;
    if ($json) {
      $errors = json_encode($errors);
    }
    return (!empty($errors)) ? $errors : NULL;
  }

  /**
   * Create class node.
   */
  public function generateClass($node) {
    if ($node && $this->isClassUnchanged($node)) {
      // Skip updating if class base field not changed.
      return $node->id();
    }
    if (!$node) {
      $node = Node::create([
        'type' => 'class',
        'uid' => 1,
        'status' => 1,
        'moderation_state' => 'published',
        'field_type' => 'flexreg',
      ]);
    }
    $node->set('moderation_state', 'published');
    $node->set('title', $this->get('program_name')->getValue());
    $description = $this->get('program_description')->getValue();
    if ($description) {
      $node->set('field_class_description', [
        'value' => $this->get('program_description')->getValue(),
        'format' => 'full_html',
      ]);
    }
    $node->set('field_external_id', $this->get('program_id')->getValue());
    $node->set('field_url', $this->getClassUrl());
    $node->set('field_price', $this->getFlexRegPrice());
    $node->set('field_class_activity', NULL);
    foreach ($this->getActivitiesId() as $target_id) {
      $node->field_class_activity->appendItem($target_id);
    }
    $node->save();
    if (!$node->id()) {
      $this->setValidationError(['message' => 'Could not create class node'], 'failed');
      return NULL;
    }
    return $node->id();
  }

  /**
   * Create session node.
   */
  public function generateSession($class_id, $node) {
    if (!$node) {
      $node = Node::create([
        'type' => 'session',
        'uid' => 1,
        'status' => 1,
        'moderation_state' => 'published',
        'field_sales_status' => 'open',
        'field_session_online' => TRUE,
      ]);
    }
    else {
      // Delete existing field_session_time items.
      $dates = $node->field_session_time->referencedEntities();
      foreach ($dates as $session_time_item) {
        $session_time_item->delete();
      }
      // Empty the field.
      $node->set('field_session_time', []);
    }
    $node->set('moderation_state', 'published');
    $node->set('title', $this->get('session_name')->getValue());
    $node->set('field_session_class', ['target_id' => $class_id]);
    $node->set('field_external_id', $this->getSessionExternalId());
    $node->set('field_session_max_age', $this->get('agesmax')->getCastedValue());
    $node->set('field_session_min_age', $this->get('agesmin')->getCastedValue());
    $node->set('field_session_gender', $this->getSessionGender());
    $node->set('field_session_location', ['target_id' => $this->getLocationId()]);
    $node->set('field_session_plocation', ['target_id' => $this->getFacilityId()]);
    $node->set('field_session_reg_link', [$this->getSessionLink()]);
    $node->set('field_session_description', [
      'value' => $this->get('session_description')->getValue(),
      'format' => 'full_html',
    ]);
    $node->set('field_online_registration_date', [
      'value' => $this->getDate('online_reg'),
      'end_value' => $this->getDate('online_reg_end'),
    ]);
    $node->set('field_standard_registration_date', [
      'value' => $this->getDate('standard_reg'),
      'end_value' => $this->getDate('standard_reg_end'),
    ]);

    $session_time = Paragraph::create([
      'type' => 'session_time',
      'parent_id' => $node->id(),
      'parent_type' => 'node',
      'parent_field_name' => 'field_session_time',
      'status' => 1,
      'field_session_time_actual' => 1,
      'field_session_time_days' => $this->getSessionWeekdays(),
      'field_session_time_frequency' => 'weekly',
      'field_session_time_date' => [
        'value' => $this->getSessionDate('start'),
        'end_value' => $this->getSessionDate('end'),
      ],
    ]);
    $node->set('field_session_time', [$session_time]);
    $node->save();

    return $node->id();
  }

  /**
   * Check base class fields for modifying.
   *
   * @return bool
   *   TRUE if fields values are identical.
   */
  public function isClassUnchanged($node) {
    $activities = [];
    $description = $this->get('program_description')->getValue();
    $new_activities = $this->getActivitiesId(TRUE);
    if (!$description && empty($new_activities)) {
      // Do not update class to NULL data.
      return TRUE;
    }
    $body = $node->field_class_description->value == $description;
    foreach ($node->field_class_activity as $activity_id) {
      $activities[] = $activity_id->get('target_id')->getValue();
    }
    sort($activities);
    $activity = $activities == $new_activities;
    $url = $node->field_url->first()->get('uri')->getValue() == $this->getClassUrl();
    $price = $node->field_price->value == $this->getFlexRegPrice();

    return $body && $activity && $url && $price;
  }

  /**
   * Get Activity node ID by DataWarehouse data.
   *
   * @param bool $skip_errors
   *   If set TRUE it will no log errors.
   *
   * @return array
   *   Activity node ID's.
   */
  public function getActivitiesId($skip_errors = FALSE) {
    $department = strtolower($this->get('department_name')->getValue());
    $category = strtolower($this->get('category_name')->getValue());
    $subcategory = strtolower($this->get('sub_category_name')->getValue());
    if (!$department && !$category && !$subcategory) {
      $this->setValidationError([
        'message' => 'Empty values from DataWarehouse: department_name, category_name, sub_category_name',
      ]);
      return [];
    }
    $activity_ids = $this->getActivityReference($category, $department, $subcategory);

    if (empty($activity_ids) && !$skip_errors) {
      $this->setValidationError([
        'department_name' => $department,
        'category_name' => $category,
        'sub_category_name' => $subcategory,
        'message' => 'Activity not found',
      ]);
    }
    sort($activity_ids);
    return $activity_ids;
  }

  /**
   * Get FlexReg Registration URL.
   *
   * @return string
   *   FlexReg Registration URL.
   */
  public function getClassUrl() {
    $program_id = $this->get('program_id')->getValue();
    return "https://apm.activecommunities.com/seattleymca/ActiveNet_Home?FileName=onlineDCProgramDetail.sdi&dcprogram_id=$program_id&online=true";
  }

  /**
   * Get minPriceAmt from assetPrices by type.
   *
   * @return null|float
   *   MinPriceAmt.
   */
  public function getFlexRegPrice() {
    $prices = [];
    foreach ($this->get('costs') as $item) {
      $prices[] = $item->get('FEEAMOUNT')->getCastedValue();
    }
    foreach ($this->get('fee_amounts') as $item) {
      $fees[] = $item->get('FEEAMOUNT')->getCastedValue();
    }
    $price = (!empty($prices)) ? min($prices) : 0;
    $fee = (!empty($fees)) ? min($fees) : 0;
    return $price + $fee;
  }

  /**
   * Get node ID by placeName.
   *
   * @return int|null
   *   Branch|camp node ID.
   */
  public function getLocationId() {
    $place = $this->get('location')->getValue();
    $location = NULL;
    if (!empty($place)) {
      $location = \Drupal::service('activenet_sync.repository')->getLocationByPlaceName($place, ['branch', 'camp']);
    }
    else {
      $this->setValidationError([
        'message' => 'Empty location name',
      ]);
      return NULL;
    }
    if (!$location) {
      $this->setValidationError([
        'placeName' => $place,
        'message' => 'Location not found',
      ]);
    }
    return ($location) ? $location->id() : NULL;
  }

  /**
   * Get facility node ID by FACILITYNAME.
   *
   * @return int|null
   *   Facility node ID.
   */
  public function getFacilityId() {
    $facility_name = $this->get('physical_location')->getValue();
    $facility = ($facility_name) ? \Drupal::service('activenet_sync.repository')->getFacilityByName($facility_name) : NULL;
    return ($facility) ? $facility->id() : NULL;
  }

  /**
   * Get Session Link.
   *
   * @return string
   *   Session Link.
   */
  public function getSessionLink() {
    // Get activity id from assetLegacyData:substitutionUrl.
    $id = $this->get('program_id')->getValue();
    return [
      'title' => t('Register Now'),
      'uri' => "https://apm.activecommunities.com/seattleymca/ActiveNet_Home?FileName=onlineDCProgramDetail.sdi&dcprogram_id=$id&online=true",
    ];
  }

  /**
   * Get Session Weekdays.
   *
   * @return array
   *   Weekdays.
   */
  public function getSessionWeekdays() {
    $weekdays_values = str_split($this->get('weekdays')->getValue());
    $weekdays_keys = [
      'sunday',
      'monday',
      'tuesday',
      'wednesday',
      'thursday',
      'friday',
      'saturday',
    ];
    $weekdays = array_combine($weekdays_keys, $weekdays_values);
    $active_weekdays = array_filter($weekdays, function ($elem) {
      return $elem == 1;
    });
    return array_keys($active_weekdays);
  }

  /**
   * Get Session date.
   *
   * @param string $type
   *   Date type (start|end)
   *
   * @return string
   *   UTC datetime.
   */
  public function getSessionDate($type) {
    $date = $this->get($type . '_date')->getDateTime();
    $time = $this->get($type . '_time')->getDateTime();
    if ($date && $time) {
      $date->setTime((int) $time->format('H'), (int) $time->format('i'));
      return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s');
    }
    return NULL;
  }

  /**
   * Get Session Gender.
   *
   * @return string|null
   *   Gender list key.
   */
  public function getSessionGender() {
    $key = $this->get('program_gender')->getCastedValue();
    $values = [0 => 'coed', 1 => 'male', 2 => 'female'];
    return (isset($values[$key])) ? $values[$key] : NULL;
  }

  /**
   * Get Session External Id.
   *
   * @return string
   *   External Id.
   */
  public function getSessionExternalId() {
    $session_id = $this->get('session_id')->getValue();
    $ps_id = $this->get('ps_id')->getValue();
    return "$ps_id:$session_id";
  }

  /**
   * Get Date.
   *
   * @param string $type
   *   Date type.
   *
   * @return string
   *   UTC datetime.
   */
  public function getDate($type) {
    if ($this->get($type)->getValue() == '1899-12-30 00:00:00.000') {
      return NULL;
    }
    else {
      $date = $this->get($type)->getDateTime();
      return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s');
    }
  }

}
