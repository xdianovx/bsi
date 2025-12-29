<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  if (!function_exists('acf_add_options_page')) {
    return;
  }

  acf_add_options_page([
    'page_title' => 'Настройки валют',
    'menu_title' => 'Настройки валют',
    'menu_slug' => 'currency-settings',
    'capability' => 'manage_options',
    'icon_url' => 'dashicons-money-alt',
    'position' => 30,
  ]);

  acf_add_local_field_group([
    'key' => 'group_currency_settings',
    'title' => 'Настройки курсов валют',
    'fields' => [
      [
        'key' => 'field_currency_markup',
        'label' => 'Надбавка (%)',
        'name' => 'currency_markup',
        'type' => 'number',
        'instructions' => 'Процент надбавки к курсу ЦБ РФ для всех валют',
        'default_value' => 0,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'placeholder' => '0',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'options_page',
          'operator' => '==',
          'value' => 'currency-settings',
        ],
      ],
    ],
  ]);
});

add_action('acf/save_post', function ($post_id) {
  if ($post_id !== 'options') {
    return;
  }

  if (!isset($_POST['acf']) || !is_array($_POST['acf'])) {
    return;
  }

  if (isset($_POST['acf']['field_currency_markup'])) {
    delete_transient('bsi_cbr_rates');
  }
}, 20);

