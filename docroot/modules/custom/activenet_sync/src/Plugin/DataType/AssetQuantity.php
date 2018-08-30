<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Asset Quantity type.
 *
 * @DataType(
 * id = "asset_quantity",
 * label = @Translation("Asset Quantity"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\AssetQuantityDefinition"
 * )
 */
class AssetQuantity extends Map {}
