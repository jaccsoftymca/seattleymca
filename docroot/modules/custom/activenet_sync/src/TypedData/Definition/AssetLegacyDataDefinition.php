<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Asset Legacy Data definition.
 */
class AssetLegacyDataDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['substitutionUrl'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('substitutionUrl');
      $info['onlineRegistration'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('onlineRegistration');
    }
    return $this->propertyDefinitions;
  }

}
