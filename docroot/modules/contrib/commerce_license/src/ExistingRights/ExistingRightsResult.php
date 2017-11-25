<?php

namespace Drupal\commerce_license\ExistingRights;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Represents the result of a check for a user's existing rights.
 *
 * This is used when checking whether a user already has the rights a license
 * would grant.
 */
class ExistingRightsResult {

  /**
   * Boolean stating whether the user has existing rights.
   *
   * @var bool
   */
  protected $status;

  /**
   * Message to show the user stating that they already have these rights.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $messageOwner;

  /**
   * Message to show to another stating that the user already has these rights.
   *
   * This is for cases such as an admin user updating a cart, or creating an
   * order for another user.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $messageOther;

  /**
   * Constructs a new ExistingRightsResult.
   *
   * @param bool $status
   *   Whether the user has existing rights.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_owner
   *   (optional) A translated message intended to be shown to the user, to
   *   explain that they already have these rights. This should not make
   *   reference to cart or product.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_other
   *   (optional) A translated message intended to be shown to a different user,
   *   such as an admin, to explain that the user being checked already has
   *   these rights. This should not make reference to cart or product.
   */
  public function __construct($status, TranslatableMarkup $message_owner = NULL, TranslatableMarkup $message_other = NULL) {
    $this->status = $status;
    $this->messageOwner = $message_owner;
    $this->messageOther = $message_other;
  }

  /**
   * Creates an ExistingRightsResult from a condition.
   *
   * @param bool $condition
   *   The condition to evaluate.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_owner
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_other
   *
   * @return \Drupal\commerce_license\ExistingRights\ExistingRightsResult
   *   The result object.
   */
  public static function rightsExistIf($condition, TranslatableMarkup $message_owner, TranslatableMarkup $message_other) {
    if ($condition) {
      return static::rightsExist(
        $message_owner,
        $message_other
      );
    }
    else {
      return static::rightsDoNotExist();
    }
  }

  /**
   * Creates an ExistingRightsResult stating that rights exist.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_owner
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_other
   *
   * @return \Drupal\commerce_license\ExistingRights\ExistingRightsResult
   *   The result object.
   */
  public static function rightsExist(TranslatableMarkup $message_owner, TranslatableMarkup $message_other) {
    return new static(
      TRUE,
      $message_owner,
      $message_other
    );
  }

  /**
   * Creates an ExistingRightsResult stating that rights do not exist.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_owner
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message_other
   *
   * @return \Drupal\commerce_license\ExistingRights\ExistingRightsResult
   *   The result object.
   */
  public static function rightsDoNotExist() {
    return new static(FALSE);
  }

  /**
   * Gets the status of the result.
   *
   * @return bool
   *   Boolean indicating whether the checked user has existing rights.
   */
  public function hasExistingRights() {
    return $this->status;
  }

  /**
   * Gets the message intended for the user that was checked.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated message.
   */
  public function getOwnerUserMessage() {
    return $this->messageOwner;
  }

  /**
   * Gets the message intended for a user other than the one that was checked.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated message.
   */
  public function getOtherUserMessage() {
    return $this->messageOther;
  }

}
