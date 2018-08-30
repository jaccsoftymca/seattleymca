<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Asset Legacy Data type.
 *
 * @DataType(
 * id = "asset_legacy_data",
 * label = @Translation("Asset Legacy Data"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\AssetLegacyDataDefinition"
 * )
 */
class AssetLegacyData extends Map {}
