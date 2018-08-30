<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Flexreg Data Warehouse definition.
 */
class FlexregDwDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['program_id'] = DataDefinition::create('integer')
        ->setRequired(TRUE)
        ->setLabel('program_id');
      $info['ps_id'] = DataDefinition::create('integer')
        ->setRequired(TRUE)
        ->setLabel('ps_id');
      $info['program_name'] = DataDefinition::create('string')
        ->setLabel('program_name');
      $info['program_description'] = DataDefinition::create('string')
        ->setLabel('program_description');
      $info['program_gender'] = DataDefinition::create('integer')
        ->setLabel('program_gender');
      $info['agesmax'] = DataDefinition::create('integer')
        ->setLabel('agesmax');
      $info['agesmin'] = DataDefinition::create('integer')
        ->setLabel('agesmin');
      $info['session_id'] = DataDefinition::create('integer')
        ->setRequired(TRUE)
        ->setLabel('session_id');
      $info['session_name'] = DataDefinition::create('string')
        ->setLabel('session_name');
      $info['session_description'] = DataDefinition::create('string')
        ->setLabel('session_description');
      $info['start_date'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('start_date');
      $info['end_date'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('end_date');
      $info['start_time'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('start_time');
      $info['end_time'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('end_time');
      $info['category_name'] = DataDefinition::create('string')
        ->setLabel('category_name');
      $info['sub_category_name'] = DataDefinition::create('string')
        ->setLabel('sub_category_name');
      $info['department_name'] = DataDefinition::create('string')
        ->setLabel('department_name');
      $info['costs'] = ListDataDefinition::create('fee_amount')
        ->setLabel('costs');
      $info['fee_amounts'] = ListDataDefinition::create('fee_amount')
        ->setLabel('fee_amounts');
      $info['location'] = DataDefinition::create('string')
        ->setLabel('location');
      $info['physical_location'] = DataDefinition::create('string')
        ->setLabel('physical_location');
      $info['weekdays'] = DataDefinition::create('string')
        ->setLabel('weekdays');
      $info['standard_reg'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('standard_reg');
      $info['standard_reg_end'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('standard_reg_end');
      $info['online_reg'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('online_reg');
      $info['online_reg_end'] = DataDefinition::create('datetime_iso8601')
        ->setLabel('online_reg_end');
    }
    return $this->propertyDefinitions;
  }

}
