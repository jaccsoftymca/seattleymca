<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Asset Price type.
 *
 * @DataType(
 * id = "asset_price",
 * label = @Translation("Asset price"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\AssetPriceDefinition"
 * )
 */
class AssetPrice extends Map {}
