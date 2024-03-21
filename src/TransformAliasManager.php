<?php

namespace Drupal\transform_api_domain;

use Drupal\path_alias\AliasManager;

class TransformAliasManager extends AliasManager {

  public function getPathByAlias($alias, $langcode = NULL) {
    // Get the current language code if none is provided.
    // Since AliasManager::getPathByAlias() uses only URL language detection,
    // we're getting language from the domain.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage()->getId();

    return parent::getPathByAlias($alias, $langcode);
  }

  public function getAliasByPath($path, $langcode = NULL) {
    // Get the current language code if none is provided.
    // Since AliasManager::getAliasByPath() uses only URL language detection,
    // we're getting language from the domain.
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage()->getId();

    return parent::getAliasByPath($path, $langcode);
  }

}
