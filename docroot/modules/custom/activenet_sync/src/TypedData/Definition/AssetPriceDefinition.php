<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * ActiveNet Asset Price definition.
 */
class AssetPriceDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['priceType'] = DataDefinition::create('string')->setRequired(TRUE)->setLabel('priceType');
      $info['priceAmt'] = DataDefinition::create('float')->setRequired(TRUE)->setLabel('priceAmt');
      $info['maxPriceAmt'] = DataDefinition::create('float')->setRequired(TRUE)->setLabel('maxPriceAmt');
      $info['minPriceAmt'] = DataDefinition::create('float')->setRequired(TRUE)->setLabel('minPriceAmt');
    }
    return $this->propertyDefinitions;
  }

}
