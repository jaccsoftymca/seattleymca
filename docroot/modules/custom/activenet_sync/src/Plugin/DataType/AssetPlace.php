<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Asset Place type.
 *
 * @DataType(
 * id = "asset_place",
 * label = @Translation("Asset Place"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\AssetPlaceDefinition"
 * )
 */
class AssetPlace extends Map {}
