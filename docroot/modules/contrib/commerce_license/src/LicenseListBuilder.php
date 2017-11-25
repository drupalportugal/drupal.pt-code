<?php

namespace Drupal\commerce_license;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of License entities.
 *
 * @ingroup commerce_license
 */
class LicenseListBuilder extends EntityListBuilder {

  /**
   * The license types definitions.
   *
   * @var array
   */
  protected $licenseTypes;

  /**
   * Constructs a new LicenseListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\commerce_license\LicenseTypeManager $license_type_manager
   *   The license type manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, LicenseTypeManager $license_type_manager) {
    parent::__construct($entity_type, $storage);
    $this->licenseTypes = $license_type_manager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.commerce_license_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('License ID');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_license\Entity\License */
    $row['id'] = $entity->id();
    $row['type'] = $this->licenseTypes[$entity->bundle()]['label'];
    return $row + parent::buildRow($entity);
  }

}
