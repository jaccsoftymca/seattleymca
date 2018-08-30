### Examples
```php
function ygs_master_update_8001() {
  \Drupal::service('config_import.importer')->importConfigs(
    [
      YGS_CONFDIR . 'core.entity_form_display.contact_message.become_a_supplier.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.cc_wellness_employee.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.cc_wellness_employer.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.contact_an_aquatics_manager.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.contact_personal_training.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.journey_to_freedom.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.membership_form.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.personal_trainers_individual.default.yml',
      YGS_CONFDIR . 'core.entity_form_display.contact_message.wait_list.default.yml',
    ]
  );
}

function ygs_master_update_8002() {
  $path = drupal_get_path('module', 'ymca_errors') . '/config/install/';
  \Drupal::service('config_import.importer')->importConfigs(
    [
      $path . 'ymca_errors.errors.yml',
    ]
  );
}
```
