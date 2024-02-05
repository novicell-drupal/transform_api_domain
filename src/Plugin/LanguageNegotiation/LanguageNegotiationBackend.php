<?php

namespace Drupal\transform_api_domain\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\language\LanguageSwitcherInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\transform_api_domain\TransformDomainNegotiator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via URL prefix or domain.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\transform_api_domain\Plugin\LanguageNegotiation\LanguageNegotiationBackend::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -7,
 *   name = @Translation("Backend URL"),
 *   description = @Translation("Language from the URL (Path prefix or domain)."),
 *   config_route_name = "language.negotiation_url"
 * )
 */
class LanguageNegotiationBackend extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-backend';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    if ($request && $this->languageManager) {
      if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER) || $request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT)) {
        return NULL;
      }
    }
    return parent::getLangcode($request);
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($request && $this->languageManager) {
      if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER) || $request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT)) {
        return $path;
      }
    }
    return parent::processInbound($path, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($request && $this->languageManager) {
      if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER) || $request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT)) {
        return $path;
      }
    }
    return parent::processOutbound($path, $options, $request, $bubbleable_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url) {
    if ($request && $this->languageManager) {
      if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER) || $request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT)) {
        return [];
      }
    }
    return parent::getLanguageSwitchLinks($request, $type, $url);
  }

}
