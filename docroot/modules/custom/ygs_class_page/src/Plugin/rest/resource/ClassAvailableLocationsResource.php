<?php

namespace Drupal\ygs_class_page\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get upcoming class sessions.
 *
 * @RestResource(
 *   id = "class_available_locations",
 *   label = @Translation("Class available locations"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/rest/class/{nid}/available-locations/{datetime}"
 *   }
 * )
 */
class ClassAvailableLocationsResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of available locations for specified class entity.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response containing a list of bundle names.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function get($nid, $datetime) {
    if (!$nid) {
      throw new NotFoundHttpException(t('Entity wasn\'t provided'));
    }

    if (!$node = \Drupal::entityTypeManager()->getStorage('node')->load($nid)) {
      throw new NotFoundHttpException(t('Entity @entity was not found', ['@entity' => $nid]));
    }
    if ($node->bundle() != 'class') {
      throw new NotFoundHttpException(t('Entity @entity is not of bundle "class"', ['@entity' => $nid]));
    }

    $locations = \Drupal::service('ygs_class_page.data_provider')
      ->getAvailableLocations($nid);

    if (!$locations) {
      throw new NotFoundHttpException(t('No available locations found', ['@entity' => $nid]));
    }

    $response = [];
    foreach ($locations as $id => $location) {
      $response[$id] = [
        'label' => $location->label(),
        'id' => $id,
      ];
    }

    return new ModifiedResourceResponse([
      'locations' => $response,
    ]);
  }

}
