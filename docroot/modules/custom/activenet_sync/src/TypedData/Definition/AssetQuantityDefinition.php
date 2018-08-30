<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Asset Quantity definition.
 */
class AssetQuantityDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['availableCnt'] = DataDefinition::create('integer')
        ->setRequired(TRUE)
        ->setLabel('availableCnt');
      $info['capacityNb'] = DataDefinition::create('integer')
        ->setRequired(TRUE)
        ->setLabel('capacityNb');
    }
    return $this->propertyDefinitions;
  }

}
