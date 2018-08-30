<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Data Warehouse definition.
 */
class DwDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['ACTIVITYNAME'] = DataDefinition::create('string')
        ->setLabel('activity');
      $info['DEPARTMENT_NAME'] = DataDefinition::create('string')
        ->setLabel('department');
      $info['CATEGORYNAME'] = DataDefinition::create('string')
        ->setLabel('category');
      $info['SUBCATEGORYNAME'] = DataDefinition::create('string')
        ->setLabel('subcategory');
      $info['FACILITYNAME'] = DataDefinition::create('string')
        ->setLabel('facilityname');
      $info['ALLOW_WAIT_LISTING'] = DataDefinition::create('integer')
        ->setLabel('allow_wait_listing');
      $info['NO_MEETING_DATES'] = DataDefinition::create('integer')
        ->setLabel('no_meeting_dates');
      $info['ACTIVITYSTATUS'] = DataDefinition::create('integer')
        ->setLabel('activity_status');
      $info['location'] = DataDefinition::create('string')
        ->setLabel('location');
      $info['DESCRIPTION'] = DataDefinition::create('string')
        ->setLabel('description');
      $info['IGNOREMAXIMUM'] = DataDefinition::create('integer')
        ->setLabel('IGNOREMAXIMUM');
      $info['sales_start_date'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('sales_start_date');
      $info['sales_end_date'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('sales_end_date');
    }
    return $this->propertyDefinitions;
  }

}
