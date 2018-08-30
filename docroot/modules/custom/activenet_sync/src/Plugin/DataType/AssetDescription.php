<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Asset Description type.
 *
 * @DataType(
 * id = "asset_description",
 * label = @Translation("Asset Description"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\AssetDescriptionDefinition"
 * )
 */
class AssetDescription extends Map {}
