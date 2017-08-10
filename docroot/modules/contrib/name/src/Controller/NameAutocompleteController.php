<?php

/**
 * @file
 * Contains \Drupal\name\Controller\NameAutocompleteController.
 */

namespace Drupal\name\Controller;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\name\NameAutocomplete;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for name autocompletion routes.
 */
class NameAutocompleteController implements ContainerInjectionInterface {

  /**
   * The name autocomplete helper class to find matching name values.
   *
   * @var \Drupal\name\NameAutocomplete
   */
  protected $nameAutocomplete;

  /**
   * Constructs an NameAutocompleteController object.
   *
   * @param \Drupal\name\NameAutocomplete $name_autocomplete
   *   The name autocomplete helper class to find matching name values.
   */
  public function __construct(NameAutocomplete $name_autocomplete, EntityManagerInterface $entity_manager) {
    $this->nameAutocomplete = $name_autocomplete;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('name.autocomplete'),
      $container->get('entity.manager')
    );
  }

  /**
   * Returns response for the name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   *
   * @see \Drupal\name\NameAutocomplete::getMatches()
   */
  public function autocomplete(Request $request, $field_name, $entity_type, $bundle, $component) {
    $definitions = $this->entityManager->getFieldDefinitions($entity_type, $bundle);

    if (!isset($definitions[$field_name])) {
      throw new AccessDeniedHttpException();
    }

    $field_definition = $definitions[$field_name];
    $access_control_handler = $this->entityManager->getAccessControlHandler($entity_type);
    if ($field_definition->getType() != 'name' || !$access_control_handler->fieldAccess('edit', $field_definition)) {
      throw new AccessDeniedHttpException();
    }

    $matches = $this->nameAutocomplete->getMatches($field_definition, $component, $request->query->get('q'));
    return new JsonResponse($matches);
  }
}
