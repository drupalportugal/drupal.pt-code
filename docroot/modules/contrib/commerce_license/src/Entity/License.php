<?php

namespace Drupal\commerce_license\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface;

/**
 * Defines the License entity.
 *
 * @ingroup commerce_license
 *
 * @ContentEntityType(
 *   id = "commerce_license",
 *   label = @Translation("License"),
 *   label_collection = @Translation("Licenses"),
 *   label_singular = @Translation("license"),
 *   label_plural = @Translation("licenses"),
 *   label_count = @PluralTranslation(
 *     singular = "@count license",
 *     plural = "@count licenses",
 *   ),
 *   bundle_label = @Translation("License type"),
 *   bundle_plugin_type = "commerce_license_type",
 *   handlers = {
 *     "access" = "\Drupal\entity\UncacheableEntityAccessControlHandler",
 *     "permission_provider" = "\Drupal\commerce_license\LicensePermissionProvider",
 *     "list_builder" = "Drupal\commerce_license\LicenseListBuilder",
 *     "storage" = "Drupal\commerce_license\LicenseStorage",
 *     "form" = {
 *       "default" = "Drupal\commerce_license\Form\LicenseForm",
 *       "checkout" = "Drupal\commerce_license\Form\LicenseCheckoutForm",
 *       "create" = "Drupal\commerce_license\Form\LicenseCreateForm",
 *       "edit" = "Drupal\commerce_license\Form\LicenseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\commerce_license\LicenseViewsData",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_license",
 *   admin_permission = "administer licenses",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "license_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/licenses/{commerce_license}",
 *     "create-form" = "/admin/commerce/licenses/create",
 *     "edit-form" = "/admin/commerce/licenses/{commerce_license}/edit",
 *     "delete-form" = "/admin/commerce/licenses/{commerce_license}/delete",
 *     "collection" = "/admin/commerce/licenses",
 *   },
 * )
 */
class License extends ContentEntityBase implements LicenseInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Get the label for the license from the plugin.
    return $this->getTypePlugin()->buildLabel($this);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If the state is being changed to 'active', set the granted and expiration
    // timestamps.
    // We don't notify the license type plugin here in case the save is
    // cancelled by something else.
    // (Note that $this->original is not set on new entities.)
    if ((isset($this->original) && $this->state->value != $this->original->state->value) || !isset($this->original)) {
      if ($this->state->value == 'active') {
        $granted_time = \Drupal::service('datetime.time')->getRequestTime();

        $this->set('granted', $granted_time);
        $this->setExpiresTime($this->calculateExpirationTime($granted_time));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // If the state was changed, notify our license type plugin.
    // (Note that $this->original is not set on new entities.)
    if ((isset($this->original) && $this->state->value != $this->original->state->value) || !isset($this->original)) {
      if ($this->state->value == 'active') {
        // The state is moved to 'active', or the license was created active:
        // the license activates.
        $this->getTypePlugin()->grantLicense($this);
      }

      if (isset($this->original) && $this->original->state->value == 'active') {
        // The state is moved away from 'active': the license is revoked.
        $this->getTypePlugin()->revokeLicense($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Revoke the license if it is active.
    if ($this->state->value == 'active') {
      $this->getTypePlugin()->revokeLicense($this);
    }

    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    /** @var \Drupal\commerce_license\LicenseTypeManager $license_type_manager */
    $license_type_manager = \Drupal::service('plugin.manager.commerce_license_type');
    return $license_type_manager->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function setValuesFromPlugin(LicenseTypeInterface $license_plugin) {
    $license_plugin->setConfigurationValuesOnLicense($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiresTime() {
    return $this->get('expires')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpiresTime($timestamp) {
    $this->set('expires', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * Calculate the expiration time for this license from a start time.
   *
   * @param int $start
   *   The timestamp to calculate the duration from.
   *
   * @return int
   *   The expiry timestamp, or the value
   *   \Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface::UNLIMITED
   *   if the license does not expire.
   */
  protected function calculateExpirationTime($start) {
    /** @var \Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface $expiration_type_plugin */
    $expiration_type_plugin = $this->get('expiration_type')->first()->getTargetInstance();

    // The recurring period plugin needs DateTimeImmutable objects in order
    // to handle timezones properly. So we convert the timestamp to a datetime
    // using an appropriate timezone for the user, and then convert the
    // expiration back into a UTC timestamp.
    $start_date = (new \DateTimeImmutable('@' . $start))
      ->setTimezone(new \DateTimeZone(commerce_licence_get_user_timezone($this->getOwner())));
    $expiration_date = $expiration_type_plugin->calculateDate($start_date);

    // The returned date is either \DateTimeImmutable or
    // \Drupal\recurring_period\Plugin\RecurringPeriod\RecurringPeriodInterface::UNLIMITED.
    if (is_object($expiration_date)) {
      return $expiration_date->format('U');
    }
    else {
      return $expiration_date;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public static function getWorkflowId(LicenseInterface $license) {
    return $license->getTypePlugin()->getWorkflowId();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user ID of the license owner.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_license\Entity\License::getCurrentUserId')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The license state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_license\Entity\License', 'getWorkflowId']);

    $fields['queues'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Queues'))
      ->setDescription(t('The queues in which this license is currently placed.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['product_variation'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Licensed product variation'))
      ->setDescription(t('The licensed product variation.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_product_variation')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expiration_type'] = BaseFieldDefinition::create('commerce_plugin_item:recurring_period')
      ->setLabel(t('Expiration type'))
      ->setDescription(t("The configuration for calculating the license's expiry."))
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_plugin_select',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the license was created.'));

    $fields['granted'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Granted'))
      ->setDescription(t('The time that the license was granted or activated.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 1,
        'settings' => [
          'date_format' => 'custom',
          'custom_date_format' => 'n/Y',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(0);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the license was last modified.'));

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires'))
      ->setDescription(t('The time that the license will expire, if any.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 1,
        'settings' => [
          'date_format' => 'custom',
          'custom_date_format' => 'n/Y',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
