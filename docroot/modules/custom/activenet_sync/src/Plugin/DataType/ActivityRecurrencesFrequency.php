<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * ActiveNet Activity Recurrences frequency type.
 *
 * @DataType(
 * id = "activity_recurrences_frequency",
 * label = @Translation("Activity Recurrences frequency"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\ActivityRecurrencesFrequencyDefinition"
 * )
 */
class ActivityRecurrencesFrequency extends Map {}
