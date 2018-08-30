<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Asset Tags Wrapper type.
 *
 * @DataType(
 * id = "asset_tag_wrap",
 * label = @Translation("Asset Tags"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\AssetTagsWrapDefinition"
 * )
 */
class AssetTagsWrap extends Map {}
