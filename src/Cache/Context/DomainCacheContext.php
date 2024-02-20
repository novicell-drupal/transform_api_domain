<?php

namespace Drupal\transform_api_domain\Cache\Context;

use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\transform_api_domain\TransformDomainNegotiator;
use Drupal\transform_api_domain\TransformDomainService;

/**
 * Defines the DomainCacheContext service, for "per domain" caching.
 *
 * Cache context ID: 'domain'.
 */
class DomainCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Domain');
  }

  public function getContext() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER)) {
      return $request->headers->get(TransformDomainNegotiator::FRONTEND_HEADER);
    } elseif ($request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT)) {
      return $request->query->get(TransformDomainNegotiator::QUERY_ARGUMENT);
    } elseif ($request->hasSession()) {
      return $request->getSession()->get(TransformDomainService::SESSION_KEY);
    }
    return $request->getSchemeAndHttpHost() . $request->getBaseUrl();
  }
}
