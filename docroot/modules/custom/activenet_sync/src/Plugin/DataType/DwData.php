<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Data Warehouse data type.
 *
 * @DataType(
 * id = "dw_data",
 * label = @Translation("Data Warehouse data"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\DwDataDefinition"
 * )
 */
class DwData extends Map {}
