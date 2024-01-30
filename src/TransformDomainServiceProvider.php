<?php

namespace Drupal\transform_api_domain;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class TransformDomainServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    // Replace the DomainNegotiator service with our own implementation.
    $definition = $container->getDefinition('domain.negotiator');
    $definition->setClass(TransformDomainNegotiator::class);

  }

}
