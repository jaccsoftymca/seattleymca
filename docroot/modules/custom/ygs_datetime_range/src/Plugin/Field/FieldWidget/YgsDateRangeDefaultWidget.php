<?php

namespace Drupal\ygs_datetime_range\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;

/**
 * Plugin implementation of the 'ygs_daterange_default' widget.
 *
 * @FieldWidget(
 *   id = "ygs_daterange_default",
 *   label = @Translation("YGS Date and time range"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class YgsDateRangeDefaultWidget extends DateRangeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hide_end_date' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['hide_end_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide end date'),
      '#default_value' => $this->getSetting('hide_end_date'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Hide end date: @order', ['@order' => $this->getSetting('hide_end_date') ? $this->t('Yes') : $this->t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    if (!$this->getSetting('hide_end_date')) {
      return $element;
    }

    // Hide the end date input.
    $element['end_value']['#date_date_element'] = 'none';

    // Add our validation function as the first one.
    array_unshift($element['#element_validate'], [$this, 'validateSetEndDate']);
    return $element;
  }

  /**
   * Callback #element_validate to set end date = start date.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateSetEndDate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['value']['#value']['date']) || !$this->getSetting('hide_end_date')) {
      return;
    }
    $element['end_value']['#value']['date'] = $element['value']['#value']['date'];
    $element['end_value']['#value']['object'] = new DrupalDateTime($element['end_value']['#value']['date'] . 'T' . $element['end_value']['#value']['time']);
    $value = [
      'value' => $element['value']['#value']['object'],
      'end_value' => $element['end_value']['#value']['object'],
      '_weight' => $element['weight'],
    ];
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    $id_prefix = implode('-', array_merge($parents, array($field_name)));
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = array(
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', array('@number' => $delta + 1)),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          );

          if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed() && $field_state['items_count'] > 0) {
            $element['remove_item'] = array(
              '#type' => 'submit',
              '#value' => $this->t('Remove'),
              '#submit' => array(array(get_class($this), 'removeItemSubmit')),
              '#ajax' => array(
                'callback' => array(get_class($this), 'removeItemAjax'),
                'wrapper' => $wrapper_id,
                'effect' => 'fade',
              ),
              '#name' => implode('_', array_merge($parents, array(
                $field_name,
                $delta,
                'remove_item',
              ))),
              '#attributes' => array('class' => array('field-remove-item-submit')),
              '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
              '#weight' => 10,
            );
          }
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      );

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#submit' => array(array(get_class($this), 'addMoreSubmit')),
          '#ajax' => array(
            'callback' => array(get_class($this), 'addMoreAjax'),
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ),
        );
      }
    }

    return $elements;
  }

  /**
   * Remove item button submit callback.
   */
  public static function removeItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $container_element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $container_element['#field_name'];
    $field_parents = isset($element['#field_parents']) ? $element['#field_parents'] :
      isset($element['target_id']['#field_parents']) ? $element['target_id']['#field_parents'] : array();
    $delta = isset($element['#delta']) ? $element['#delta'] :
      isset($element['target_id']['#delta']) ? $element['target_id']['#delta'] : 0;
    $field_values = &$form_state->getValue($container_element['#parents']);
    $field_input = &NestedArray::getValue($form_state->getUserInput(), $container_element['#parents']);
    $field_state = static::getWidgetState($field_parents, $field_name, $form_state);

    for ($i = $delta; $i < $field_state['items_count']; $i++) {
      $field_values[$i] = $field_values[$i + 1];
      $field_input[$i] = $field_input[$i + 1];
    }
    unset($field_values[$field_state['items_count']]);
    unset($field_input[$field_state['items_count']]);

    // Increment the items count.
    $field_state['items_count']--;
    static::setWidgetState($field_parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for remove item button.
   */
  public static function removeItemAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go two levels up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    return $element;
  }

}
