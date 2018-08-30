<?php

namespace Drupal\ymca_mappings;

use Drupal\Core\Entity\EntityRepository;

/**
 * Class FacilityRepository.
 *
 * @package Drupal\ymca_mappings\Entity
 */
class FacilityRepository extends EntityRepository {

  /**
   * Load mapping by facility ID.
   *
   * @param int $id
   *   Node ID.
   *
   * @return object
   *   Mapping entity.
   */
  public function loadMappingByFacilityId($id) {
    $entities = $this->entityTypeManager->getStorage('mapping')->loadByProperties([
      'type' => 'facility',
      'field_facility_ct' => $id,
    ]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * Load Facility by mapping ID.
   *
   * @param int $id
   *   Mapping id.
   *
   * @return object
   *   Node entity.
   */
  public function loadFacilityByMappingId($id) {
    return $this->loadFacilityByMappingProperty('id', $id);
  }

  /**
   * Load Facility by mapping Name.
   *
   * @param string $name
   *   Mapping name.
   *
   * @return object
   *   Node entity.
   */
  public function loadFacilityByFacilityName($name) {
    return $this->loadFacilityByMappingProperty('field_flexreg_facility_name', $name);
  }

  /**
   * Load Facility by property.
   *
   * @param string $property
   *   Property name.
   * @param string $value
   *   Property value.
   *
   * @return object
   *   Node entity.
   */
  private function loadFacilityByMappingProperty($property, $value) {
    $entities = $this->entityTypeManager->getStorage('mapping')->loadByProperties([
      'type' => 'facility',
      $property => $value,
    ]);
    $mapping = ($entities) ? reset($entities) : NULL;
    $facilities = NULL;
    if ($mapping) {
      $facilities = $mapping->get('field_facility_ct')->referencedEntities();
    }
    return ($facilities) ? reset($facilities) : NULL;
  }

}
