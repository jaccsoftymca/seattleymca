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
 *   id = "class_session",
 *   label = @Translation("Class specific session"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/rest/class/{class}/location/{location}/session/{session}/{datetime}"
 *   }
 * )
 */
class ClassSessionResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a schedule for specified entities.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response containing a schedule for specified entities.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get($class, $location = NULL, $session = NULL, $datetime = NULL) {
    if (!$class) {
      throw new HttpException(t('Entity wasn\'t provided'));
    }

    if (!$class_node = \Drupal::entityTypeManager()->getStorage('node')->load($class)) {
      throw new NotFoundHttpException(t('Class entity @entity was not found', ['@entity' => $class]));
    }
    if ($class_node->bundle() != 'class') {
      throw new NotFoundHttpException(t('Entity @entity is not of bundle "class"', ['@entity' => $class]));
    }

    if (!$location_node = \Drupal::entityTypeManager()->getStorage('node')->load($location)) {
      throw new NotFoundHttpException(t('Branch entity @entity was not found', ['@entity' => $location]));
    }
    if (!in_array($location_node->bundle(), ['branch', 'camp'])) {
      throw new NotFoundHttpException(t('Entity @entity is not of bundle "branch" or "camp"', ['@entity' => $location]));
    }

    if (!$session) {
      throw new HttpException(t('Session id wasn\'t provided'));
    }
    if (!$session_node = \Drupal::entityTypeManager()->getStorage('node')->load($session)) {
      throw new NotFoundHttpException(t('Session entity @entity was not found', ['@entity' => $session]));
    }
    if ($session_node->bundle() != 'session') {
      throw new NotFoundHttpException(t('Entity @entity is not of bundle "session"', ['@entity' => $session]));
    }

    $_sessions = \Drupal::service('ygs_class_page.data_provider')
      ->getUpcomingSessions($class, $location, $session);

    $sessions = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use (&$_sessions) {
      return \Drupal::service('ygs_class_page.data_provider')
        ->formatSessions($_sessions);
    });

    $locations = \Drupal::service('ygs_class_page.data_provider')
      ->getAvailableLocations($class);

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
