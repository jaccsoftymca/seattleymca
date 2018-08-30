<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * ActiveNet Activity Recurrences definition.
 */
class ActivityRecurrencesDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['activityStartDate'] = DataDefinition::create('datetime_iso8601')->setRequired(TRUE)->setLabel('activityStartDate');
      $info['activityEndDate'] = DataDefinition::create('datetime_iso8601')->setRequired(TRUE)->setLabel('activityEndDate');
      $info['days'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('days');
      $info['startTime'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('startTime');
      $info['endTime'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('endTime');
      $info['frequency'] = ActivityRecurrencesFrequencyDefinition::create('activity_recurrences_frequency')->setLabel('frequency');
      $info['activityExclusions'] = ListDataDefinition::create('activity_recurrences_exclusions')->setLabel('activityExclusions');
    }
    return $this->propertyDefinitions;
  }

}
