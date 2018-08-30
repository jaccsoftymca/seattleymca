<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Data Warehouse data type.
 *
 * @DataType(
 * id = "fee_amount",
 * label = @Translation("Fee Amount data"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\FeeAmountDefinition"
 * )
 */
class FeeAmount extends Map {}
