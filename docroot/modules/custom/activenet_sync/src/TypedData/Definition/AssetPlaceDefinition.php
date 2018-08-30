<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Asset Place definition.
 */
class AssetPlaceDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['placeName'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('placeName');
    }
    return $this->propertyDefinitions;
  }

}
