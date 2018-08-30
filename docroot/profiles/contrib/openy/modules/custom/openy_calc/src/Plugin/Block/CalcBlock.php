<?php

namespace Drupal\openy_calc\Plugin\Block;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Membership Calculator' block.
 *
 * @Block(
 *   id = "calc_block",
 *   admin_label = @Translation("Membership Calculator Block"),
 *   category = @Translation("Forms")
 * )
 */
class CalcBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new Programs Search Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $form = parent::blockForm($form, $form_state);
    $terms = (isset($config['membership_types']) && !empty($config['membership_types'])) ? Term::loadMultiple($config['membership_types']) : [];
    $default_value = EntityAutocomplete::getEntityLabels($terms);
    $form['membership_types'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => [
          'membership_type' => 'membership_type',
        ],
      ],
      '#title' => $this->t('Membership Types'),
      '#tags' => TRUE,
      '#process_default_value' => FALSE,
      '#default_value' => $default_value,
    ];

    $form['membership_types_negate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate Membership Types'),
      '#description' => $this->t('Check to remove the selected "Membership Types" from the calculator.'),
      '#default_value' => $config['membership_types_negate'] ? $config['membership_types_negate'] : FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $tids = [];
    if ($values = $form_state->getValue('membership_types')) {
      foreach ($values as $value) {
        $tids[] = $value['target_id'];
      }
    }
    $this->configuration['membership_types'] = $tids;
    $this->configuration['membership_types_negate'] = $form_state->getValue('membership_types_negate');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $args = [];
    // Check for any membership type args and add them to the form.
    if (isset($this->configuration['membership_types']) && !empty($this->configuration['membership_types'])) {
      $args['membership_types'] = $this->configuration['membership_types'];
      $args['membership_types_negate'] = $this->configuration['membership_types_negate'];
    }
    $form = $this->formBuilder->getForm('Drupal\openy_calc\Form\CalcBlockForm', $args);
    return [
      'form' => $form,
    ];
  }

}
