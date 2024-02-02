<?php

namespace Drupal\transform_api_domain\Plugin\Transform\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\domain\Entity\Domain;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\transform_api\TransformBlockBase;
use Drupal\transform_api_domain\TransformDomainService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a domain menu block.
 *
 * @TransformBlock(
 *   id = "domain_menu_block",
 *   admin_label = @Translation("Domain menu"),
 *   category = @Translation("Domain"),
 * )
 */

class DomainMenuTransformBlock extends TransformBlockBase {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * Transform API domain service.
   *
   * @var \Drupal\transform_api_domain\TransformDomainService
   */
  protected TransformDomainService $transformDomainService;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $menuStorage;

  /**
   * Constructs a new SystemMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\transform_api_domain\TransformDomainService $transform_domain_service
   *   Transform API domain service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail, TransformDomainService $transform_domain_service, EntityStorageInterface $menu_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
    $this->transformDomainService = $transform_domain_service;
    $this->menuStorage = $menu_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('transform_api_domain.service'),
      $container->get('entity_type.manager')->getStorage('menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'domains' => []
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;
    $available_domains = $this->transformDomainService->getAvailableDomains();

    $form['domains'] = [
      '#type' => 'details',
      '#title' => $this->t('Domains'),
      '#open' => TRUE,
      '#tree' => TRUE
    ];
    $menu_options = [];
    foreach ($this->menuStorage->loadMultiple() as $menu => $entity) {
      $menu_options[$menu] = $entity->label();
    }
    /** @var Domain $domain */
    foreach ($available_domains as $domain) {
      $form['domains'][$domain->id()] = [
        '#type' => 'select',
        '#title' => $domain->label(),
        '#options' => $menu_options,
        '#default_value' => $config['domains'][$domain->id()] ?? '',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['domains'] = $form_state->getValue('domains');
  }

  /**
   * {@inheritdoc}
   */
  public function transform() {
    $activeDomain = $this->transformDomainService->getCurrentDomain();
    $menu_name = $this->configuration['domains'][$activeDomain->id()];
    if ($this->configuration['expand_all_items']) {
      $parameters = new MenuTreeParameters();
      $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
      $parameters->setActiveTrail($active_trail);
    }
    else {
      $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    }

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence, this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    if ($level > 1) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuTree->maxDepth()));
        }
      }
      else {
        return [];
      }
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);
    $cacheMetadata = CacheableMetadata::createFromRenderArray($build);
    $cacheMetadata->addCacheContexts(['url.site']);
    $transformation = [
      'type' => 'menu',
      'menu_name' => $build['#menu_name'] ?? $menu_name,
      'items' => $this->transformMenuItems($build['#items'] ?? []),
    ];
    $cacheMetadata->applyTo($transformation);
    return $transformation;
  }

  /**
   * Take an array of menu items and transform them.
   *
   * @param array $items
   *   Array of menu items.
   *
   * @return array
   *   The JSON array.
   */
  protected function transformMenuItems(array $items): array {
    $result = [];
    foreach ($items as $array) {
      $item = $array;
      /** @var \Drupal\Core\Url $url */
      $url = $array['url'];
      $item['url'] = $url->toString();
      $item['url_options'] = $url->getOptions();
      /** @var Attribute $attributes */
      $attributes = $array['attributes'];
      /** @var MenuLinkContent $original */
      $original = $array['original_link'];
      $item['description'] = $original->getDescription();
      $item['attributes'] = $attributes->toArray();
      $item['#original_link'] = $item['original_link'];
      unset($item['original_link']);
      $item['below'] = $this->transformMenuItems($array['below']);
      $result[] = $item;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Even when the menu block renders to the empty string for a user, we want
    // the cache tag for this menu to be set: whenever the menu is changed, this
    // menu block must also be re-rendered for that user, because maybe a menu
    // link that is accessible for that user has been added.
    $activeDomain = $this->transformDomainService->getCurrentDomain();
    $menu_name = $this->configuration['domains'][$activeDomain->id()];
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:system.menu.' . $menu_name;
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // ::build() uses MenuLinkTreeInterface::getCurrentRouteMenuTreeParameters()
    // to generate menu tree parameters, and those take the active menu trail
    // into account. Therefore, we must vary the rendered menu by the active
    // trail of the rendered menu.
    // Additional cache contexts, e.g. those that determine link text or
    // accessibility of a menu, will be bubbled automatically.
    $activeDomain = $this->transformDomainService->getCurrentDomain();
    $menu_name = $this->configuration['domains'][$activeDomain->id()];
    return Cache::mergeContexts(parent::getCacheContexts(), ['domain', 'route.menu_active_trails:' . $menu_name]);
  }
}
