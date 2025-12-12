<?php

add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_visa_fields',
    'title' => 'Настройки визы',
    'fields' => [

      [
        'key' => 'field_visa_country',
        'label' => 'Страна',
        'name' => 'visa_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'required' => 1,
        'return_format' => 'id',
        'ui' => 1,
        'wrapper' => ['width' => '50'],
      ],

    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'visa',
        ],
      ],
    ],
  ]);
});

add_filter('acf/fields/post_object/query/name=visa_country', function ($args, $field, $post_id) {
  $args['post_parent'] = 0;

  return $args;
}, 10, 3);
