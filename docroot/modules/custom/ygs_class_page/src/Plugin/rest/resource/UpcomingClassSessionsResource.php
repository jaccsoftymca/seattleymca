<?php

namespace Drupal\ygs_class_page\Plugin\rest\resource;

use Drupal\Core\Render\RenderContext;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get upcoming class sessions.
 *
 * @RestResource(
 *   id = "class_upcoming_sessions",
 *   label = @Translation("Upcoming class sessions"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/rest/class/{class_entity}/location/{location_entity}/schedule/{datetime}"
 *   }
 * )
 */
class UpcomingClassSessionsResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response containing a list of bundle names.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($class_entity, $location_entity = NULL, $datetime = NULL) {
    if (!$class_entity) {
      throw new HttpException(t('Entity wasn\'t provided'));
    }

    if (!$class_node = \Drupal::entityTypeManager()->getStorage('node')->load($class_entity)) {
      throw new NotFoundHttpException(t('Class entity @entity was not found', ['@entity' => $class_entity]));
    }
    if ($class_node->bundle() != 'class') {
      throw new NotFoundHttpException(t('Entity @entity is not of bundle "class"', ['@entity' => $class_entity]));
    }

    if (!$location_node = \Drupal::entityTypeManager()->getStorage('node')->load($location_entity)) {
      throw new NotFoundHttpException(t('Branch entity @entity was not found', ['@entity' => $location_entity]));
    }
    if (!in_array($location_node->bundle(), ['branch', 'camp'])) {
      throw new NotFoundHttpException(t('Entity @entity is not of bundle "branch" or "camp"', ['@entity' => $location_entity]));
    }

    $_sessions = \Drupal::service('ygs_class_page.data_provider')
      ->getUpcomingSessions($class_entity, $location_entity);

    $sessions = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use (&$_sessions) {
      return \Drupal::service('ygs_class_page.data_provider')
        ->formatSessions($_sessions);
    });

    $locations = \Drupal::service('ygs_class_page.data_provider')
      ->getAvailableLocations($class_entity);

    if ($locations) {
      foreach ($locations as $id => &$location) {
        $location = [
          'label' => $location->label(),
          'id' => $id,
        ];
      }
    }

    return new ModifiedResourceResponse([
      'sessions' => $sessions,
      'locations' => array_values($locations),
    ]);
  }

}
