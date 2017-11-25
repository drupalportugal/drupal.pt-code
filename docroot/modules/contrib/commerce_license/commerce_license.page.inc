<?php

/**
 * @file
 * Contains commerce_license.page.inc.
 *
 * Page callback for License entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for License templates.
 *
 * Default template: commerce_license.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_license(array &$variables) {
  // Fetch License Entity Object.
  $commerce_license = $variables['elements']['#commerce_license'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
