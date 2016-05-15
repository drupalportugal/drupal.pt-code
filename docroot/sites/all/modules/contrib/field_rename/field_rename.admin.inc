<?php

function field_rename_admin() {
    $form = array();
    $form['current_field'] = array(
        '#type' => 'textfield',
        '#title' => t('Current field name'),
        '#required' => TRUE,
    );
    $form['new_field'] = array(
        '#type' => 'textfield',
        '#title' => t('New field name'),
        '#required' => TRUE,
    );

    if (module_exists('views')) {
        $form['rename_views'] = array(
            '#type' => 'checkbox',
            '#title' => t('Rename views'),
            '#description' => t('Rename this field in all views on this system.'),
            '#default_value' => FALSE,
        );
    }

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
    );

    return $form;
}

function field_rename_admin_validate(&$form, &$form_state) {
    $current_field = field_read_field($form_state['values']['current_field']);
    if (empty($current_field)) {
        form_set_error('current_field', 'Field does not exist.');
    }
    else if ($form_state['values']['current_field'] == $form_state['values']['new_field']) {
        form_set_error('new_field', 'New field name should be different.');
    }
}

function field_rename_admin_submit(&$form, &$form_state) {
    $fields = array(
        $form_state['values']['current_field'] => $form_state['values']['new_field'],
    );

    try {
        field_rename_rename_fields($fields);
        if (!empty($form_state['values']['rename_views'])) {
            field_rename_rename_views($fields);
        }
    } catch (Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
        return;
    }

    drupal_flush_all_caches();
    drupal_set_message('Flushed all Drupal caches.');
}
