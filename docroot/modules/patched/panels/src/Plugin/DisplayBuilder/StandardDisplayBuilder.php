<?php

namespace Drupal\panels\Plugin\DisplayBuilder;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Plugin\PluginWizardInterface;
use Drupal\panels\Form\PanelsContentForm;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The standard display builder for viewing a PanelsDisplayVariant.
 *
 * @DisplayBuilder(
 *   id = "standard",
 *   label = @Translation("Standard")
 * )
 */
class StandardDisplayBuilder extends DisplayBuilderBase implements PluginWizardInterface, ContainerFactoryPluginInterface {

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new PanelsDisplayVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextHandlerInterface $context_handler, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->contextHandler = $context_handler;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('current_user')
    );
  }

  /**
   * Build render arrays for each of the regions.
   *
   * @param array $regions
   *   The render array representing regions.
   * @param array $contexts
   *   The array of context objects.
   *
   * @return array
   *   An associative array, keyed by region ID, containing the render arrays
   *   representing the content of each region.
   */
  protected function buildRegions(array $regions, array $contexts) {
    $build = [];
    /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
    foreach ($regions as $region => $blocks) {
      if (!$blocks) {
        continue;
      }

      $weight = 0;

      $build[$region]['#prefix'] = '<div class="' . Html::getClass("block-region-$region") . '">';
      $build[$region]['#suffix'] = '</div>';

      foreach ($blocks as $block_id => $block) {
        if ($block instanceof ContextAwarePluginInterface) {
          $this->contextHandler->applyContextMapping($block, $contexts);
        }

        if ($block->access($this->account)) {
          $content = $block->build();

          if (!is_array($content)) {
            throw new \LogicException(sprintf(
              '%s::build() method has to return an array. Class returning non-array value is: %s',
              BlockPluginInterface::class,
              get_class($block)
            ));
          }

          // If the block is empty, instead of trying to render the block
          // correctly return just #cache, so that the render system knows the
          // reasons (cache contexts & tags) why this block is empty.
          // @see \Drupal\block\BlockViewBuilder::preRender()
          if (Element::isEmpty($content)) {
            $block_render_array = [];
            $cacheable_metadata = CacheableMetadata::createFromObject($block_render_array);
            $cacheable_metadata->applyTo($block_render_array);
            if (isset($content['#cache'])) {
              $block_render_array['#cache'] = $content['#cache'];
            }
          }
          else {
            $block_render_array = [
              '#theme' => 'block',
              '#attributes' => [],
              '#contextual_links' => [],
              '#weight' => $weight++,
              '#configuration' => $block->getConfiguration(),
              '#plugin_id' => $block->getPluginId(),
              '#base_plugin_id' => $block->getBaseId(),
              '#derivative_plugin_id' => $block->getDerivativeId(),
            ];

            // If the block is not empty, build the block and bubble its
            // attributes up if possible. This allows modules like Quickedit to
            // function.
            foreach (['#attributes', '#contextual_links'] as $property) {
              if (isset($content[$property])) {
                $block_render_array[$property] += $content[$property];
                unset($content[$property]);
              }
            }

            $block_render_array['content'] = $content;
          }

          $build[$region][$block_id] = $block_render_array;
        }
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build(PanelsDisplayVariant $panels_display) {
    $regions = $panels_display->getRegionAssignments();
    $contexts = $panels_display->getContexts();
    $layout = $panels_display->getLayout();

    $regions = $this->buildRegions($regions, $contexts);
    if ($layout) {
      $regions = $layout->build($regions);
    }
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardOperations($cached_values) {
    $operations = [];
    $operations['content'] = [
      'title' => $this->t('Content'),
      'form' => PanelsContentForm::class,
    ];
    return $operations;
  }

}
