<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Asset Description definition.
 */
class AssetDescriptionDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['description'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('description');
    }
    return $this->propertyDefinitions;
  }

}
