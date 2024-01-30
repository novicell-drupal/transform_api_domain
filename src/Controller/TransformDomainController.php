<?php

namespace Drupal\transform_api_domain\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\transform_api_domain\TransformDomainService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TransformDomainController extends ControllerBase {

  protected TransformDomainService $transformDomainService;

  public function __construct(TransformDomainService $transformDomainService) {
    $this->transformDomainService = $transformDomainService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transform_api_domain.service')
    );
  }

  public function changeDomain($domain_id) {
    $success = $this->transformDomainService->setActiveDomain($domain_id);

    if ($success) {
      $this->messenger()->addStatus($this->t('Domain changed.'));
    } else {
      $this->messenger()->addError($this->t('You do not have access to this domain.'));
    }

    return $this->redirectBack();
  }

  public function redirectBack() {
    $source_url = \Drupal::request()->server->get('HTTP_REFERER');
    return new RedirectResponse($source_url);
  }

}
