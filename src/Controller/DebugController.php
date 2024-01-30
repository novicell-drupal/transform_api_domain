<?php

namespace Drupal\transform_api_domain\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DebugController extends ControllerBase {

  public function debugRequest(Request $request) {
    \Drupal::logger('egmont')->info(json_encode($request->headers->all()));
    return new JsonResponse($request->headers->all());
  }

}
