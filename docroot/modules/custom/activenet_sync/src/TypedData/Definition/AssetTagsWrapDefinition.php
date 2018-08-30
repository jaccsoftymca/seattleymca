<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * AssetTagsWrap definition.
 */
class AssetTagsWrapDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['tag'] = AssetTagDefinition::create('asset_tag')->setLabel('tag');
    }
    return $this->propertyDefinitions;
  }

}
