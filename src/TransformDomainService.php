<?php

namespace Drupal\transform_api_domain;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain\Entity\Domain;
use Drupal\domain_access\DomainAccessManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

class TransformDomainService {

  public const SESSION_KEY = 'editor_domain';

  protected EntityTypeManagerInterface $entityTypeManager;

  protected AccountProxy $currentUser;

  protected DomainAccessManagerInterface $domainAccessManager;

  protected DomainNegotiatorInterface $domainNegotiator;

  protected RequestStack $requestStack;

  protected ?User $userEntity = NULL;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user, DomainAccessManagerInterface $domain_access_manager, DomainNegotiatorInterface $domain_negotiator, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->domainAccessManager = $domain_access_manager;
    $this->domainNegotiator = $domain_negotiator;
    $this->requestStack = $request_stack;
  }

  public function getAvailableDomains(): array {
    /** @var \Drupal\domain\DomainInterface $domain */
    $domains = $this->entityTypeManager
      ->getStorage('domain')
      ->loadMultipleSorted();

    $available_domains = [];

    foreach ($domains as $domain) {
      if ($this->userHasAccess($domain)) {
        $available_domains[] = $domain;
      }
    }

    return $available_domains;
  }

  public function getCurrentDomain(): Domain {
    return $this->domainNegotiator->getActiveDomain();
  }

  public function setActiveDomain($domain_id): bool {
    /** @var Domain $domain */
    $domain = $this->entityTypeManager
      ->getStorage('domain')
      ->load($domain_id);

    if (!$domain || !$this->userHasAccess($domain)) {
      return FALSE;
    }

    $session = $this->requestStack->getSession();

    if (!$session->isStarted()) {
      $session->start();
    }

    $session->set(self::SESSION_KEY, $domain->getHostname());

    return TRUE;
  }

  public function userHasAccess(Domain $domain): bool {
    if (!$this->currentUser->isAuthenticated()) {
      return FALSE;
    }

    if ($this->currentUser->hasRole('administrator') || $this->currentUser->hasPermission('view domain list')) {
      return TRUE;
    }

    $user = $this->getUserEntity();

    if ($user === NULL) {
      return FALSE;
    }

    if ($user->get('field_domain_all_affiliates')->getString() == '1') {
      return TRUE;
    }

    $user_domains = $user->get('field_domain_access')->getValue();

    foreach ($user_domains as $user_domain) {
      if ($user_domain['target_id'] == $domain->id()) {
        return TRUE;
      }
    }

    // Check field domain admin
    $user_domains = $user->get('field_domain_admin')->getValue();

    foreach ($user_domains as $user_domain) {
      if ($user_domain['target_id'] == $domain->id()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  protected function getUserEntity(): ?User {
    if (!isset($this->userEntity)) {
      $this->userEntity = $this->entityTypeManager
        ->getStorage('user')
        ->load($this->currentUser->id());
    }

    return $this->userEntity;
  }

}
