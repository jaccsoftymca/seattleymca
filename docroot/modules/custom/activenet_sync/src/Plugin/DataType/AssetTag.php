<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Asset Tag type.
 *
 * @DataType(
 * id = "asset_tag",
 * label = @Translation("Asset Tag"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\AssetTagDefinition"
 * )
 */
class AssetTag extends Map {}
