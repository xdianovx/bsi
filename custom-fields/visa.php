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

      [
        'key' => 'field_visa_files',
        'label' => 'Файлы',
        'name' => 'visa_files',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить файл',
        'sub_fields' => [
          [
            'key' => 'field_visa_file',
            'label' => 'Файл',
            'name' => 'file',
            'type' => 'file',
            'return_format' => 'array',
            'library' => 'all',
            'required' => 1,
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_visa_file_name',
            'label' => 'Название файла',
            'name' => 'name',
            'type' => 'text',
            'instructions' => 'Если не указано, будет использовано имя файла',
            'wrapper' => ['width' => '50'],
          ],
        ],
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
