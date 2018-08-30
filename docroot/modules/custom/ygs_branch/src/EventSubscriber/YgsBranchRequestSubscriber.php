<?php

namespace Drupal\ygs_branch\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Class YgsBranchRequestSubscriber.
 *
 * @package Drupal\ygs_branch\EventSubscriber
 */
class YgsBranchRequestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * A method to be called whenever a kernel.request event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event triggered by the request.
   */
  public function onRequest(GetResponseEvent $event) {
    // Don't process events with HTTP exceptions.
    if ($event->getRequest()->get('exception') != NULL) {
      return;
    }

    // We won't go ahead if we have an entity form (i.e. we're adding/editing
    // an entity).
    if ($event->getRequest()->get('_entity_form') != NULL) {
      return;
    }

    // Check this request for node value.
    /** @var NodeInterface $node */
    $node = $event->getRequest()->get('node');
    if (!isset($node) || !($node instanceof NodeInterface)) {
      return;
    }

    // Check for node type.
    if ($node->getType() != 'branch') {
      return;
    }
    // Check for Coming Soon checkbox.
    if (!$node->get('field_location_state')->value) {
      return;
    }
    // Check for Temporary URL value.
    $temporary_url_values = $node->get('field_location_temp_url')->getValue();
    $temporary_url = reset($temporary_url_values);
    if (!$temporary_url['uri']) {
      return;
    }

    $url = Url::fromUri($temporary_url['uri'])->toString();
    // We need to use the TrustedRedirectResponse to be able to redirect to
    // external URLs.
    $response = new TrustedRedirectResponse($url);
    // Add cache dependency on node.
    $response->addCacheableDependency($node);
    $event->setResponse($response);
  }

}
