<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Fee Amounts definition(from Data Warehouse).
 */
class FeeAmountDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['FEEAMOUNT'] = DataDefinition::create('float')
        ->setLabel('FEEAMOUNT');
      $info['CHARGE_NAME'] = DataDefinition::create('string')
        ->setLabel('CHARGE_NAME');
    }
    return $this->propertyDefinitions;
  }

}
