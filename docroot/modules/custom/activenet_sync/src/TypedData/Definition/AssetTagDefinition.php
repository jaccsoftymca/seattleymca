<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Asset Tag definition.
 */
class AssetTagDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['tagId'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('tagId');
      $info['tagName'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('tagName');
      $info['tagDescription'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('tagDescription');
    }
    return $this->propertyDefinitions;
  }

}
