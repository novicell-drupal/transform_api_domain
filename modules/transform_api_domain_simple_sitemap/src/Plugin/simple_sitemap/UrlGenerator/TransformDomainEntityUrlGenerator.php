<?php

namespace Drupal\transform_api_domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\domain_simple_sitemap\Plugin\simple_sitemap\UrlGenerator\DomainEntityUrlGenerator;
use Drupal\simple_sitemap\Entity\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Manager\EntityManager;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Settings;

/**
 * Generates URLs for entity bundles and bundle overrides.
 *
 * @UrlGenerator(
 *   id = "transform_domain_entity",
 *   label = @Translation("Transform Domain entity URL generator"),
 *   description = @Translation("Generates URLs for entity bundles and bundle
 *   overrides."),
 * )
 */
class TransformDomainEntityUrlGenerator extends DomainEntityUrlGenerator {

  protected array $urlLanguageMappings = [];
  protected array $requestDomainLanguageMappings = [];

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Logger $logger, Settings $settings, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EntityHelper $entity_helper, EntityManager $entities_manager, UrlGeneratorManager $url_generator_manager, MemoryCacheInterface $memory_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $settings, $language_manager, $entity_type_manager, $entity_helper, $entities_manager, $url_generator_manager, $memory_cache);

    $config = \Drupal::config('language.negotiation');

    $this->urlLanguageMappings = $config->get('url') ?? [];
    $this->requestDomainLanguageMappings = $config->get('request_domain') ?? [];
  }

  /**
   * Replace the base url with the custom domain base url if possible.
   *
   * @param string $url
   *   The url to replace the base url.
   * @param string $language_id
   *   ID of the language used to generate the URL.
   *
   * @return string
   *   The url with the base url replaced.
   */
  protected function replaceBaseUrlWithCustomLanguageBaseUrl(string $url, string $language_id): string {
    if (empty($this->requestDomainLanguageMappings)) {
      // If the transform_api_domain language negotiation is not used,
      // use the default domain base url.
      return $this->replaceBaseUrlWithCustom($url);
    }
    /** @var \Drupal\domain\DomainInterface $domain */
    $domain = \Drupal::service('domain.negotiator')->getActiveDomain();

    $domain_mapping = $this->requestDomainLanguageMappings[$domain->id()] ?? NULL;

    if (empty($domain_mapping) || empty($domain_mapping[$language_id])) {
      // If the domain is not in the mappings, use the default domain base url.
      return $this->replaceBaseUrlWithCustom($url);
    }

    $domain_base_url = $domain_mapping[$language_id];

    if (empty($this->urlLanguageMappings) || empty($this->urlLanguageMappings[$language_id])) {
      return str_replace($GLOBALS['base_url'], $domain->getScheme() . $domain_base_url, $url);
    }

    // When site is using url language negotiation,
    // generated urls are absolute and contain the base url.
    // We need to replace the base url with the custom domain base url.
    $base_url = $this->urlLanguageMappings[$language_id];

    return str_replace($base_url, $domain_base_url, $url);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlternateUrlsForAllLanguages(Url $url_object): array {
    $alternate_urls = [];
    if ($url_object->access($this->anonUser)) {
      foreach ($this->languages as $language) {
        if (!isset($this->settings->get('excluded_languages')[$language->getId()]) || $language->isDefault()) {
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustomLanguageBaseUrl($url_object
            ->setAbsolute()->setOption('language', $language)->toString(),
            $language->getId(),
          );
        }
      }
    }

    return $alternate_urls;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlternateUrlsForDefaultLanguage(Url $url_object): array {
    $alternate_urls = [];
    if ($url_object->access($this->anonUser)) {
      $alternate_urls[$this->defaultLanguageId] = $this->replaceBaseUrlWithCustomLanguageBaseUrl($url_object
        ->setAbsolute()->setOption('language', $this->languages[$this->defaultLanguageId])->toString(),
        $this->defaultLanguageId,
      );
    }

    return $alternate_urls;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlternateUrlsForTranslatedLanguages(ContentEntityInterface $entity, Url $url_object): array {
    $alternate_urls = [];

    foreach ($entity->getTranslationLanguages() as $language) {
      if (!isset($this->settings->get('excluded_languages')[$language->getId()]) || $language->isDefault()) {
        if ($entity->getTranslation($language->getId())->access('view', $this->anonUser)) {
          $alternate_urls[$language->getId()] = $this->replaceBaseUrlWithCustomLanguageBaseUrl($url_object
            ->setAbsolute()->setOption('language', $language)->toString(),
            $language->getId(),
          );
        }
      }
    }

    return $alternate_urls;
  }

}
