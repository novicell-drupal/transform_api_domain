<?php

namespace Drupal\transform_api_domain\Cache\Context;

use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\transform_api_domain\TransformDomainService;

/**
 * Defines the EditorSessionCacheContext service, for "per domain" caching.
 *
 * Cache context ID: 'editor_session'.
 */
class EditorSessionCacheContext extends RequestStackCacheContextBase {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Editor session');
  }

  public function getContext() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->hasSession()) {
      return $request->getSession()->get(TransformDomainService::SESSION_KEY);
    }
    return 'none';
  }
}
