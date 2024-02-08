<?php

namespace Drupal\transform_api_domain\Entity;

use Drupal\domain_simple_sitemap\Entity\DomainSimpleSitemap;
use Drupal\transform_api_domain\Plugin\LanguageNegotiation\LanguageNegotiationRequestDomain;

class TransformDomainSimpleSitemap extends DomainSimpleSitemap {

  public function isMultilingual(): bool {
    if (!\Drupal::moduleHandler()->moduleExists('language')) {
      return FALSE;
    }

    /** @var \Drupal\language\LanguageNegotiatorInterface $language_negotiator */
    $language_negotiator = \Drupal::service('language_negotiator');

    $has_transform_language = $language_negotiator
      ->isNegotiationMethodEnabled(LanguageNegotiationRequestDomain::METHOD_ID);

    if ($has_transform_language) {
      return TRUE;
    }

    return parent::isMultilingual();
  }

}
