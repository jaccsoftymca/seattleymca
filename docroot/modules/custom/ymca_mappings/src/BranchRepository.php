<?php

namespace Drupal\ymca_mappings;

use Drupal\Core\Entity\EntityRepository;

/**
 * Class BranchRepository.
 *
 * @package Drupal\ymca_mappings\Entity
 */
class BranchRepository extends EntityRepository {

  /**
   * Load mapping by branch ID.
   *
   * @param int $id
   *   Node ID.
   *
   * @return object
   *   Mapping entity.
   */
  public function loadMappingByBranchId($id) {
    $entities = $this->entityTypeManager->getStorage('mapping')->loadByProperties(['field_branch_mapping_location' => $id]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * Load mapping by branch Name.
   *
   * @param string $name
   *   Node title.
   *
   * @return object
   *   Mapping entity.
   */
  public function loadMappingByBranchName($name) {
    // Branch mapping title have the same name.
    $entities = $this->entityTypeManager->getStorage('mapping')->loadByProperties(['name' => $name]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * Load branch by mapping ID.
   *
   * @param int $id
   *   Mapping id.
   *
   * @return object
   *   Node entity.
   */
  public function loadBranchByMappingId($id) {
    return $this->loadBranchByMappingProperty('id', $id);
  }

  /**
   * Load branch by mapping Name.
   *
   * @param string $name
   *   Mapping name.
   *
   * @return object
   *   Node entity.
   */
  public function loadBranchByMappingName($name) {
    return $this->loadBranchByMappingProperty('name', $name);
  }

  /**
   * Load branch by ActiveNet Location Name.
   *
   * @param string $name
   *   ActiveNet Location name.
   *
   * @return object
   *   Node entity.
   */
  public function loadBranchByActiveNetLocationName($name) {
    return $this->loadBranchByMappingProperty('field_activenet_location_name', $name);
  }

  /**
   * Load branch by ActiveNet Location Id.
   *
   * @param int $id
   *   Property name.
   *
   * @return object
   *   Node entity.
   */
  public function loadBranchByActiveNetLocationId($id) {
    return $this->loadBranchByMappingProperty('field_activenet_location_id', $id);
  }

  /**
   * Load branch by property.
   *
   * @param string $property
   *   Property name.
   * @param string $value
   *   Property value.
   *
   * @return object
   *   Node entity.
   */
  private function loadBranchByMappingProperty($property, $value) {
    $entities = $this->entityTypeManager->getStorage('mapping')->loadByProperties([$property => $value]);
    $mapping = ($entities) ? reset($entities) : NULL;
    $branches = NULL;
    if ($mapping) {
      $branches = $mapping->get('field_branch_mapping_location')->referencedEntities();
    }
    return ($branches) ? reset($branches) : NULL;
  }

}
