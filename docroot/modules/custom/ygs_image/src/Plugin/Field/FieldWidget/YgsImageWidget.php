<?php

namespace Drupal\ygs_image\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Plugin implementation of the 'image_ygs_image' widget.
 *
 * @FieldWidget(
 *   id = "image_ygs_image",
 *   label = @Translation("YGS Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class YgsImageWidget extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'preview_svg_width' => 100,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['preview_svg_width'] = array(
      '#title' => t('Preview SVG width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('preview_svg_width'),
      '#description' => t('Specify width of the SVG image preview.'),
      '#weight' => 20,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = t('Preview SVG width: @width', array('@width' => $this->getSetting('preview_svg_width')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field_settings = $this->getFieldSettings();
    $supported_extensions = array('png', 'gif', 'jpg', 'jpeg', 'svg');
    $extensions = array_intersect(explode(' ', $field_settings['file_extensions']), $supported_extensions);
    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

    // Add properties needed by process() method.
    $element['#preview_svg_width'] = $this->getSetting('preview_svg_width');

    return $element;
  }

  /**
   * Process element.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    if (!empty($element['#files']) && !empty($element['preview'])) {
      $file = reset($element['#files']);
      if ($file->getMimeType() == 'image/svg+xml') {
        $element['preview']['#width'] = $element['#preview_svg_width'];
        // Prevent the "This value should be of the correct primitive type." error message.
        unset($element['width']);
        unset($element['height']);
      }
    }

    return $element;
  }

}
