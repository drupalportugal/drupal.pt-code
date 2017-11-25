<?php

namespace Drupal\commerce_license\Form;

use Drupal\commerce_license\LicenseTypeManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for License edit forms.
 *
 * @ingroup commerce_license
 */
class LicenseCreateForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The license type plugin manager.
   *
   * @var \Drupal\commerce_license\LicenseTypeManager
   */
  protected $pluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\commerce_license\LicenseTypeManager $plugin_manager
   *   The plugin manager for the license type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(LicenseTypeManager $plugin_manager, ModuleHandlerInterface $module_handler, $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    $this->pluginManager = $plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_license_type'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(FormStateInterface $form_state) {
    // Flag that this form has been initialized.
    $form_state->set('entity_form_initialized', TRUE);
    // Prepare the entity to be presented in the entity form.
    $this->prepareEntity();
    // Invoke the prepare form hooks.
    $this->prepareInvokeAll('entity_prepare_form', $form_state);
    $this->prepareInvokeAll($this->entity->getEntityTypeId() . '_prepare_form', $form_state);
  }


  /**
   * Form element #after_build callback: Updates the entity with submitted data.
   *
   * Updates the internal $this->entity object with submitted values when the
   * form is being rebuilt (e.g. submitted via AJAX), so that subsequent
   * processing (e.g. AJAX callbacks) can rely on it.
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Rebuild the entity if #after_build is being called as part of a form
    // rebuild, i.e. if we are processing input.
    if ($form_state->isProcessingInput()) {
      $this->entity = $this->buildEntity($element, $form_state);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    $base_form_id = 'commerce_license_form';
    return $base_form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_license_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if (!$step) {
      $step = 'license_type';
      // Skip the license type selection if there's only 1 type.
      $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
      if (count($plugins) === 1) {
        /** @var \Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeInterface $license_type */
        $license_type = reset($plugins);
        error_log($license_type);
        $form_state->set('license_type', $license_type);
        $step = 'license';
      }
      $form_state->set('step', $step);
    }


    if ($step == 'license_type') {
      $form = $this->buildLicenseTypeForm($form, $form_state);
    }
    elseif ($step == 'license') {
      // DEPRECATED???
      // Create our license now. We can't do it in RouteMatch because reasons.
      $values = [];
      // If the entity has bundles, fetch it from the route match.
      $values['type'] = $form_state->get('license_type');

      $entity = $this->entityTypeManager->getStorage('commerce_license')
        ->create($values);
      $this->setEntity($entity);

      $form = $this->buildLicenseForm($form, $form_state);
    }

    // Add the submit button.
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];
    $actions['#type'] = 'actions';
    if (!empty($actions)) {
      $form['actions'] = $actions;
    }

    return $form;
  }

  public function buildLicenseTypeForm(array $form, FormStateInterface $form_state) {
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    $plugin_ids = array_keys($plugins);
    $first_plugin = reset($plugin_ids);

    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('license_type', $first_plugin);

    $form['license_type'] = [
      '#type' => 'select',
      '#title' => $this->t('License type'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
    ];

    return $form;
  }

  public function buildLicenseForm(array $form, FormStateInterface $form_state) {
    // During the initial form build, add this form object to the form state and
    // allow for initial preparation before form building and processing.
    if (!$form_state->has('entity_form_initialized')) {
      $this->init($form_state);
    }

    // Ensure that edit forms have the correct cacheability metadata so they can
    // be cached.
    if (!$this->entity->isNew()) {
      \Drupal::service('renderer')
        ->addCacheableDependency($form, $this->entity);
    }

    // Callbacks not added by using the entity methods.
    $form['#process'][] = '::processForm';

    $this->getFormDisplay($form_state)->buildForm($this->entity, $form, $form_state);
    // Allow modules to act before and after form language is updated.
    $form['#entity_builders']['update_form_langcode'] = '::updateFormLangcode';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    // No need to validate the plugin/type selection step more than the select
    // list element will do for us.
    if ($step == 'license') {
      // @TODO: Entity form validation here.
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step == 'license_type') {
      // Set the license type and rebuild the form.
      $form_state->set('license_type', $form_state->getValue('license_type'));
      $form_state->set('step', 'license');
      $form_state->setRebuild(TRUE);
    }
    elseif ($step == 'license') {
      // @TODO: Entity form submission here.
      parent::submitForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = clone $this->entity;
    $this->copyFormValuesToEntity($entity, $form, $form_state);

    // Invoke all specified builders for copying form values to entity
    // properties.
    if (isset($form['#entity_builders'])) {
      foreach ($form['#entity_builders'] as $function) {
        call_user_func_array($form_state->prepareCallback($function), [
          $entity->getEntityTypeId(),
          $entity,
          &$form,
          &$form_state
        ]);
      }
    }

    // Mark the entity as requiring validation.
    $entity->setValidationRequired(!$form_state->getTemporaryValue('entity_validated'));

    return $entity;
  }

  /**
   * Copies top-level form values to entity properties
   *
   * This should not change existing entity properties that are not being edited
   * by this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $values = array_diff_key($values, $this->entity->getPluginCollections());
    }

    // @todo: This relies on a method that only exists for config and content
    //   entities, in a different way. Consider moving this logic to a config
    //   entity specific implementation.
    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Prepares the entity object before the form is built first.
   */
  protected function prepareEntity() {
  }

  /**
   * Invokes the specified prepare hook variant.
   *
   * @param string $hook
   *   The hook variant name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function prepareInvokeAll($hook, FormStateInterface $form_state) {
    $implementations = $this->moduleHandler->getImplementations($hook);
    foreach ($implementations as $module) {
      $function = $module . '_' . $hook;
      if (function_exists($function)) {
        // Ensure we pass an updated translation object and form display at
        // each invocation, since they depend on form state which is alterable.
        $args = [$this->entity, $this->operation, &$form_state];
        call_user_func_array($function, $args);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(FormStateInterface $form_state) {
    return $form_state->get('form_display');
  }
}
