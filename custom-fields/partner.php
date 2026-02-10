<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_partner_fields',
    'title' => 'Партнер',
    'fields' => [
      [
        'key' => 'field_partner_show_on_mice',
        'label' => 'Показать на странице MICE',
        'name' => 'show_on_mice_page',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'partner',
        ],
      ],
    ],
  ]);
});
