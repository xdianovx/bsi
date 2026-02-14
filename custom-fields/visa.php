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

      [
        'key' => 'field_visa_info_group',
        'label' => 'Информация о визе',
        'name' => '',
        'type' => 'group',
        'instructions' => '',
        'layout' => 'block',
      ],

      [
        'key' => 'field_visa_processing_time',
        'label' => 'Срок оформления',
        'name' => 'visa_processing_time',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],

      [
        'key' => 'field_visa_costs',
        'label' => 'Стоимость виз',
        'name' => 'visa_costs',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить визу',
        'sub_fields' => [
          [
            'key' => 'field_visa_cost_title',
            'label' => 'Название визы',
            'name' => 'title',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_visa_cost_price',
            'label' => 'Цена',
            'name' => 'price',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_visa_cost_included',
            'label' => 'В стоимость включено',
            'name' => 'included',
            'type' => 'textarea',
            'rows' => 3,
            'wrapper' => ['width' => '100'],
            'instructions' => 'Если поле пустое — блок не отображается',
          ],
        ],
      ],

      [
        'key' => 'field_visa_embassy_group',
        'label' => 'Контакты посольства',
        'name' => '',
        'type' => 'group',
        'instructions' => '',
        'layout' => 'block',
      ],

      [
        'key' => 'field_visa_embassy_phone',
        'label' => 'Телефон посольства',
        'name' => 'visa_embassy_phone',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],

      [
        'key' => 'field_visa_embassy_address',
        'label' => 'Адрес посольства',
        'name' => 'visa_embassy_address',
        'type' => 'textarea',
        'rows' => 2,
        'wrapper' => ['width' => '50'],
      ],

      [
        'key' => 'field_visa_embassy_website',
        'label' => 'Сайт посольства',
        'name' => 'visa_embassy_website',
        'type' => 'url',
        'wrapper' => ['width' => '100'],
      ],

      [
        'key' => 'field_visa_callout_text',
        'label' => 'Текст коллаута',
        'name' => 'visa_callout_text',
        'type' => 'textarea',
        'rows' => 3,
        'default_value' => 'Все документы по турам выдаются только при наличии: счет-подтверждения на тур, паспорта гражданина РФ.',
        'wrapper' => ['width' => '100'],
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
