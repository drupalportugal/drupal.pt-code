<?php

namespace Drupal\commerce_license\Plugin\Commerce\LicenseType;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_license\Entity\LicenseInterface;

/**
 * Defines the interface for license types.
 *
 * License type plugins provide the functionality for what the license grants
 * access to.
 *
 * They participate in different ways at various points of the license's
 * lifecycle:
 * - When a product is created that sells a license, the license type
 *   plugin's configuration represents the details of the license to be sold.
 *   This is stored as a configured plugin on a field on the product variation
 *   (this field is provided by our commerce_license entity trait on product
 *   variation types).
 * - When a product is purchased, the configured plugin on the product
 *   variation is used to set field values on the newly created license entity.
 *   In effect, the configured plugin is used as a template for the new license.
 *   These fields are provided by the license type plugin behaving as a bundle
 *   plugin. Accordingly, the plugin configuration defined in
 *   defaultConfiguration() and the bundle fields defined in
 *   buildFieldDefinitions() should usually match. The license has a reference
 *   to the plugin type in its bundle field, but when a license obtains its
 *   plugin to work with, it is no longer configured as this would duplicate the
 *   fields (which we use rather than the plugin configuration so they can be
 *   queried for).
 * - When a license is granted or revoked, the plugin's grantLicense()
 *   and revokeLicense() methods are responsible for making changes to the
 *   system, such as adding or removing a role on the license's owner.
 */
interface LicenseTypeInterface extends BundlePluginInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Gets the license type label.
   *
   * @return string
   *   The license type label.
   */
  public function getLabel();

  /**
   * Build a label for the given license type.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *
   * @return string
   *   The label.
   */
  public function buildLabel(LicenseInterface $license);

  /**
   * Gets the workflow ID this this license type should use.
   *
   * @return string
   *   The ID of the workflow used for this license type.
   */
  public function getWorkflowId();

  /**
   * Gets the name of the order state in which a license should activate.
   *
   * @see commerce_order.workflows.yml
   * @see \Drupal\commerce_license\EventSubscriber\LicenseOrderSyncSubscriber
   *
   * @return string
   *   The ID of the order workflow in which licenses of this type should
   *   activate.
   */
  public function getActivationOrderState();

  /**
   * Copy configuration values to a license entity.
   *
   * This does not save the license; it is the caller's responsibility to do so.
   *
   * This should only be called on a plugin which has configuration. It should
   * not be called on a plugin obtained from LicenseInterface::getTypePlugin(),
   * as that has no configuration.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   */
  public function setConfigurationValuesOnLicense(LicenseInterface $license);

  /**
   * Reacts to the license being activated.
   *
   * The license's privileges should be granted to its user.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   */
  public function grantLicense(LicenseInterface $license);

  /**
   * Reacts to the license being revoked.
   *
   * The license's privileges should be removed from its user.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   */
  public function revokeLicense(LicenseInterface $license);

}
