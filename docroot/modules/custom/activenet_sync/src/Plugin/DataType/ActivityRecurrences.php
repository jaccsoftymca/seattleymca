<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * ActiveNet Activity Recurrences type.
 *
 * @DataType(
 * id = "activity_recurrences",
 * label = @Translation("Activity Recurrences"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\ActivityRecurrencesDefinition"
 * )
 */
class ActivityRecurrences extends Map {}
