<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  if (!function_exists('acf_add_options_sub_page')) {
    return;
  }

  acf_add_options_sub_page([
    'page_title'  => 'Настройки MICE',
    'menu_title'  => 'Настройки MICE',
    'menu_slug'   => 'mice-settings',
    'parent_slug' => 'mice-pages',
    'capability'  => 'manage_options',
  ]);

  acf_add_local_field_group([
    'key'    => 'group_mice_settings',
    'title'  => 'MICE — Настройки CTA',
    'fields' => [
      [
        'key'   => 'field_mice_cta_title',
        'label' => 'Заголовок CTA',
        'name'  => 'mice_cta_title',
        'type'  => 'text',
      ],
      [
        'key'   => 'field_mice_cta_description',
        'label' => 'Описание CTA',
        'name'  => 'mice_cta_description',
        'type'  => 'textarea',
        'rows'  => 3,
      ],
    ],
    'location' => [
      [
        [
          'param'    => 'options_page',
          'operator' => '==',
          'value'    => 'mice-settings',
        ],
      ],
    ],
  ]);
});
