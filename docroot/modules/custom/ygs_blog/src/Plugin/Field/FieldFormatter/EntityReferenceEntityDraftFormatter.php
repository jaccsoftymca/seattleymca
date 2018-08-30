<?php

namespace Drupal\ygs_blog\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'entity reference rendered draft entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_draft_view",
 *   label = @Translation("Rendered draft entity"),
 *   description = @Translation("Display the referenced draft entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceEntityDraftFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Display the latest draft if user has permission.
      $user = \Drupal::currentUser();
      if ($user->hasPermission('View all revisions')) {
        $moderation_information = \Drupal::service(ygs_moderation_wrapper_active_module() . '.moderation_information');
        if (!$moderation_information->isLatestRevision($entity)) {
          $entity_type_id = $entity->getEntityTypeId();
          $latest_revision_id = $moderation_information->getLatestRevisionId($entity_type_id, $entity->id());
          $entity = $this->entityTypeManager
            ->getStorage($entity_type_id)
            ->loadRevision($latest_revision_id);
        }
      }

      // Due to render caching and delayed calls, the viewElements() method
      // will be called later in the rendering process through a '#pre_render'
      // callback, so we need to generate a counter that takes into account
      // all the relevant information about this field and the referenced
      // entity that is being rendered.
      $recursive_render_id = $items->getFieldDefinition()->getTargetEntityTypeId()
        . $items->getFieldDefinition()->getTargetBundle()
        . $items->getName()
        // We include the referencing entity, so we can render default images
        // without hitting recursive protections.
        . $items->getEntity()->id()
        . $entity->getEntityTypeId()
        . $entity->id();

      if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
        static::$recursiveRenderDepth[$recursive_render_id]++;
      }
      else {
        static::$recursiveRenderDepth[$recursive_render_id] = 1;
      }

      // Protect ourselves from recursive rendering.
      if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity %entity_type: %entity_id, using the %field_name field on the %bundle_name bundle. Aborting rendering.', [
          '%entity_type' => $entity->getEntityTypeId(),
          '%entity_id' => $entity->id(),
          '%field_name' => $items->getName(),
          '%bundle_name' => $items->getFieldDefinition()->getTargetBundle(),
        ]);
        return $elements;
      }

      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());
      $elements[$delta]['#cache']['contexts'][] = 'user.permissions';

      // Add a resource attribute to set the mapping property's value to the
      // entity's url. Since we don't know what the markup of the entity will
      // be, we shouldn't rely on it for structured data such as RDFa.
      if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
        $items[$delta]->_attributes += array('resource' => $entity->toUrl()->toString());
      }
    }

    return $elements;
  }

}
