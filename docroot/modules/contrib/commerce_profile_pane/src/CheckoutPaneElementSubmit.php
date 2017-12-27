<?php

namespace Drupal\commerce_profile_pane;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\ElementSubmit;

/**
 * Provides #ief_element_submit, the submit version of #element_validate.
 *
 * #ief_element_submit callbacks are invoked by a #submit callback added
 * to the form's main submit button.
 *
 * **NOT CURRENTLY IN USE**
 *
 * @see \Drupal\commerce_profile_pane\Plugin\Commerce\CheckoutPane\Profile::buildPaneForm()
 */
class CheckoutPaneElementSubmit extends ElementSubmit {

  /**
   * {@inheritdoc}
   */
  public static function attach(&$form, FormStateInterface $form_state) {
    // We need this override as the pane form doens't have any of the action
    // buttons that the parent attach() method looks for.
    // The parent method will already have been called by
    // \Drupal\inline_entity_form\Element\InlineEntityForm::processEntityForm()
    // and so the guard property will be set. So ignore it, as we know the
    // parent class's attach() will have done nothing.
    static::addCallback($form['actions']['next'], $form);
  }

}
