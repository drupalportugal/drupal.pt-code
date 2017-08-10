<?php

/**
 * @file
 * Provides the functionality and information about the
 * Name module name parsing engine.
 */

/**
 * Prepares variables for name-format-parameter-help template.
 *
 * @param $variables
 */
function template_preprocess_name_format_parameter_help(&$variables) {
  $variables['tokens'] = name_replacement_tokens();
}

/**
 * Provides the tokens that the name parse can handle.
 *
 * Todo:: make the labels generic
 */
function name_replacement_tokens() {
  $tokens = array(
    't' => t('Title'),
    'g' => t('Given name'),
    'm' => t('Middle name(s)'),
    'f' => t('Family name'),
    'c' => t('Credentials'),
    's' => t('Generational suffix'),
    'x' => t('First letter given'),
    'y' => t('First letter middle'),
    'z' => t('First letter family'),
    'e' => t('Conditional: Either the given or family name. Given name is given preference.'),
    'E' => t('Conditional: Either the given or family name. Family name is given preference.'),
    'i' => t('Separator 1'),
    'j' => t('Separator 2'),
    'k' => t('Separator 3'),
    '\\' => t('You can prevent a character in the format string from being expanded by escaping it with a preceding backslash.'),
    'L' => t('Modifier: Converts the next token to all lowercase.'),
    'U' => t('Modifier: Converts the next token to all uppercase.'),
    'F' => t('Modifier: Converts the first letter to uppercase.'),
    'G' => t('Modifier: Converts the first letter of ALL words to uppercase.'),
    'T' => t('Modifier: Trims whitespace around the next token.'),
    'S' => t('Modifier: Ensures that the next token is safe for the display.'),
    '+' => t('Conditional: Insert the token if both the surrounding tokens are not empty.'),
    '-' => t('Conditional: Insert the token if the previous token is not empty'),
    '~' => t('Conditional: Insert the token if the previous token is empty'),
    '=' => t('Conditional: Insert the token if the next token is not empty.'),
    '^' => t('Conditional: Insert the token if the next token is empty.'),
    '|' => t('Conditional: Uses the previous token unless empty, otherwise it uses this token.'),
    '(' => t('Group: Start of token grouping.'),
    ')' => t('Group: End of token grouping.'),
    /*

 // Placeholders for token support insertion on the [object / key | entity / bundle].
 '1' => t('Token placeholder 1'),
 '2' => t('Token placeholder 2'),
 '3' => t('Token placeholder 3'),
 '4' => t('Token placeholder 4'),
 '5' => t('Token placeholder 5'),
 '6' => t('Token placeholder 6'),
 '7' => t('Token placeholder 7'),
 '8' => t('Token placeholder 8'),
 '9' => t('Token placeholder 9'),

 */
  );

  return $tokens;
}

