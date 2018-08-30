<?php

namespace Drupal\activenet_sync\Utility;

/**
 * Wraps activity reference methods.
 */
trait ActivityReference {

  /**
   * Get Activity reference conditions.
   *
   * Do all permutations of the three category fields:
   * 1. All fields.
   * 2. Category, Detailed Category, empty Age Category.
   * 3. Category, empty Detailed Category, Age Category.
   * 4. Empty Category, Detailed Category, Age Category.
   * 5. Category, empty Detailed Category, empty Age Category.
   * 6. Empty Category, Detailed Category, empty Age Category.
   * 7. Empty Category, empty Detailed Category, Age Category.
   */
  public function getActivityReferenceConditions($category, $department, $subcategory) {
    return [
      [
        'field_activenet_category' => $category,
        'field_activenet_detailed_categor' => $department,
        'field_activenet_age_category' => $subcategory,
      ],
      [
        'field_activenet_category' => $category,
        'field_activenet_detailed_categor' => $department,
        'field_activenet_age_category' => '',
      ],
      [
        'field_activenet_category' => $category,
        'field_activenet_detailed_categor' => '',
        'field_activenet_age_category' => $subcategory,
      ],
      [
        'field_activenet_category' => '',
        'field_activenet_detailed_categor' => $department,
        'field_activenet_age_category' => $subcategory,
      ],
      [
        'field_activenet_category' => $category,
        'field_activenet_detailed_categor' => '',
        'field_activenet_age_category' => '',
      ],
      [
        'field_activenet_category' => '',
        'field_activenet_detailed_categor' => $department,
        'field_activenet_age_category' => '',
      ],
      [
        'field_activenet_category' => '',
        'field_activenet_detailed_categor' => '',
        'field_activenet_age_category' => $subcategory,
      ],
    ];
  }

  /**
   * Returns activity reference.
   *
   * @param string $category
   *   Activity category.
   * @param string $department
   *   Activity detailed category.
   * @param string $subcategory
   *   Activity age category.
   *
   * @return array
   *   Array of activity IDs.
   */
  public function getActivityReference($category, $department, $subcategory) {
    $activity_ids = [];
    $conditions = $this->getActivityReferenceConditions($category, $department, $subcategory);
    // Go through all conditions and stop at the 1st match.
    foreach ($conditions as $condition) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'activity');
      foreach ($condition as $field => $value) {
        if (empty($value)) {
          // Add empty condition to check NULL or "" values.
          $empty_condition = $query->orConditionGroup()
            ->condition($field, $value)
            ->condition($field, NULL, 'IS NULL');
          $query->condition($empty_condition);
        }
        else {
          $query->condition($field, $value);
        }
      }

      $activity_ids = array_merge($activity_ids, $query->execute());
    }

    return array_unique($activity_ids);
  }

}
