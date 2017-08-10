<?php

namespace Drupal\eu_cookie_compliance\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for testing EUCC functionality.
 */
abstract class EuCookieComplianceTestBase extends WebTestBase {

  /**
   * Assert function to determine if EU Cookie Compliance rendered to the page
   * have a corresponding page element.
   *
   * @code
   * // Basic example.
   * $this->assertEuCookieCompliance();
   * @endcode
   */
  public function assertEuCookieCompliance() {

    $rendered_eucc = $this->xpath('//div[@id = "sliding-popup"]//div[starts-with(@class, "popup-content")]');

    $this->assertTrue($rendered_eucc, 'EU Cookie Compliance render.');

  }

}
