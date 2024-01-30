<?php

namespace Drupal\transform_api_domain;

use Drupal\domain\DomainNegotiator;

class TransformDomainNegotiator extends DomainNegotiator {

  public const FRONTEND_HEADER = 'X-Host';
  public const QUERY_ARGUMENT = 'host';

  public function negotiateActiveHostname() {
    $request = $this->requestStack->getCurrentRequest();

    if ($request->hasSession()) {
      $session_domain = $request->getSession()->get(TransformDomainService::SESSION_KEY);

      if ($session_domain != NULL) {
        return $session_domain;
      }
    }

    if ($request->headers->has(self::FRONTEND_HEADER)) {
      $hostname = parse_url($request->headers->get(self::FRONTEND_HEADER), PHP_URL_HOST);;
      return $this->domainStorage()->prepareHostname($hostname);
    } elseif ($request->query->has(self::QUERY_ARGUMENT)) {
      $hostname = $request->query->get(self::QUERY_ARGUMENT);
      return $this->domainStorage()->prepareHostname($hostname);
    }

    return parent::negotiateActiveHostname();
  }

}
