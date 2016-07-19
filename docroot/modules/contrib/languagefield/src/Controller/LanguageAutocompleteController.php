<?php

namespace Drupal\languagefield\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;


/**
 * Returns autocomplete responses for countries.
 */
class LanguageAutocompleteController {

  /**
   * Returns response for the language autocomplete widget.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @param string $field_name
   *   The name of the field with the autocomplete widget.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for languages.
   *
   * @see getMatches()
   */
  public function autocomplete(Request $request, $field_name) {
    $matches = $this->getMatches($request->query->get('q'), $field_name);
    return new JsonResponse($matches);
  }

  /**
   * Get matches for the autocompletion of languages.
   *
   * @param string $string
   *   The string to match for languages.
   * @param $string $field_name
   *   The name of the autocomplete field.
   *
   * @return array
   *   An array containing the matching languages.
   */
  public function getMatches($string, $field_name) {
    $matches = array();
    if ($string) {
      $languages = \Drupal::cache('data')->get('languagefield:languages:' . $field_name)->data;
      foreach ($languages as $langcode => $language) {
        if (strpos(Unicode::strtolower($language), Unicode::strtolower($string)) !== FALSE) {
          $matches[] = array('value' => $language, 'label' => $language);
        }
      }
    }
    return $matches;
  }
}
