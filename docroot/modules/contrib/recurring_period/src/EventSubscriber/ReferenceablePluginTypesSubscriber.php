<?php

namespace Drupal\recurring_period\EventSubscriber;

use Drupal\commerce\Event\ReferenceablePluginTypesEvent;
use Drupal\commerce\Event\CommerceEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Registers our plugin type as refereceable by Commerce's plugin field.
 *
 * This does require Commerce module to be present, and will just do nothing
 * if it is not. (At least that's the plan! ;)
 */
class ReferenceablePluginTypesSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Too early to check for commerce module with the module handler service.
    // Can't check for whether the CommerceEvents::REFERENCEABLE_PLUGIN_TYPES
    // constant is defined.
    // Check for the class that provides the constant instead.
    if (class_exists(CommerceEvents::class)) {
      return [
        CommerceEvents::REFERENCEABLE_PLUGIN_TYPES => 'onPluginTypes',
      ];
    }
    else {
      return [];
    }
  }

  /**
   * Registers our plugin types as referenceable.
   *
   * @param \Drupal\commerce\Event\ReferenceablePluginTypesEvent $event
   *   The event.
   */
  public function onPluginTypes(ReferenceablePluginTypesEvent $event) {
    $plugin_types = $event->getPluginTypes();
    $plugin_types['recurring_period'] = $this->t('Recurring period');
    $event->setPluginTypes($plugin_types);
  }

}
