<?php

namespace Drupal\ygs_page_cache\StackMiddleware;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Drupal\page_cache\StackMiddleware\PageCache;

/**
 * Executes the page caching before the main kernel takes over the request.
 */
class YgsPageCache extends PageCache {

  /**
   * {@inheritdoc}
   */
  protected function fetch(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $response = $this->httpKernel->handle($request, $type, $catch);

    // Drupal's primary cache invalidation architecture is cache tags: any
    // response that varies by a configuration value or data in a content
    // entity should have cache tags, to allow for instant cache invalidation
    // when that data is updated. However, HTTP does not standardize how to
    // encode cache tags in a response. Different CDNs implement their own
    // approaches, and configurable reverse proxies (e.g., Varnish) allow for
    // custom implementations. To keep Drupal's internal page cache simple, we
    // only cache CacheableResponseInterface responses, since those provide a
    // defined API for retrieving cache tags. For responses that do not
    // implement CacheableResponseInterface, there's no easy way to distinguish
    // responses that truly don't depend on any site data from responses that
    // contain invalidation information customized to a particular proxy or
    // CDN.
    // - Drupal modules are encouraged to use CacheableResponseInterface
    //   responses where possible and to leave the encoding of that information
    //   into response headers to the corresponding proxy/CDN integration
    //   modules.
    // - Custom applications that wish to provide internal page cache support
    //   for responses that do not implement CacheableResponseInterface may do
    //   so by replacing/extending this middleware service or adding another
    //   one.
    if (!$response instanceof CacheableResponseInterface) {
      return $response;
    }

    // Currently it is not possible to cache binary file or streamed responses:
    // https://github.com/symfony/symfony/issues/9128#issuecomment-25088678.
    // Therefore exclude them, even for subclasses that implement
    // CacheableResponseInterface.
    if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
      return $response;
    }

    // Allow policy rules to further restrict which responses to cache.
    if ($this->responsePolicy->check($response, $request) === ResponsePolicyInterface::DENY) {
      return $response;
    }

    $request_time = $request->server->get('REQUEST_TIME');
    // The response passes all of the above checks, so cache it. Page cache
    // entries default to Cache::PERMANENT since they will be expired via cache
    // tags locally. Because of this, page cache ignores max age.
    // - Get the tags from CacheableResponseInterface per the earlier comments.
    // - Get the time expiration from the Expires header, rather than the
    //   interface, but see https://www.drupal.org/node/2352009 about possibly
    //   changing that.
    $expire = 0;
    // 403 and 404 responses can fill non-LRU cache backends and generally are
    // likely to have a low cache hit rate. So do not cache them permanently.
    if ($response->isClientError()) {
      // Cache for an hour by default. If the 'cache_ttl_4xx' setting is
      // set to 0 then do not cache the response.
      $cache_ttl_4xx = Settings::get('cache_ttl_4xx', 3600);
      if ($cache_ttl_4xx > 0) {
        $expire = $request_time + $cache_ttl_4xx;
      }
    }
    else {
      $date = $response->getExpires()->getTimestamp();
      // The 2 lines below is the only change to the original fetch() function.
      $config = \Drupal::config('system.performance');
      $expire = ($date > $request_time) ? $date : ($request_time + $config->get('cache.page.max_age'));
    }

    if ($expire === Cache::PERMANENT || $expire > $request_time) {
      $tags = $response->getCacheableMetadata()->getCacheTags();
      $this->set($request, $response, $expire, $tags);
    }

    // Mark response as a cache miss.
    $response->headers->set('X-Drupal-Cache', 'MISS');

    return $response;
  }

}
