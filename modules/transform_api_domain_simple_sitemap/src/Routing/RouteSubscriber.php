<?php

namespace Drupal\transform_api_domain_simple_sitemap\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Override the domain_simple_sitemap controller to add our cache context.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('simple_sitemap.sitemap_default')) {
      $route->setDefault('_controller', '\Drupal\transform_api_domain_simple_sitemap\Controller\TransformDomainSimpleSitemapController::getSitemap');
    }
  }

}
