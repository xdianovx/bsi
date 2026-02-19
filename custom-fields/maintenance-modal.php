<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  if (!function_exists('acf_add_options_page')) {
    return;
  }

  // Создаем отдельную страницу настроек для модального окна
  acf_add_options_page([
    'page_title' => 'Предупреждение на сайте',
    'menu_title' => 'Предупреждение на сайте',
    'menu_slug' => 'maintenance-modal-settings',
    'capability' => 'manage_options',
    'icon_url' => 'dashicons-warning',
    'position' => 31,
  ]);

  acf_add_local_field_group([
    'key' => 'group_maintenance_modal',
    'title' => 'Предупреждение на сайте',
    'fields' => [
      [
        'key' => 'field_maintenance_modal_enabled',
        'label' => 'Включить модальное окно предупреждения',
        'name' => 'maintenance_modal_enabled',
        'type' => 'true_false',
        'instructions' => 'Включить или выключить показ модального окна предупреждения для пользователей',
        'ui' => 1,
        'default_value' => 0,
      ],
      [
        'key' => 'field_maintenance_modal_message',
        'label' => 'Текст сообщения',
        'name' => 'maintenance_modal_message',
        'type' => 'text',
        'instructions' => 'Текст сообщения, которое будет отображаться в модальном окне',
        'required' => 0,
        'conditional_logic' => [
          [
            [
              'field' => 'field_maintenance_modal_enabled',
              'operator' => '==',
              'value' => '1',
            ],
          ],
        ],
        'default_value' => '',
        'placeholder' => 'Например: На сайте ведутся технические работы',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'options_page',
          'operator' => '==',
          'value' => 'maintenance-modal-settings',
        ],
      ],
    ],
  ]);
});
