<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_resort_term_content',
    'title' => 'Курорт — контент',

    // ✅ наверх
    'position' => 'acf_after_title',
    'menu_order' => 0,

    'fields' => [
      [
        'key' => 'field_resort_excerpt',
        'label' => 'Краткое описание',
        'name' => 'resort_excerpt',
        'type' => 'textarea',
        'rows' => 3,
        'new_lines' => 'br',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_resort_gallery',
        'label' => 'Галерея курорта',
        'name' => 'resort_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 30,
        'wrapper' => ['width' => '100'],
      ],

    ],

    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'resort',
        ],
      ],
    ],
  ]);
});