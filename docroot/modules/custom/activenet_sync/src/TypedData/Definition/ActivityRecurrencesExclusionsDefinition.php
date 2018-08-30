<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * ActiveNet Activity Recurrences Frequency definition.
 */
class ActivityRecurrencesExclusionsDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['exclusionStartDate'] = DataDefinition::create('datetime_iso8601')->setLabel('exclusionStartDate');
      $info['exclusionEndDate'] = DataDefinition::create('datetime_iso8601')->setLabel('exclusionEndDate');
    }
    return $this->propertyDefinitions;
  }

}
