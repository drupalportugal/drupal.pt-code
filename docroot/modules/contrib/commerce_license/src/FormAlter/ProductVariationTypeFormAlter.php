<?php

namespace Drupal\commerce_license\FormAlter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Alters the product variation type form.
 *
 * - Adds a form element for our third-party setting for available license
 *   types.
 * - Provides validation in the product variation type admin UI to ensure that
 *   everything joins up when a license trait is used.
 *
 * @see commerce_license_field_widget_form_alter()
 */
class ProductVariationTypeFormAlter {

  /**
   * The product variation type entity being edited in the form being altered.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $variation_type;

  /**
   * Construct a ProductVariationTypeFormAlter object.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationType $variation_type
   *   The product variation type entity.
   */
  public function __construct(ProductVariationType $variation_type) {
    $this->variation_type = $variation_type;

    // TODO: inject services.
  }

  /**
   * Alters the form.
   *
   * Helper for hook_form_FORM_ID_alter(); same parameters.
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    // Create our form elements which we insert into the form at the end.
    $our_form = [];

    $our_form['license'] = [
      '#type' => 'details',
      '#title' => t('License settings'),
      '#open' => TRUE,
      // Only show this if the license trait is set on the product variation
      // type.
      '#states' => [
        'visible' => [
          ':input[name="traits[commerce_license]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add checkboxes to the product variation type form to select the license
    // types that product variations of this type may use.
    $options = array_column(\Drupal::service('plugin.manager.commerce_license_type')->getDefinitions(), 'label', 'id');
    $our_form['license']['license_types'] = [
      '#type' => 'checkboxes',
      '#title' => t("Available license types"),
      '#description' => t("Limit the license types that can be used on product variations of this type. All types will be allowed if none are selected."),
      '#options' => $options,
      '#default_value' => $this->variation_type->getThirdPartySetting('commerce_license', 'license_types') ?: [],
    ];
    // TODO: consider whether to lock this once the product variation type is
    // created or has product variation entities, or at least lock the enabled
    // license types.

    $our_form['license']['activate_on_place'] = [
      '#type' => 'checkbox',
      '#title' => t("Activate license when order is placed"),
      '#description' => t(
        "Activates the license as soon as the customer completes checkout, rather than waiting for payment to be taken. " .
        "If payment subsequently fails, canceling the order will cancel the license. " .
        "This only has an effect with order types that use validation or fulfilment states and payment gateways that are asynchronous."
      ),
      '#default_value' => $this->variation_type->getThirdPartySetting('commerce_license', 'activate_on_place', FALSE),
    ];

    // Insert our form elements into the form after the 'traits' element.
    // The form elements don't have their weight set, so we can't use that.
    $traits_element_form_array_index = array_search('traits', array_keys($form));

    $form = array_merge(
      array_slice($form, 0, $traits_element_form_array_index + 1),
      $our_form,
      array_slice($form, $traits_element_form_array_index + 1)
    );

    // Add our validate handler, which ensures that all the various config
    // entities join up properly.
    $form['#validate'][] = [$this, 'formValidate'];

    // Add our submit handler, which saves our third-party settings.
    $form['actions']['submit']['#submit'][] = [$this, 'formSubmit'];
  }

  /**
   * Form validation callback.
   *
   * Ensures that everything joins up when a license trait is used.
   */
  public function formValidate($form, FormStateInterface $form_state) {
    $traits = $form_state->getValue('traits');
    $original_traits = $form_state->getValue('original_traits');

    // Only validate if our trait is in use. Need to check both new traits and
    // existing traits values, as the 'traits' form value won't have a value for
    // a checkbox that's disabled because an existing trait can't be removed.
    if (empty($traits['commerce_license']) && !in_array('commerce_license', $original_traits)) {
      return;
    }

    // The order item type must have the license trait.
    $order_item_type_id = $form_state->getValue('orderItemType');
    $order_item_type = \Drupal::entityTypeManager()->getStorage('commerce_order_item_type')->load($order_item_type_id);

    if (!in_array('commerce_license_order_item_type', $order_item_type->getTraits())) {
      $form_state->setError($form['orderItemType'], t(
        'The License trait requires an order item type with the order item license trait. ' .
        'This product variation is set to use the @order-item-type-label order item type. You must either change this, or <a href="@url-edit-order-item-type">edit the order item type</a> to add the license trait.',
        [
          '@order-item-type-label' => $order_item_type->label(),
          '@url-edit-order-item-type' => $order_item_type->toUrl('edit-form')->toString(),
        ]
      ));
    }

    // The checkout flow may not allow anonymous checkout.
    $order_type_id = $order_item_type->getOrderTypeId();
    $order_type = \Drupal::entityTypeManager()->getStorage('commerce_order_type')->load($order_type_id);
    $checkout_flow_id = $order_type->getThirdPartySetting('commerce_checkout', 'checkout_flow');
    if ($checkout_flow_id) {
      $checkout_flow = \Drupal::entityTypeManager()->getStorage('commerce_checkout_flow')->load($checkout_flow_id);
      $login_pane_configuration = $checkout_flow->get('configuration')['panes']['login'];
      if ($login_pane_configuration['step'] != '_disabled') {
        if ($login_pane_configuration['allow_guest_checkout']) {
          $form_state->setError($form['orderItemType'], t(
            "The License trait requires a checkout flow that does not allow guest checkout. " .
            'This product variation is set to use the @order-item-type-label order item type, ' .
            'which is set to use the @order-type-label order type, ' .
            'which is set to use the @flow-label checkout flow. ' .
            'You must either change this, or <a href="@url-checkout-flow">edit the checkout flow</a>.',
            [
              '@order-item-type-label' => $order_item_type->label(),
              '@order-type-label' => $order_type->label(),
              '@flow-label' => $checkout_flow->label(),
              '@url-checkout-flow' => $checkout_flow->toUrl('edit-form')->toString(),
            ]
          ));
        }
      }
    }
  }

  /**
   * Form submit handler.
   *
   * Saves our third-party settings into the product variation type.
   */
  public function formSubmit($form, FormStateInterface $form_state) {
    $variation_type = $form_state->getFormObject()->getEntity();

    $value = $form_state->getValue('license_types');
    $license_types = array_filter($value);
    $variation_type->setThirdPartySetting('commerce_license', 'license_types', $license_types);

    $activate_on_place = $form_state->getValue('activate_on_place');
    $variation_type->setThirdPartySetting('commerce_license', 'activate_on_place', $activate_on_place);

    // This is saving it a second time... but Commerce does the same in its form
    // alterations.
    $variation_type->save();
  }

}
