<?php

namespace Drupal\commerce_license\EventSubscriber;

use Drupal\commerce\Event\ReferenceablePluginTypesEvent;
use Drupal\commerce\Event\CommerceEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReferenceablePluginTypesSubscriber.
 *
 * @package Drupal\commerce_license
 */
class ReferenceablePluginTypesSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    return [
      CommerceEvents::REFERENCEABLE_PLUGIN_TYPES => 'onPluginTypes',
    ];
  }

  /**
   * Registers our plugin types as referenceable.
   *
   * @param \Drupal\commerce\Event\ReferenceablePluginTypesEvent $event
   *   The event.
   */
  public function onPluginTypes(ReferenceablePluginTypesEvent $event) {
    $plugin_types = $event->getPluginTypes();
    $plugin_types['commerce_license_type'] = $this->t('License type');
    $event->setPluginTypes($plugin_types);
  }

}
