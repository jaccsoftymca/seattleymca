<?php

namespace Drupal\ygs_page_cache;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Modifies the page_cache service.
 */
class YgsPageCacheServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides page_cache class.
    try {
      $definition = $container->getDefinition('http_middleware.page_cache');
      $definition->setClass('Drupal\ygs_page_cache\StackMiddleware\YgsPageCache');
    }
    catch (ServiceNotFoundException $e) {

    }
  }

}
