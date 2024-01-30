<?php

namespace Drupal\transform_api_domain\Plugin\LanguageNegotiation;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\transform_api_domain\TransformDomainNegotiator;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\language\LanguageSwitcherInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via request domain.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\transform_api_domain\Plugin\LanguageNegotiation\LanguageNegotiationRequestDomain::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *    \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *    \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = 100,
 *   name = @Translation("Request domain"),
 *   description = @Translation("Language from the request domain."),
 *   config_route_name = "language.negotiation_request_domain"
 * )
 */
class LanguageNegotiationRequestDomain extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface, InboundPathProcessorInterface, OutboundPathProcessorInterface, LanguageSwitcherInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'request-domain';

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiator
   */
  protected $domainNegotiator;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static();
    $instance->domainNegotiator = $container->get('domain.negotiator');
    $instance->pathMatcher = $container->get('path.matcher');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    if ($request && $this->languageManager && !is_null($this->domainNegotiator->getActiveDomain())) {
      if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER) || $request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT)) {
        $languages = $this->languageManager->getLanguages();
        $config = $this->config->get('language.negotiation')
          ->get('request_domain');
        $activeDomain = $this->domainNegotiator->getActiveDomain();

        // Get only the host, not the protocol.
        if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER)) {
          $http_host = parse_url($request->headers->get(TransformDomainNegotiator::FRONTEND_HEADER), PHP_URL_HOST);
        } else {
          $http_host = $request->get(TransformDomainNegotiator::QUERY_ARGUMENT);
        }
        foreach ($languages as $language) {
          // Skip the check if the language doesn't have a domain.
          if (!empty($config[$activeDomain->id()][$language->getId()])) {
            $host = $config[$activeDomain->id()][$language->getId()];
            if ($http_host == $host) {
              $langcode = $language->getId();
              break;
            }
          }
        }
      }
    }

    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($request && ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER) || $request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT))) {
      unset($options['prefix']);
      unset($options['query'][LanguageNegotiationContentEntity::QUERY_PARAMETER]);
      unset($options['query'][TransformDomainNegotiator::QUERY_ARGUMENT]);
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url) {
    $links = [];
    $query = [];
    parse_str($request->getQueryString() ?? '', $query);

    if ($request && $this->languageManager && !is_null($this->domainNegotiator->getActiveDomain())) {
      if ($request->headers->has(TransformDomainNegotiator::FRONTEND_HEADER) || $request->query->has(TransformDomainNegotiator::QUERY_ARGUMENT)) {
        $config = $this->config->get('language.negotiation')
          ->get('request_domain');
        $activeDomain = $this->domainNegotiator->getActiveDomain();
        foreach ($this->languageManager->getNativeLanguages() as $language) {
          if (!empty($config[$activeDomain->id()][$language->getId()])) {
            $link_url = clone $url;
            if ($this->pathMatcher->isFrontPage()) {
              $link_url = new Url('<front>');
            }
            $link_url->setOption('language', $language);
            $url_scheme = $request->isSecure() ? 'https://' : 'http://';
            $link_url->setOption('base_url', $url_scheme . $config[$activeDomain->id()][$language->getId()]);
            $links[$language->getId()] = [
              // We need to clone the $url object to avoid using the same one for all
              // links. When the links are rendered, options are set on the $url
              // object, so if we use the same one, they would be set for all links.
              'url' => $link_url,
              'title' => $language->getName(),
              'language' => $language,
              'attributes' => ['class' => ['language-link']],
              'query' => $query,
            ];
          }
        }
      }
    }

    return $links;
  }
}
