<?php

namespace Drupal\openy_hours_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\openy_field_custom_hours\Plugin\Field\FieldFormatter\CustomHoursFormatterDefault;

/**
 * Plugin implementation for openy_custom_hours formatter.
 *
 * @FieldFormatter(
 *   id = "openy_today_custom_hours",
 *   label = @Translation("OpenY Today's hours"),
 *   field_types = {
 *     "openy_custom_hours"
 *   }
 * )
 */
class CustomHoursToday extends CustomHoursFormatterDefault {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $lazy_hours = [];
    foreach ($items as $delta => $item) {
      $groups = [];
      $rows = [];
      $label = '';

      // Group days by their values.
      foreach ($item as $i_item) {
        // Do not process label. Store it name for later usage.
        $name = $i_item->getName();
        if ($name == 'hours_label') {
          $label = $i_item->getValue();
          continue;
        }

        $day = str_replace('hours_', '', $name);
        $value = $i_item->getValue() ? $i_item->getValue() : 'closed';
        $lazy_hours[$day] = $value;
        if ($groups && end($groups)['value'] == $value) {
          $array_keys = array_keys($groups);
          $group = &$groups[end($array_keys)];
          $group['days'][] = $day;
        }
        else {
          $groups[] = [
            'value' => $value,
            'days' => [$day],
          ];
        }
      }

      foreach ($groups as $group_item) {
        $title = sprintf('%s - %s', ucfirst(reset($group_item['days'])), ucfirst(end($group_item['days'])));
        if (count($group_item['days']) == 1) {
          $title = ucfirst(reset($group_item['days']));
        }
        $hours = $group_item['value'];
        $rows[] = [$title . ':', $hours];
      }

      $lazy_hours_placeholder = [
        '#lazy_builder' => [
          'openy_hours_formatter.hours_today:generateHoursToday',
          $lazy_hours,
        ],
        '#create_placeholder' => TRUE,
      ];

      $elements[$delta] = [
        '#theme' => 'openy_hours_formatter',
        '#hours' => $lazy_hours_placeholder,
        '#week' => [
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h5',
            '#value' => $label,
          ],
          'table' => [
            '#theme' => 'table',
            '#header' => [],
            '#rows' => $rows,
          ],
        ],
        '#attached' => [
          'library' => [
            'openy_hours_formatter/openy_hours_formatter',
          ],
        ],
      ];
    }

    return $elements;
  }

}
