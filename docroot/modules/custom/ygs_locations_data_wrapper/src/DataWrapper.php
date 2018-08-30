<?php

namespace Drupal\ygs_locations_data_wrapper;

use Drupal\openy_socrates\OpenyDataServiceInterface;
use Drupal\openy_socrates\OpenySocratesFacade;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Class DataWrapper.
 *
 * Provides data for location finder add membership calc.
 */
class DataWrapper implements OpenyDataServiceInterface {

  /**
   * Openy Socrates Facade.
   *
   * @var OpenySocratesFacade
   */
  protected $socrates;

  /**
   * Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * DataWrapperBase constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   Query factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\openy_socrates\OpenySocratesFacade $socrates
   *   Socrates.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   */
  public function __construct(
    QueryFactory $queryFactory,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entityTypeManager,
    OpenySocratesFacade $socrates,
    CacheBackendInterface $cacheBackend,
    LoggerChannelInterface $loggerChannel
  ) {

    $this->queryFactory = $queryFactory;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->socrates = $socrates;
    $this->cacheBackend = $cacheBackend;
    $this->loggerChannel = $loggerChannel;
  }

  /**
   * Get all location pins for map.
   *
   * Used in location finder block.
   */
  public function getLocationPins() {
    $branch_pins = $this->getPins('branch', t('YMCA'), 'blue');
    $camp_pins = $this->getPins('camp', t('Camps'), 'green');
    // TODO: Add icon with new color for facility.
    $facility_pins = $this->getPins('facility', t('Facilities'), 'green');
    $pins = array_merge($branch_pins, $camp_pins, $facility_pins);
    return $pins;
  }

