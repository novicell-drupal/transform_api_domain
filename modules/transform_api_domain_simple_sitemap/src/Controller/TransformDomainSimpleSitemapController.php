<?php

namespace Drupal\transform_api_domain_simple_sitemap\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\domain_simple_sitemap\Controller\DomainSimpleSitemapController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransformDomainSimpleSitemapController extends DomainSimpleSitemapController {

  /**
   * {@inheritdoc}
   */
  public function getSitemap(Request $request, ?string $variant = NULL): Response {
    $response = parent::getSitemap($request, $variant);

    // Add transform_api_domain cache context.
    if ($response instanceof CacheableResponse) {
      $response->getCacheableMetadata()
        ->addCacheContexts(['domain']);
    }

    return $response;
  }

}
