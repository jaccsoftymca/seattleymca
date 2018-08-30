<?php

namespace Drupal\activenet_sync\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * ActiveNet data definition.
 */
class ActiveNetDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['assetName'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('assetName');
      $info['assetGuid'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('assetGuid');
      $info['salesStatus'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('salesStatus');
      $info['regReqGenderCd'] = DataDefinition::create('string')
        ->setRequired(TRUE)
        ->setLabel('regReqGenderCd')
        ->addConstraint('Length', array('max' => 1));
      $info['regReqMaxAge'] = DataDefinition::create('integer')
        ->setLabel('regReqMaxAge');
      $info['regReqMinAge'] = DataDefinition::create('integer')
        ->setLabel('regReqMinAge');
      $info['modifiedDate'] = DataDefinition::create('datetime_iso8601')
        ->setRequired(TRUE)
        ->setLabel('modifiedDate');
      $info['assetPrices'] = ListDataDefinition::create('asset_price')
        ->setLabel('assetPrices');
      $info['activityRecurrences'] = ListDataDefinition::create('activity_recurrences')
        ->setLabel('activityRecurrences');
      $info['assetLegacyData'] = AssetLegacyDataDefinition::create('asset_legacy_data')
        ->setLabel('assetLegacyData');
      $info['assetDescriptions'] = ListDataDefinition::create('asset_description')
        ->setLabel('assetDescriptions');
      $info['assetTags'] = ListDataDefinition::create('asset_tag_wrap')
        ->setLabel('assetTags');
      $info['place'] = AssetPlaceDefinition::create('asset_place')
        ->setLabel('place');
      $info['assetQuantity'] = AssetQuantityDefinition::create('asset_quantity')
        ->setLabel('assetQuantity');
      $info['dwData'] = DwDataDefinition::create('dw_data')
        ->setLabel('dwData');
    }
    return $this->propertyDefinitions;
  }

}
