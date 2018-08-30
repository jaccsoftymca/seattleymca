<?php

namespace Drupal\ygs_salesforce_mc\Plugin\YamlFormHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\yamlform\YamlFormHandlerBase;
use Drupal\yamlform\YamlFormSubmissionInterface;
use Drupal\ygs_salesforce_mc\SalesForceMcClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Emails a form submission.
 *
 * @YamlFormHandler(
 *   id = "salesforce_mc",
 *   label = @Translation("SalesForce MC"),
 *   category = @Translation("External"),
 *   description = @Translation("Sends a form submission to SalesForce MC."),
 *   cardinality = \Drupal\yamlform\YamlFormHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\yamlform\YamlFormHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class SalesForceMcYamlFormHandler extends YamlFormHandlerBase {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token handler.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Cache of default configuration values.
   *
   * @var array
   */
  protected $defaultValues;

  /**
   * Client.
   *
   * @var \Drupal\ygs_salesforce_mc\SalesForceMcClient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, Token $token, SalesForceMcClient $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('yamlform.email'),
      $container->get('config.factory'),
      $container->get('token'),
      $container->get('salesforce_mc.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Add to list: @value', ['@value' => $this->configuration['add_to_list']]),
        $this->t('List: @value', ['@value' => $this->configuration['list']]),
        $this->t('Add to Trigger: @value', ['@value' => $this->configuration['add_to_trigger']]),
        $this->t('Triggered definition: @value', ['@value' => $this->configuration['triggered_definition']]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'add_to_list' => FALSE,
      'list' => NULL,
      'add_to_trigger' => FALSE,
      'triggered_definition' => '',
      'mapping' => [],
      'excluded_elements' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Settings.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => FALSE,
    ];
    $form['settings']['add_to_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add to list'),
      '#return_value' => TRUE,
      '#parents' => ['settings', 'add_to_list'],
      '#default_value' => $this->configuration['add_to_list'],
    ];
    $form['settings']['list'] = [
      '#type' => 'select',
      '#placeholder' => $this->t('List name'),
      '#default_value' => $this->configuration['list'],
      '#options' => $this->client->getLists(TRUE),
      '#parents' => ['settings', 'list'],
      '#states' => [
        'visible' => [
          ':input[name="settings[add_to_list]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['settings']['add_to_trigger'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add to Trigger'),
      '#return_value' => TRUE,
      '#parents' => ['settings', 'add_to_trigger'],
      '#default_value' => $this->configuration['add_to_trigger'],
    ];
    $form['settings']['triggered_definition'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Triggered definition'),
      '#default_value' => $this->configuration['triggered_definition'],
      '#parents' => ['settings', 'triggered_definition'],
      '#states' => [
        'visible' => [
          ':input[name="settings[add_to_trigger]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Mapping.
    $properties = $this->client->getProperties();
    $form['mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Mapping'),
      '#tree' => TRUE,
      '#description' => $this->t('Use this values:') . '<br>' . implode(', ', array_merge($properties['properties'], $properties['attributes'])),
    ];
    $elements = $this->yamlform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      $form['mapping'][$key] = [
        '#type' => 'textfield',
        '#title' => (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $key]) : $key,
        '#placeholder' => $this->t('Enter mapping value'),
        '#parents' => ['settings', 'mapping', $key],
        '#default_value' => $this->configuration['mapping'][$key],
      ];
    }

    // Included Elements.
    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included values'),
      '#open' => FALSE,
    ];
    $form['elements']['excluded_elements'] = [
      '#type' => 'yamlform_excluded_elements',
      '#yamlform' => $this->yamlform,
      '#default_value' => $this->configuration['excluded_elements'],
      '#parents' => ['settings', 'excluded_elements'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();

    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(YamlFormSubmissionInterface $yamlform_submission, $update = TRUE) {
    $is_results_disabled = $yamlform_submission->getYamlForm()->getSetting('results_disabled');
    $is_completed = ($yamlform_submission->getState() == YamlFormSubmissionInterface::STATE_COMPLETED);
    if ($is_results_disabled || $is_completed) {
      // Get submission data without excluded elements.
      $data = array_diff_key($yamlform_submission->getData(), $this->configuration['excluded_elements']);
      $properties = $this->client->getProperties();
      $mapping = $this->configuration['mapping'];
      $props = [];
      $email_key = array_search('EmailAddress', $mapping);
      if (!$email_key || empty($data[$email_key])) {
        // Skip if don't have email.
        return;
      }
      // Set base properties.
      $props['SubscriberKey'] = $data[$email_key];
      $props['EmailAddress'] = $data[$email_key];
      unset($data[$email_key]);

      foreach ($data as $field_name => $value) {
        if (!empty($mapping[$field_name])) {
          // Check if this field property or attribute.
          if (in_array($mapping[$field_name], $properties['properties'])) {
            $props[$mapping[$field_name]] = $data[$field_name];
          }
          else {
            $props['Attributes'][] = [
              'Name' => $mapping[$field_name],
              'Value' => $data[$field_name],
            ];
          }
        }
      }
      if ($this->configuration['add_to_list']) {
        // Add subscriber to list.
        $response = $this->client->addSubscriberToList($props, $this->configuration['list']);
      }
      if ($this->configuration['add_to_trigger']) {
        // Add subscriber to triggered definition.
        $response = $this->client->triggeredSend($props, $this->configuration['triggered_definition']);
      }
    }
  }

}
