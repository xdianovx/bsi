<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_gde_kupit_fields',
    'title' => 'Где купить — Турагентства',
    'fields' => [
      [
        'key' => 'field_travel_agencies',
        'label' => 'Турагентства',
        'name' => 'travel_agencies',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить турагентство',
        'sub_fields' => [
          [
            'key' => 'field_agency_city',
            'label' => 'Город',
            'name' => 'city',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_agency_name',
            'label' => 'Турагентство',
            'name' => 'agency_name',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_agency_phone',
            'label' => 'Телефон',
            'name' => 'phone',
            'type' => 'text',
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_agency_email',
            'label' => 'Электронная почта',
            'name' => 'email',
            'type' => 'email',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_agency_address',
            'label' => 'Адрес',
            'name' => 'address',
            'type' => 'textarea',
            'rows' => 2,
            'wrapper' => ['width' => '50'],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'page_template',
          'operator' => '==',
          'value' => 'page-gde-kupit.php',
        ],
      ],
    ],
  ]);
});
