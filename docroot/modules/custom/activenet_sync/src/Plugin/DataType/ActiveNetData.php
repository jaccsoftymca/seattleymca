<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use DateTimeZone;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\node\Entity\Node;
use Drupal\activenet_sync\Utility\ActivityReference;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * ActiveNet Data type.
 *
 * @DataType(
 * id = "active_net_data",
 * label = @Translation("ActiveNet Data"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\ActiveNetDefinition"
 * )
 */
class ActiveNetData extends Map {

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
        // For required fields we want to set validation status 'detached' for
        // missing or bad data.
        $status = $this->getValidationStatus();
        $property_path = explode('.', $violation->getPropertyPath());
        $active_net_field = $property_path[0];
        $property_definitions = $violation->getRoot()->definition->getPropertyDefinitions();
        // If required set status detached, else keep current status.
        if (isset($property_definitions[$active_net_field]) && $property_definitions[$active_net_field]->isRequired()) {
          $status = 'detached';
        }

        $this->setValidationError([
          'propertyPath' => $violation->getPropertyPath(),
          'invalidValue' => $violation->getInvalidValue(),
          'message' => $violation->getMessage()->render(),
        ], $status);
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
      // Skip updating if class base fields not changed.
      return $node->id();
    }
    if (!$node) {
      $node = Node::create([
        'type' => 'class',
        'uid' => 1,
        'status' => 1,
        'moderation_state' => 'published',
        'field_type' => 'activity',
      ]);
    }
    $node->set('moderation_state', 'published');
    $node->set('title', $this->getTitle());
    $description = $this->getAssetDescription();
    if ($description) {
      $node->set('field_class_description', [
        'value' => $this->getAssetDescription(),
        'format' => 'full_html',
      ]);
    }
    $external_id = $this->getClassExternalId();
    if (empty($external_id)) {
      $this->setValidationError(['message' => 'Empty external ID(tagName where type = MISCELLANEOUS)']);
    }
    $node->set('field_external_id', $external_id);
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
    $node->set('title', $this->getTitle());
    $node->set('field_session_mbr_price', $this->getActivityPrice('member'));
    $node->set('field_session_nmbr_price', $this->getActivityPrice('non-member'));
    $node->set('field_session_class', ['target_id' => $class_id]);
    $node->set('field_session_location', ['target_id' => $this->getLocationId()]);
    $node->set('field_prerequisite', $this->getPrerequisite());
    $node->set('field_external_id', $this->get('assetGuid')->getValue());
    $node->set('field_session_gender', $this->getSessionGender());
    $node->set('field_session_max_age', $this->get('regReqMaxAge')->getCastedValue());
    $node->set('field_session_min_age', $this->get('regReqMinAge')->getCastedValue());
    $node->set('field_spots_available', $this->getSpotsStatus('available'));
    $node->set('field_spots_allowed', $this->getSpotsStatus('allowed'));
    $node->set('field_session_online', $this->getSessionOnlineRegistration());
    $node->set('field_session_reg_link', [$this->getSessionLink()]);
    $node->set('field_sales_status', $this->getSessionSalesStatus());
    $node->set('field_session_ticket', (strpos($this->get('assetName')->getValue(), '*') !== FALSE) ? 1 : 0);
    $node->set('field_physical_location_text', $this->get('dwData')->get('FACILITYNAME')->getValue());
    $node->set('field_allow_waitlist', ((bool) $this->get('dwData')->get('ALLOW_WAIT_LISTING')->getCastedValue()) ? 1 : 0);
    $node->set('field_session_exclusions', []);
    $sales_func = function ($key) {
      $sales_date = NULL;
      if (!empty($sd = $this->get('dwData')->get($key))) {
        if (!is_null($sd = $sd->getDateTime())) {
          $sales_date = $sd->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s');
        }
      }
      return $sales_date;
    };
    $sales_start_date = $sales_func('sales_start_date');
    $sales_end_date = $sales_func('sales_end_date');
    $node->set('field_sales_date', []);
    if (!is_null($sales_start_date) && !is_null($sales_end_date)) {
      $node->set('field_sales_date', [
        'value' => $sales_start_date,
        'end_value' => $sales_end_date,
      ]);
    }

    if (!empty($this->get('activityRecurrences'))) {
      // Session_actual - inverted frequencyInterval.
      $session_actual = $this->getActualSession();
      // Create field_session_time values.
      $session_time_prgfs = [];
      foreach ($this->get('activityRecurrences')->getIterator() as $item) {
        // Calculate start and end time.
        $start_date = $item->get('activityStartDate')->getDateTime();
        $end_date = $item->get('activityEndDate')->getDateTime();
        if ($item->get('startTime')->getValue()) {
          $start_time = explode(':', $item->get('startTime')->getValue());
          $start_date->setTime((int) $start_time[0], (int) $start_time[1]);
        }
        if ($item->get('endTime')->getValue()) {
          $end_time = explode(':', $item->get('endTime')->getValue());
          $end_date->setTime((int) $end_time[0], (int) $end_time[1]);
        }

        $session_time_prgfs[] = Paragraph::create([
          'type' => 'session_time',
          'parent_id' => $node->id(),
          'parent_type' => 'node',
          'parent_field_name' => 'field_session_time',
          'status' => 1,
          'field_session_time_actual' => $session_actual,
          'field_session_time_days' => explode(', ', strtolower($item->get('days')
            ->getValue())),
          'field_session_time_frequency' => strtolower($item->get('frequency')
            ->get('frequencyName')
            ->getValue()),
          'field_session_time_date' => [
            'value' => $start_date->setTimezone(new DateTimeZone('UTC'))
              ->format('Y-m-d\TH:i:s'),
            'end_value' => $end_date->setTimezone(new DateTimeZone('UTC'))
              ->format('Y-m-d\TH:i:s'),
          ],
        ]);

        // Add exclusion date to session.
        foreach ($item->get('activityExclusions')
          ->getIterator() as $exclusion) {
          $node->field_session_exclusions->appendItem([
            'value' => $exclusion->get('exclusionStartDate')
              ->getDateTime()
              ->setTimezone(new DateTimeZone('UTC'))
              ->format('Y-m-d\TH:i:s'),
            'end_value' => $exclusion->get('exclusionEndDate')
              ->getDateTime()
              ->setTimezone(new DateTimeZone('UTC'))
              ->format('Y-m-d\TH:i:s'),
          ]);
        }
      }

      $node->set('field_session_time', $session_time_prgfs);
    }

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
    $description = $this->getAssetDescription();
    $new_activities = $this->getActivitiesId(TRUE);
    if (!$description && empty($new_activities)) {
      // Do not update class to NULL data.
      return TRUE;
    }
    foreach ($node->field_class_activity as $activity_id) {
      $activities[] = $activity_id->get('target_id')->getValue();
    }
    $body = $node->field_class_description->value == $description;
    sort($activities);
    $activity = $activities == $new_activities;
    return $body && $activity;
  }

  /**
   * Returns processed title.
   *
   * @return string
   *   Processed item title.
   */
  public function getTitle() {
    return trim(str_replace('Â®', '', $this->get('assetName')->getValue()));
  }

  /**
   * Get node ID by placeName.
   *
   * @return int|null
   *   Branch|camp node ID.
   */
  public function getLocationId() {
    $place = $this->get('dwData')->get('location')->getValue();
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
   * Get Activity node ID by DataWarehouse data.
   *
   * @param bool $skip_errors
   *   If set TRUE it will no log errors.
   *
   * @return array
   *   Activity node ID's.
   */
  public function getActivitiesId($skip_errors = FALSE) {
    $dw_data = $this->get('dwData');
    $department = strtolower($dw_data->get('DEPARTMENT_NAME')->getValue());
    $category = strtolower($dw_data->get('CATEGORYNAME')->getValue());
    $subcategory = strtolower($dw_data->get('SUBCATEGORYNAME')->getValue());
    if (!$department && !$category && !$subcategory) {
      $this->setValidationError([
        'message' => 'Empty values from DataWarehouse: DEPARTMENT_NAME, CATEGORYNAME, SUBCATEGORYNAME',
      ]);
      return [];
    }
    $activity_ids = $this->getActivityReference($category, $department, $subcategory);

    if (empty($activity_ids) && !$skip_errors) {
      $this->setValidationError([
        'DEPARTMENT_NAME' => $department,
        'CATEGORYNAME' => $category,
        'SUBCATEGORYNAME' => $subcategory,
        'message' => 'Activity not found',
      ]);
    }
    sort($activity_ids);
    return $activity_ids;
  }

  /**
   * Get minPriceAmt from assetPrices by type.
   *
   * @param string $type
   *   Type is 'member' or 'non-member'.
   *
   * @return null|float
   *   MinPriceAmt.
   */
  public function getActivityPrice($type = 'member') {
    $price = NULL;
    $standard_charge = NULL;
    $search_value = ($type == 'member') ? 'Member.' : 'Non-member.';
    foreach ($this->get('assetPrices') as $item) {
      if (trim($item->get('priceType')->getValue()) == 'Standard charge.') {
        $standard_charge = $item->get('priceAmt')->getCastedValue();
      }
      if (strpos($item->get('priceType')->getValue(), $search_value) !== FALSE) {
        $price = $item->get('priceAmt')->getCastedValue();
      }
    }
    return ($price) ? $price : $standard_charge;
  }

  /**
   * Get description.
   *
   * @return string
   *   Asset Description
   */
  public function getAssetDescription() {
    $description = stripslashes($this->get('dwData')->get('DESCRIPTION')->getValue());
    $description = str_replace('***CLASS SPECIFIC DESCRIPTION***', '', $description);
    return $description;
  }

  /**
   * Get prerequisites.
   *
   * @return bool
   *   Prerequisites field value
   */
  public function getPrerequisite() {
    $prerequisites = 0;
    $full_description = stripslashes($this->get('assetDescriptions')->first()->get('description')->getValue());
    if (strpos($full_description, '<h4>Prerequisites</h4>') !== FALSE) {
      $prerequisites = 1;
    }
    return $prerequisites;
  }

  /**
   * Get External Id.
   *
   * @return int|null
   *   Unique value that determines class.
   */
  public function getClassExternalId() {
    foreach ($this->get('assetTags')->getIterator() as $tag) {
      if ($tag->get('tag')->get('tagDescription')->getValue() == 'MISCELLANEOUS' && ctype_digit($tag->get('tag')->get('tagName')->getValue())) {
        return $tag->get('tag')->get('tagName')->getValue();
      }
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
    $key = strtolower($this->get('regReqGenderCd')->getValue());
    $values = ['c' => 'coed', 'm' => 'male', 'f' => 'female'];
    return (isset($values[$key])) ? $values[$key] : NULL;
  }

  /**
   * Get Online Registration status.
   *
   * @return string|null
   *   Gender list key.
   */
  public function getSessionOnlineRegistration() {
    $registration = 0;
    $sales_end_date = $this->get('dwData')->get('sales_end_date');
    $end_date = !is_null($sales_end_date) && !is_null($sales_end_date->getDateTime()) ? $sales_end_date->getDateTime()->getTimestamp() : 0;
    $start_start_date = $this->get('dwData')->get('sales_start_date');
    $start_date = !is_null($start_start_date) && !is_null($start_start_date->getDateTime()) ? $start_start_date->getDateTime()->getTimestamp() : 0;
    $online_registration = $this->get('assetLegacyData')->get('onlineRegistration')->getValue();
    if ($online_registration == 'true' || $online_registration == 1) {
      if ($end_date < time()) {
        if (empty($start_date) && empty($end_date)) {
          // Online Registration is available.
          $registration = 1;
        }
        else {
          // If expired sales end date - online registration is unavailable.
          $registration = 0;
        }
      }
      else {
        // Online Registration is available.
        $registration = 1;
      }
    }
    return $registration;
  }

  /**
   * Get Actual Session status.
   *
   * If it equals -1 the "Actual session" field should not be checked.
   * If it's 0 it should be checked.
   *
   * @return int
   *   Actual session status..
   */
  public function getActualSession() {
    if ($this->get('dwData')->get('NO_MEETING_DATES')->getValue() === NULL) {
      $this->setValidationError([
        'message' => 'Empty value from DataWarehouse: NO_MEETING_DATES',
      ], NULL);
      $session_actual = 0;
    }
    else {
      $session_actual = ($this->get('dwData')->get('NO_MEETING_DATES')->getCastedValue() == -1) ? 0 : 1;
    }
    return $session_actual;
  }

  /**
   * Get Session Link.
   *
   * @param bool $only_id
   *   If TRUE - return ID from API:assetLegacyData:substitutionUrl.
   *
   * @return string
   *   Session Link.
   */
  public function getSessionLink($only_id = FALSE) {
    // Get activity id from assetLegacyData:substitutionUrl.
    $activity_id = preg_replace('/\D/', '', $this->get('assetLegacyData')->get('substitutionUrl')->getValue());
    if ($only_id) {
      return $activity_id;
    }
    // Convert asset name to url format.
    $asset_name_formatted = preg_replace('/[^a-z0-9_]+/', '-', strtolower($this->get('assetName')->getValue()));
    return [
      'title' => t('Register Now'),
      'uri' => "https://apm.activecommunities.com/seattleymca/Activity_Search/$asset_name_formatted/$activity_id",
    ];
  }

  /**
   * Get Session Sales Status.
   *
   * @return string|null
   *   List key.
   */
  public function getSessionSalesStatus() {
    $status = $this->get('dwData')->get('ACTIVITYSTATUS')->getCastedValue();
    if (!is_null($status)) {
      // If status <> 0 the session’s Sales Status is Closed.
      return ($status !== 0) ? 'close' : 'open';
    }
    else {
      $this->setValidationError([
        'message' => 'Empty value from DataWarehouse: ACTIVITYSTATUS',
      ], NULL);
      return NULL;
    }
  }

  /**
   * Get spots available/allowed.
   *
   * @param string $type
   *   Spots type - available|allowed.
   *
   * @return int
   *   Spots available/allowed field value
   */
  public function getSpotsStatus($type = 'available') {
    $ignore = $this->get('dwData')->get('IGNOREMAXIMUM')->getCastedValue();
    $result = 0;
    if ($ignore !== 0) {
      return $result;
    }
    if ($type == 'available') {
      $result = $this->get('assetQuantity')->get('availableCnt')->getCastedValue();
    }
    elseif ($type == 'allowed') {
      $result = $this->get('assetQuantity')->get('capacityNb')->getCastedValue();
    }
    return $result;
  }

}
