<?php

/**
 * @file
 * Contains \Drupal\name\Test\NameFormatParserTest.
 */

namespace Drupal\name\Tests;

use Drupal\name\NameFormatParser;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the name formatter.
 *
 * @group name
 */
class NameFormatParserTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'NameFormatterParser Test',
      'description' => 'Test NameFormatParser',
      'group' => 'Name',
    );
  }

  /**
   * Helper function to provide data for testParser.
   *
   * @return array
   */
  public function names() {
    return array(
      'given' => array(
        'components' => array('given' => 'John'),
        'tests' => array(
          // Test that only the given name creates a entry
          't' => '', // Title
          'g' => 'John', // Given name
          '\g' => 'g', // Escaped letter
          'm' => '', // Middle name(s)
          'f' => '', // Family name
          'c' => '', // Credentials
          's' => '', // Generational suffix
          'x' => 'J', // First letter given
          'y' => '', // First letter middle
          'z' => '', // First letter family
          'e' => 'John', // Either the given or family name. Given name is given preference
          'E' => 'John', // Either the given or family name. Family name is given preference
          // Combination test
          'g f' => 'John ', // Using a single space
          'gif' => 'John ', // Separator 1
          'gjf' => 'John, ', // Separator 2
          'gkf' => 'John', // Separator 3
          'f g' => ' John',
          'fig' => ' John',
          'fjg' => ', John',
          'fkg' => 'John',
          't g t' => ' John ',
          'tigit' => ' John ',
          'tjgjt' => ', John, ',
          'tkgkt' => 'John',
          // Modifier entries
          'Lg' => 'john', // lowercase
          'Ug' => 'JOHN', // uppercase
          'Fg' => 'John', // first letter to uppercase
          'Gg' => 'John', // first letter of all words to uppercase
          'LF(g)' => 'John', // lowercase, first letter to uppercase
          'LG(g)' => 'John', // lowercase, first letter of all words to uppercase
          'LFg' => 'John', // lowercase, first letter to uppercase
          'LGg' => 'John', // lowercase, first letter of all words to uppercase
          'Tg' => 'John', // Trims whitespace around the next token
          'Sg' => 'John', // check_plain
          // Conditional entries
          '(((g)))' => 'John', // brackets
          '(g))()(' => 'John)(', // brackets - mismatched
          'g+ f' => 'John', // Insert the token if both the surrounding tokens are not empty
          'g= f' => 'John', // Insert the token, if and only if the next token after it is not empty
          'g^ f' => 'John ', // Skip the token, if and only if the next token after it is not empty
          's|c|g|m|f|t' => 'John', // Uses only the first one.
          'g|f' => 'John', // Uses the previous token unless empty, otherwise it uses this token
          // Real world examples
          'L(t= g= m= f= s=,(= c))' => ' john', // Full name with a comma-space before credentials
          'TS(LF(t= g= m= f= s)=,(= c))' => 'john', // Full name with a comma-space before credentials. ucfirst does not work on a whitespace
          'L(t+ g+ m+ f+ s+,(= c))' => 'john', // Full name with a comma-space before credentials
          'TS(LF(t+ g+ m+ f+ s)+,(= c))' => 'John', // Full name with a comma-space before credentials
        ),
      ),
      'full' => array(
        'components' => array(
          'title' => 'MR.',
          'given' => 'JoHn',
          'middle' => 'pEter',
          'family' => 'dOE',
          'generational' => 'sR',
          'credentials' => 'b.Sc, pHd',
        ),
        //MR. JoHn pEter dOE sR b.Sc, pHd
        'tests' => array(
          // Test that only the given name creates a entry
          't' => 'MR.', // Title
          'g' => 'JoHn', // Given name
          'm' => 'pEter', // Middle name(s)
          'f' => 'dOE', // Family name
          'c' => 'b.Sc, pHd', // Credentials
          's' => 'sR', // Generational suffix
          'x' => 'J', // First letter given
          'y' => 'p', // First letter middle
          'z' => 'd', // First letter family
          'e' => 'JoHn', // Either the given or family name. Given name is given preference
          'E' => 'dOE', // Either the given or family name. Family name is given preference
          // Combination test
          'g f' => 'JoHn dOE', // Using a single space
          'gif' => 'JoHn dOE', // Separator 1
          'gjf' => 'JoHn, dOE', // Separator 2
          'gkf' => 'JoHndOE', // Separator 3
          'f g' => 'dOE JoHn',
          'fig' => 'dOE JoHn',
          'fjg' => 'dOE, JoHn',
          'fkg' => 'dOEJoHn',
          't g t' => 'MR. JoHn MR.',
          'tigit' => 'MR. JoHn MR.',
          'tjgjt' => 'MR., JoHn, MR.',
          'tkgkt' => 'MR.JoHnMR.',
          // Modifier entries
          'L(t g m f s c)' => 'mr. john peter doe sr b.sc, phd', // lowercase
          'U(t g m f s c)' => 'MR. JOHN PETER DOE SR B.SC, PHD', // uppercase
          'F(t g m f s c)' => 'MR. JoHn pEter dOE sR b.Sc, pHd', // first letter to uppercase
          'G(t g m f s c)' => 'MR. JoHn PEter DOE SR B.Sc, PHd', // first letter of all words to uppercase
          'LF(t g m f s c)' => 'Mr. john peter doe sr b.sc, phd', // first letter to uppercase
          'LG(t g m f s c)' => 'Mr. John Peter Doe Sr B.sc, Phd', // first letter of all words to uppercase
          'T(t g m f s c)' => 'MR. JoHn pEter dOE sR b.Sc, pHd', // Trims whitespace around the next token
          'S(t g m f s c)' => 'MR. JoHn pEter dOE sR b.Sc, pHd', // check_plain
          // Conditional entries
          '(((t g m f s c)))' => 'MR. JoHn pEter dOE sR b.Sc, pHd', // brackets
          '(t g m f s c))()(' => 'MR. JoHn pEter dOE sR b.Sc, pHd)(', // brackets - mismatched
          't= g= m= f= s= c' => 'MR. JoHn pEter dOE sR b.Sc, pHd', // Insert the token, if and only if the next token after it is not empty
          'g|m|f' => 'JoHn', // Uses the previous token unless empty, otherwise it uses this token
          'm|f|g' => 'pEter', // Uses the previous token unless empty, otherwise it uses this token
          's|c|g|m|f|t' => 'sR', // Uses only the first one.
          // Real world examples
          'L(t= g= m= f= s=,(= c))' => 'mr. john peter doe sr, b.sc, phd', // Full name with a comma-space before credentials
          'TS(LG(t= g= m= f= s)=,LG(= c))' => 'Mr. John Peter Doe Sr, B.sc, Phd', // Full name with a comma-space before credentials
        ),
      ),
    );
  }

  /**
   * Convert names() to PHPUnit compatible format.
   *
   * @return array
   */
  public function patternDataProvider() {
    $data = array();

    foreach ($this->names() as $dataSet) {
      foreach ($dataSet['tests'] as $pattern => $expected) {
       $data[] = array(
         $dataSet['components'],
         $pattern,
         $expected,
       );
      }
    }

    return $data;
  }

  /**
   * Test NameFormatParser::parse
   *
   * @dataProvider patternDataProvider
   */
  public function testParser($components, $pattern, $expected) {
    $settings = array(
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    );
    $formatted = NameFormatParser::parse($components, $pattern, $settings);
    $this->assertEquals($expected, $formatted);
  }
}
