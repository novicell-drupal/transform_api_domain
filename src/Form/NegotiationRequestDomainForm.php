<?php

namespace Drupal\transform_api_domain\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\Entity\Domain;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\transform_api_domain\TransformDomainService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NegotiationRequestDomainForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\transform_api_domain\TransformDomainService
   */
  protected TransformDomainService $transformDomainService;

  /**
   * Constructs a new NegotiationUrlForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\transform_api_domain\TransformDomainService $transformDomainService
   *   The transform api domain service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, TransformDomainService $transformDomainService) {
    parent::__construct($config_factory);
    $this->languageManager = $language_manager;
    $this->transformDomainService = $transformDomainService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('transform_api_domain.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_request_domain_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('language.negotiation');
    $available_domains = $this->transformDomainService->getAvailableDomains();

    $languages = $this->languageManager->getLanguages();
    $request_domain = $config->get('request_domain');
    /** @var Domain $domain */
    foreach ($available_domains as $domain) {
      $form[$domain->id()] = [
        '#prefix' => '<h2>',
        '#markup' => $domain->label(),
        '#suffix' => '</h2>',
        '#tree' => TRUE,
      ];
      foreach ($languages as $langcode => $language) {
        $form[$domain->id()][$langcode] = [
          '#type' => 'textfield',
          '#title' => $this->t('%language (%langcode) domain', [
            '%language' => $language->getName(),
            '%langcode' => $language->getId()
          ]),
          '#maxlength' => 128,
          '#default_value' => $request_domain[$domain->id()][$langcode] ?? '',
        ];
      }
    }

    $form_state->setRedirect('language.negotiation');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();
    $available_domains = $this->transformDomainService->getAvailableDomains();

    /** @var Domain $domain */
    foreach ($available_domains as $domain) {
      // Count repeated values for uniqueness check.
      $count = array_count_values($form_state->getValue($domain->id()));
      foreach ($languages as $langcode => $language) {
        $value = $form_state->getValue([$domain->id(), $langcode]);

        if ($value === '') {
          if ($form_state->getValue('language_negotiation_url_part') == LanguageNegotiationUrl::CONFIG_DOMAIN) {
            // Throw a form error if the domain is blank for a non-default language,
            // although it is required for selected negotiation type.
            $form_state->setErrorByName($domain->id() . "][$langcode", $this->t('The domain may not be left blank for %language.', ['%language' => $language->getName()]));
          }
        }
        elseif (isset($count[$value]) && $count[$value] > 1) {
          // Throw a form error if there are two languages with the same
          // domain/domain.
          $form_state->setErrorByName($domain->id() . "][$langcode", $this->t('The domain for %language, %value, is not unique.', [
            '%language' => $language->getName(),
            '%value' => $value
          ]));
        }
      }

      // Domain names should not contain protocol and/or ports.
      foreach ($languages as $langcode => $language) {
        $value = $form_state->getValue([$domain->id(), $langcode]);
        if (!empty($value)) {
          // Ensure we have exactly one protocol when checking the hostname.
          $host = 'http://' . str_replace(['http://', 'https://'], '', $value);
          if (parse_url($host, PHP_URL_HOST) != $value) {
            $form_state->setErrorByName($domain->id() . "][$langcode", $this->t('The domain for %language may only contain the domain name, not a trailing slash, protocol and/or port.', ['%language' => $language->getName()]));
          }
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();
    $available_domains = $this->transformDomainService->getAvailableDomains();

    $request_domain = [];
    /** @var Domain $domain */
    foreach ($available_domains as $domain) {
      $request_domain[$domain->id()] = [];
      foreach ($languages as $langcode => $language) {
        $request_domain[$domain->id()][$langcode] = $form_state->getValue([$domain->id(), $langcode]);
      }
    }
    $this->config('language.negotiation')
      ->set('request_domain', $request_domain)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
