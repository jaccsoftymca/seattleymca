<?php

namespace Drupal\ygs_salesforce_mc\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ygs_salesforce_mc\SalesForceMcClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class FooterSubscribeForm extends FormBase {

  /**
   * Logger channel.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * FileSystem service.
   *
   * @var FileSystem
   */
  protected $fileSystem;

  /**
   * SalesForceMc Client.
   *
   * @var SalesForceMcClient
   */
  protected $client;

  /**
   * Creates a new MindbodyPTForm.
   *
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelFactoryInterface $logger
   *   Logger factory.
   * @param SalesForceMcClient $client
   *   SalesForceMc Client.
   */
  public function __construct(ConfigFactory $config, LoggerChannelFactoryInterface $logger, SalesForceMcClient $client) {
    $this->logger = $logger->get('ygs_salesforce_mc');
    $this->client = $client;
    $this->setConfigFactory($config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('salesforce_mc.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ygs_footer_subscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $destination = '') {
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Sign up for email updates!'),
      '#required' => TRUE,
      '#placeholder' => t('Your email address:'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Sign up'),
      '#attributes' => [
        'class' => ['btn', 'btn-default', 'blue'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggered_props = [
      'SubscriberKey' => $form_state->getValue('email'),
      'EmailAddress' => $form_state->getValue('email'),
    ];
    $triggered_response = $this->client->triggeredSend($triggered_props, 'Triggered_EmailSignUpWeb');

    foreach ($triggered_response->results as $result) {
      if ($result->StatusCode == 'OK') {
        drupal_set_message(t("Thanks for signing up for Y updates! You'll receive a confirmation email shortly."), 'status');
      }
      else {
        $this->logger->error('SalesForceMC error: @error', ['@error' => $result->StatusMessage]);
      }
    }
  }

}
