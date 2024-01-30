<?php

namespace Drupal\transform_api_domain\Cache\Context;

use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\transform_api_domain\TransformDomainService;

/**
 * Defines the EgmontSessionCacheContext service, for "per domain" caching.
 *
 * Cache context ID: 'egmont_session'.
 */
class EditorSessionCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Egmont session');
  }

  public function getContext() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->hasSession()) {
      return $request->getSession()->get(TransformDomainService::SESSION_KEY);
    }
    return 'none';
  }
}
