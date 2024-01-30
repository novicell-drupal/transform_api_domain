<?php

namespace Drupal\transform_api_domain\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\domain\Entity\Domain;
use Drupal\transform_api_domain\TransformDomainService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Domain switcher' block.
 *
 * @Block(
 *   id = "domain_switcher_block",
 *   admin_label = @Translation("Domain switcher"),
 *   category = @Translation("Drupal Premium"),
 * )
 */
class DomainSwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected TransformDomainService $transformDomainService;

  protected AccountProxy $currentUser;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, TransformDomainService $transformDomainService, AccountProxy $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->transformDomainService = $transformDomainService;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('transform_api_domain.service'),
      $container->get('current_user')
    );
  }

  public function build() {
    $links = $this->buildDomainLinks();

    return [
      '#type' => 'dropbutton',
      '#dropbutton_type' => 'small',
      '#links' => $links,
      '#attached' => [
        'library' => [
          'transform_api_domain/admin-theme',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'user',
          'editor_session',
        ],
        'tags' => [
          'domain_list',
          'user:' . $this->currentUser->id(),
        ],
      ],
    ];
  }

  protected function buildDomainLinks(): array {
    $available_domains = $this->transformDomainService->getAvailableDomains();

    $current_domain = $this->transformDomainService->getCurrentDomain();

    // Put current domain first.
    $links[] = [
      'title' => $current_domain->label(),
      'url' => Url::fromRoute('transform_api_domain.change_domain', ['domain_id' => $current_domain->id()]),
    ];

    $available_domains = array_filter($available_domains, fn($domain) => $domain->id() !== $current_domain->id());

    /** @var Domain $domain */
    foreach ($available_domains as $domain) {
      $links[] = [
        'title' => $domain->label(),
        'url' => Url::fromRoute('transform_api_domain.change_domain', ['domain_id' => $domain->id()]),
      ];
    }

    return $links;
  }

}
