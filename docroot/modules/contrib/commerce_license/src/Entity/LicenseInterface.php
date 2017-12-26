<?php

namespace Drupal\commerce_license\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface;

/**
 * Provides an interface for License entities.
 *
 * @ingroup commerce_license
 */
interface LicenseInterface extends EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the License creation timestamp.
   *
   * @return int
   *   Creation timestamp of the License.
   */
  public function getCreatedTime();

  /**
   * Sets the License creation timestamp.
   *
   * @param int $timestamp
   *   The License creation timestamp.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   The called License entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the License expiration timestamp.
   *
   * @return int
   *   Expiration timestamp of the License.
   */
  public function getExpiresTime();

  /**
   * Sets the License expiration timestamp.
   *
   * @param int $timestamp
   *   The License expiration timestamp.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   The called License entity.
   */
  public function setExpiresTime($timestamp);

  /**
   * Gets the granted timestamp.
   *
   * @return int
   *   The granted timestamp.
   */
  public function getGrantedTime();

  /**
   * Sets the granted timestamp.
   *
   * @param int $timestamp
   *   The granted timestamp.
   *
   * @return $this
   */
  public function setGrantedTime($timestamp);

  /**
   * Gets the renewal timestamp.
   *
   * @return int
   *   The renewal timestamp.
   */
  public function getRenewedTime();

  /**
   * Sets the renewal timestamp.
   *
   * @param int $timestamp
   *   The renewal timestamp.
   *
   * @return $this
   */
  public function setRenewedTime($timestamp);

  /**
   * Get an unconfigured instance of the associated license type plugin.
   *
   * @return \Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface
   */
  public function getTypePlugin();

  /**
   * Gets the type of expiration this license uses.
   *
   * @return string
   *   The ID of the recurring_period plugin.
   */
  public function getExpirationPluginType();

  /**
   * Gets the expiration plugin for this license.
   *
   * @return \Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface
   *   The recurring_period plugin configured for this license.
   */
  public function getExpirationPlugin();

  /**
   * Gets the license state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The shipment state.
   */
  public function getState();

  /**
   * Set values on the license from a configured license type plugin.
   *
   * This should be called when a license is created for an order, using the
   * configured license type plugin on the product variation that is being
   * purchased.
   *
   * @param \Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface
   *   The configured license type plugin.
   */
  public function setValuesFromPlugin(LicenseTypeInterface $license_plugin);

  /**
   * Implements the workflow_callback for the state field.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   *
   * @return string
   *   The workflow ID.
   *
   * @see \Drupal\state_machine\Plugin\Field\FieldType\StateItem
   */
  public static function getWorkflowId(LicenseInterface $license);

}
