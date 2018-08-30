<?php

namespace Drupal\activenet_sync\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * ActiveNet Activity Recurrences Exclusions type.
 *
 * @DataType(
 * id = "activity_recurrences_exclusions",
 * label = @Translation("Activity Recurrences Exclusions"),
 * definition_class = "\Drupal\activenet_sync\TypedData\Definition\ActivityRecurrencesExclusionsDefinition"
 * )
 */
class ActivityRecurrencesExclusions extends Map {}
