<?php

/**
 * @file
 * Contains \Drupal\name\NameFormatParser.
 */

namespace Drupal\name;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;

/**
 * Main class that formats a name from an array of components.
 *
 * @param array $name_components
 *   A keyed array of name components.
 *   These are: title, given, middle, family, generational and credentials.
 * @param string $format
 *   The string specifying what format to use.
 * @param array $settings
 *   A keyed array of additional parameters to pass into the function.
 *   Includes:
 *   - 'object' An object or array.
 *     This entity is used for Token module substitutions.
 *     Currently not used.
 *   - 'type' - A string.
 *     The entity identifier: node, user, etc
 */
class NameFormatParser {

  /**
   * TODO: Look at replacing the raw string functions with the Drupal equivalent
   * functions. Will need to test this carefully...
   *
   * Move this parser to a proper service.
   */
  public static function parse($name_components, $format = '', $settings = array(), $tokens = NULL) {
    foreach (array('sep1', 'sep2', 'sep3') as $key) {
      if (!isset($settings[$key])) {
        $config = \Drupal::config('name.settings')->get();
        $settings[$key] = $config[$key];
      }
    }
    $parser = new self();
    return $parser->format($name_components, $format, $settings, $tokens);
  }

  public function format($name_components, $format = '', $settings = array(), $tokens = NULL) {
    if (empty($format)) {
      return '';
    }

    if (!isset($tokens)) {
      $tokens = $this->generateTokens($name_components, $settings);
    }

    // Neutralise any escaped backslashes.
    $format = str_replace('\\\\', "\t", $format);

    $pieces = array();
    $len = strlen($format);
    $modifiers = '';
    $conditions = '';
    $depth = 0;
    for ($i = 0; $i < strlen($format); $i++) {
      $char = $format{$i};
      $last_char = ($i > 0) ? $format{$i - 1} : FALSE;
      $next_char = ($i < $len - 2) ? $format{$i + 1} : FALSE;

      // Handle escaped letters.
      if ($char == '\\') {
        continue;
      }
      if ($last_char == '\\') {
        $pieces[] = $this->addComponent($char, $modifiers, $conditions);
        continue;
      }

      switch ($char) {
        case 'L':
        case 'U':
        case 'F':
        case 'T':
        case 'S':
        case 'G':
          $modifiers .= $char;
          break;

        case '=':
        case '^':
        case '|':
        case '+':
        case '-':
        case '~':
          $conditions .= $char;
          break;

        case '(':
        case ')':
          $remaining_string = substr($format, $i);
          if ($char == '(' && $closing_bracket = $this->closingBracketPosition($remaining_string)) {
            $sub_string = $this->format($tokens, substr($format, $i + 1, $closing_bracket - 1), $settings, $tokens);

            // Increment the counter past the closing bracket.
            $i += $closing_bracket;
            $pieces[] = $this->addComponent($sub_string, $modifiers, $conditions);
          }
          else {
            // Unmatched, add it.
            $pieces[] = $this->addComponent($char, $modifiers, $conditions);
          }
          break;

        default:
          if (array_key_exists($char, $tokens)) {
            $char = $tokens[$char];
          }
          $pieces[] = $this->addComponent($char, $modifiers, $conditions);
          break;
      }
    }

    $parsed_pieces = array();
    for ($i = 0; $i < count($pieces); $i++) {
      $component = $pieces[$i]['value'];
      $conditions = $pieces[$i]['conditions'];

      $last_component = ($i > 0) ? $pieces[$i - 1]['value'] : FALSE;
      $next_component = ($i < count($pieces) - 1) ? $pieces[$i + 1]['value'] : FALSE;

      if (empty($conditions)) {
        $parsed_pieces[$i] = $component;
      }
      else {
        // Modifier: Conditional insertion. Insert if both the surrounding tokens are not empty.
        if (strpos($conditions, '+') !== FALSE && !empty($last_component) && !empty($next_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Conditional insertion. Insert if the previous token is not empty.
        if (strpos($conditions, '-') !== FALSE && !empty($last_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Conditional insertion. Insert if the previous token is empty.
        if (strpos($conditions, '~') !== FALSE && empty($last_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Insert the token if the next token is empty.
        if (strpos($conditions, '^') !== FALSE && empty($next_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Insert the token if the next token is not empty.
        // This overrides the above two settings.
        if (strpos($conditions, '=') !== FALSE && !empty($next_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Conditional insertion. Uses the previous token unless empty, otherwise insert this token.
        if (strpos($conditions, '|') !== FALSE) {
          if (empty($last_component)) {
            $parsed_pieces[$i] = $component;
          }
          else {
            unset($parsed_pieces[$i]);
          }
        }

      }
    }
    return str_replace('\\\\', "\t", implode('', $parsed_pieces));
  }

  protected function addComponent($string, &$modifiers = '', &$conditions = '') {
    $string = $this->applyModifiers($string, $modifiers);
    $piece = array(
      'value' => $string,
      'conditions' => $conditions,
    );
    $conditions = '';
    $modifiers = '';
    return $piece;
  }

  protected function applyModifiers($string, $modifiers) {
    if (!is_null($string) || strlen($string)) {
      if ($modifiers) {
        $original_string = $string;
        $prefix = '';
        $suffix = '';
        if (preg_match('/^(<span[^>]*>)(.*)(<\/span>)$/i', $string, $matches)) {
          $prefix = $matches[1];
          $string = $matches[2];
          $suffix = $matches[3];
        }

        for ($j = 0; $j < strlen($modifiers); $j++) {
          switch ($modifiers{$j}) {
            case 'L':
              $string = Unicode::strtolower($string);
              break;
            case 'U':
              $string = Unicode::strtoupper($string);
              break;
            case 'F':
              $string = Unicode::ucfirst($string);
              break;
            case 'G':
              if (!empty($string)) {
                $parts = explode(' ', $string);
                $string = array();
                foreach ($parts as $part) {
                  $string[] = Unicode::ucfirst($part);
                }
                $string = implode(' ', $string);
              }
              break;
            case 'T':
              $string = trim($string);
              break;
            case 'S':
              $string = SafeMarkup::checkPlain($string);
              break;
          }
        }
        $string = $prefix . $string . $suffix;
      }
    }
    return $string;
  }

  /**
   * Helper function to put out the first matched bracket position.
   *
   * Accepts strings in the format, ^ marks the matched bracket.
   *   '(xxx^)xxx(xxxx)xxxx' or '(xxx(xxx(xxxx))xxx^)'
   */
  protected function closingBracketPosition($string) {
    // Simplify the string by removing escaped brackets.
    $depth = 0;
    $string = str_replace(array('\(', '\)'), array('__', '__'), $string);
    for ($i = 0; $i < strlen($string); $i++) {
      $char = $string{$i};
      if ($char == '(') {
        $depth++;
      }
      elseif ($char == ')') {
        $depth--;
        if ($depth == 0) {
          return $i;
        }
      }
    }
    return FALSE;
  }

  protected function generateTokens($name_components, $settings = array()) {
    $name_components = (array) $name_components;
    $markup = !empty($settings['markup']);
    $name_components += array(
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'credentials' => '',
      'generational' => '',
    );
    $tokens = array(
      't' => $this->renderComponent($name_components['title'], 'title', $markup),
      'g' => $this->renderComponent($name_components['given'], 'given', $markup),
      'm' => $this->renderComponent($name_components['middle'], 'middle', $markup),
      'f' => $this->renderComponent($name_components['family'], 'family', $markup),
      'c' => $this->renderComponent($name_components['credentials'], 'credentials', $markup),
      's' => $this->renderComponent($name_components['generational'], 'generational', $markup),
      'x' => $this->renderComponent($name_components['given'], 'given', $markup, 'initial'),
      'y' => $this->renderComponent($name_components['middle'], 'middle', $markup, 'initial'),
      'z' => $this->renderComponent($name_components['family'], 'family', $markup, 'initial'),
      'i' => $settings['sep1'],
      'j' => $settings['sep2'],
      'k' => $settings['sep3'],
    );
    $given = $tokens['g'];
    $family = $tokens['f'];
    if ($given || $family) {
      $tokens += array(
        'e' => $given ? $given : $family,
        'E' => $family ? $family : $given,
      );
    }
    else {
      $tokens += array(
        'e' => NULL,
        'E' => NULL,
      );
    }
    return $tokens;
  }

  /**
   * Renders a name component value.
   *
   * This function does not by default sanitize the output unless the markup
   * flag is set. If this is set, it runs the component through check_plain() and
   * wraps the component in a span with the component name set as the class.
   */
  public static function renderComponent($value, $component_key, $markup, $modifier = NULL) {
    if (empty($value) || !Unicode::strlen($value)) {
      return NULL;
    }
    switch ($modifier) {
      case 'initial':
        $value = Unicode::substr($value, 0, 1);
        break;

    }
    if ($markup) {
      return '<span class="' . SafeMarkup::checkPlain($component_key) . '">' . SafeMarkup::checkPlain($value) . '</span>';
    }
    return $value;
  }

}
