<?php

namespace Drupal\ygs_alters\Plugin\views\filter;

use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTidDepth;

/**
 * Filter handler for taxonomy terms with depth.
 *
 * This handler is actually part of the media__field_media_tag table and
 * has some restrictions, because it uses a subquery to find media with.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("media_taxonomy_index_tid_depth")
 */
class MediaTaxonomyIndexTidDepth extends TaxonomyIndexTidDepth {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // If no filter values are present, then do nothing.
    if (count($this->value) == 0) {
      return;
    }
    elseif (count($this->value) == 1) {
      if (is_array($this->value)) {
        $this->value = current($this->value);
      }
      $operator = '=';
    }
    else {
      $operator = 'IN';
    }

    // If a relationship is set, we must use the alias it provides.
    if (!empty($this->relationship)) {
      $this->tableAlias = $this->relationship;
    }
    // If no relationship, then use the alias of the base table.
    else {
      $this->tableAlias = $this->query->ensureTable($this->view->storage->get('base_table'));
    }

    // Now build the subqueries.
    $subquery = db_select('media__field_media_tag', 'mt');
    $subquery->addField('mt', 'entity_id');
    $where = db_or()->condition('mt.field_media_tag_target_id', $this->value, $operator);
    $last = "tn";

    if ($this->options['depth'] > 0) {
      $subquery->leftJoin('taxonomy_term_hierarchy', 'th', "th.tid = mt.field_media_tag_target_id");
      $last = "th";
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term_hierarchy', "th$count", "$last.parent = th$count.tid");
        $where->condition("th$count.tid", $this->value, $operator);
        $last = "th$count";
      }
    }
    elseif ($this->options['depth'] < 0) {
      foreach (range(1, abs($this->options['depth'])) as $count) {
        $subquery->leftJoin('taxonomy_term_hierarchy', "th$count", "$last.tid = th$count.parent");
        $where->condition("th$count.tid", $this->value, $operator);
        $last = "th$count";
      }
    }

    $subquery->condition($where);
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", $subquery, 'IN');
  }

}