  /**
   * Get pins.
   *
   * @param string $type
   *   Location node type (branch, camp or facility).
   * @param string $tag
   *   Pin tag.
   * @param string $icon_color
   *   Icon color(see possible options in img directory).
   * @param int $id
   *   Node ID (if set return pin only for specified node ID).
   *
   * @return array
   *   Pins
   */
  public function getPins($type, $tag, $icon_color, $id = NULL) {
    if ($id) {
      $location_ids[] = $id;
    }
    else {
      $location_ids = $this->queryFactory->get('node')
        ->condition('type', $type)
        ->condition('status', 1)
        ->execute();
    }

    if (!$location_ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $locations = $storage->loadMultiple($location_ids);

    $pins = [];
    if ($type == 'facility') {
      $allowed_facility_types = $this->getFacilityTypes();
    }
    foreach ($locations as $location) {
      $view = $builder->view($location, 'teaser');
      $coordinates = $location->get('field_location_coordinates')->getValue();
      if (!$coordinates) {
        continue;
      }
      $tags = [$tag];
      if ($type == 'facility' && !empty($allowed_facility_types)) {
        $tags = [];
        if ($location->get('field_facility_type')->first()) {
          $facility_type_id = $location->get('field_facility_type')->first()->get('target_id')->getValue();
          if (in_array($facility_type_id, $allowed_facility_types)) {
            // Add facility type title to tags if it in allowed list.
            $tags[] = $location->field_facility_type->entity->getName();
          }
        }
      }
      $icon = file_create_url(drupal_get_path('module', 'location_finder') . "/img/map_icon_$icon_color.png");
      $pins[] = [
        'icon' => $icon,
        'tags' => $tags,
        'lat' => round($coordinates[0]['lat'], 5),
        'lng' => round($coordinates[0]['lng'], 5),
        'name' => trim($location->label()),
        'markup' => $this->renderer->renderRoot($view),
      ];
    }

    return $pins;
  }

  /**
   * Returns all facility types with checked "Location Filter".
   *
   * @return array
   *   Array of facility types IDs.
   */
  public function getFacilityTypes() {
    return $this->queryFactory->get('taxonomy_term')
      ->condition('vid', 'facility_type')
      ->condition('field_location_filter', 1)
      ->execute();
  }

  /**
   * Get list of membership types.
   *
   * Used in membership calc block.
   *
   * @return array
   *   The list of membership types keyed by type ID.
   */
  public function getMembershipTypes() {
    $types = [];
    // Needed when the membership is negated and nodes have empty types.
    $empty_ids = [];
    // Reset is needed since 'func_get_args' returns the args in a parent array.
    $args = reset(func_get_args());

    $query = $this->queryFactory->get('node')
      ->condition('type', 'membership')
      ->condition('status', 1);
    // If membership types are in use, add more conditions.
    if (isset($args['membership_types']) && is_array($args['membership_types'])) {
      $op =  $args['membership_types_negate'] ? 'NOT IN' : 'IN';
      $query->condition('field_mbrshp_types', $args['membership_types'], $op);
      // An extra query is needed since condition "NOT IN" excludes
      // nodes with the same field left empty.
      if ($op === 'NOT IN') {
        $empty_ids = $this->queryFactory->get('node')
          ->condition('type', 'membership')
          ->condition('status', 1)
          ->notExists('field_mbrshp_types')
          ->execute();
      }
    }
    // Get the IDs and merged with any nodes with empty values.
    $membership_ids = $query->execute() + $empty_ids;

    if (!$membership_ids) {
      return $types;
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $memberships = $storage->loadMultiple($membership_ids);

    foreach ($memberships as $membership) {
      $membership_id = $membership->id();
      $types[$membership_id] = [
        'title' => $membership->title->value,
        'description' => $builder->view($membership, 'calc_preview'),
      ];
    }

    return $types;
  }

  /**
   * Get Branch location pins for map.
   *
   * Used in membership calc block.
   */
  public function getBranchPins($id = NULL) {
    $branch_pins = $this->getPins('branch', t('YMCA'), 'blue', $id);
    return $branch_pins;
  }

  /**
   * Get the list of locations.
   *
   * Used in membership calc block.
   *
   * @return array
   *   The list of locations keyed by location ID.
   */
  public function getLocations() {
    $data = [];

    $location_ids = $this->queryFactory->get('node')
      ->condition('type', 'branch')
      ->execute();

    if (!$location_ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $locations = $storage->loadMultiple($location_ids);

    foreach ($locations as $location) {
      $data[$location->id()] = [
        'title' => $location->label(),
      ];
    }

    return $data;
  }

  /**
   * Get Summary.
   *
   * @param int $location_id
   *   Location ID.
   * @param string $membership_id
   *   Membership type ID.
   *
   * @return string
   *   Price.
   */
  public function getSummary($location_id, $membership_id) {
    $storage = $this->entityTypeManager->getStorage('node');
    $builder = $this->entityTypeManager->getViewBuilder('node');
    $location = $storage->load($location_id);
    $result['location'] = $builder->view($location, 'calc_summary');
    $membership = $storage->load($membership_id);
    $result['membership'] = $builder->view($membership, 'calc_summary');

    $info = $membership->field_mbrshp_info->referencedEntities();
    foreach ($info as $value) {
      if ($value->field_mbrshp_location->first()->get('target_id')->getValue() == $location_id) {
        $result['price']['monthly_rate'] = $value->field_mbrshp_monthly_rate->value;
        $result['price']['join_fee'] = $value->field_mbrshp_join_fee->value;
        if ($value->field_mbrshp_link) {
          $result['link'] = $value->field_mbrshp_link->first()->getUrl();
        }
      }
    }

    return $result;
  }

  /**
   * Get Redirect Link.
   *
   * @param int $location_id
   *   Location ID.
   * @param string $membership_id
   *   Membership type ID.
   *
   * @return \Drupal\Core\Url
   *   Redirect url.
   */
  public function getRedirectUrl($location_id, $membership_id) {
    $storage = $this->entityTypeManager->getStorage('node');
    $membership = $storage->load($membership_id);
    $info = $membership->field_mbrshp_info->referencedEntities();
    foreach ($info as $value) {
      if ($value->field_mbrshp_location->first()->get('target_id')->getValue() == $location_id) {
        if ($value->field_mbrshp_link) {
          return $value->field_mbrshp_link->first()->getUrl();
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function addDataServices(array $services) {
    return [
      'getLocationPins',
      'getMembershipTypes',
      'getBranchPins',
      'getLocations',
      'getSummary',
      'getRedirectUrl',
    ];
  }

}
