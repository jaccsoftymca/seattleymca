<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * ActiveNet Activity Recurrences Frequency definition.
 */
class ActivityRecurrencesFrequencyDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['frequencyName'] = DataDefinition::create('string')->setLabel('frequencyName');
    }
    return $this->propertyDefinitions;
  }

}
