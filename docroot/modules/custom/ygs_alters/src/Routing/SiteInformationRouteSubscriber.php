<?php

namespace Drupal\ygs_alters\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class SiteInformationRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Replace Site Information form with custom one.
    if ($route = $collection->get('system.site_information_settings')) {
      $route->setDefault('_form', 'Drupal\ygs_alters\Form\SiteInformationForm');
    }
  }

}
