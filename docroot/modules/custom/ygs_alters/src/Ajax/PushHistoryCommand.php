<?php

namespace Drupal\ygs_alters\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for pushing query part to url.
 *
 * @ingroup ajax
 */
class PushHistoryCommand implements CommandInterface {

  /**
   * The query part of url to be set.
   *
   * @var string
   */
  protected $query;

  /**
   * Constructs a PushHistoryCommand object.
   *
   * @param string $query
   *   An url query part.
   */
  public function __construct($query) {
    $this->query = $query;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return array(
      'command' => 'pushHistory',
      'url' => $this->query,
    );
  }

}
