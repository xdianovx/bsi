<?php


add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_hotel_info',
    'title' => 'Основная информация',
    'fields' => [
      // Основная информация
      [
        'key' => 'field_hotel_country',
        'label' => 'Страна',
        'name' => 'hotel_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'required' => 1,
        'return_format' => 'id',
        'wrapper' => [
          'width' => '25',
        ],
      ],
      [
        'key' => 'field_hotel_city',
        'label' => 'Город',
        'name' => 'hotel_city',
        'type' => 'text',
        'required' => 1,
        'wrapper' => [
          'width' => '25',
        ],
      ],

      [
        'key' => 'field_hotel_rating',
        'label' => 'Рейтинг',
        'name' => 'rating',
        'type' => 'number',
        'min' => 1,
        'max' => 5,
        // 'default_value' => '3',
        'step' => 1,
        'placeholder' => 'от 1 до 5',
        'wrapper' => [
          'width' => '25',
        ],
      ],
      [
        'key' => 'field_is_featured',
        'label' => 'Популярный?',
        'name' => 'is_popular',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'wrapper' => [
          'width' => '25',
        ],
      ],

      // Контактная информация 2
      [
        'key' => 'field_hotel_address',
        'label' => 'Адрес',
        'name' => 'address',
        'type' => 'text',
        'wrapper' => [
          'width' => '25',
        ],
      ],

      [
        'key' => 'field_hotel_phone',
        'label' => 'Телефон',
        'name' => 'phone',
        'type' => 'text',
        'wrapper' => [
          'width' => '25',
        ],
      ],

      [
        'key' => 'field_website',
        'label' => 'Сайт отеля',
        'name' => 'website',
        'type' => 'url',
        'wrapper' => [
          'width' => '25',
        ],
      ],


      [
        'key' => 'field_price',
        'label' => 'Стоимость',
        'name' => 'price',
        'type' => 'text',
        'wrapper' => [
          'width' => '25',
        ],
      ],

      // Галерея изображений
      [
        'key' => 'field_hotel_gallery',
        'label' => 'Галерея отеля',
        'name' => 'gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 20,
      ],



      // Дополнительная информация
      [
        'key' => 'field_check_in_time',
        'label' => 'Время заезда',
        'name' => 'check_in_time',
        'type' => 'text',
        'wrapper' => [
          'width' => '50',
        ],
        'placeholder' => '14:00',
      ],
      [
        'key' => 'field_check_out_time',
        'label' => 'Время выезда',
        'name' => 'check_out_time',
        'type' => 'text',
        'wrapper' => [
          'width' => '50',
        ],
        'placeholder' => '12:00',
      ],
      [
        'key' => 'field_wifi',
        'label' => 'Wi-Fi',
        'name' => 'wifi',
        'type' => 'text',
        'wrapper' => [
          'width' => '50',
        ],
        'placeholder' => 'Бесплатный',
      ],
      [
        'key' => 'field_breakfast',
        'label' => 'Завтрак',
        'name' => 'breakfast',
        'type' => 'text',
        'wrapper' => [
          'width' => '50',
        ],
        'placeholder' => 'Включен/Дополнительно',
      ],

      // Служебные поля


    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'hotel',
        ]
      ]
    ],
  ]);
});

add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_hotel_location2',
    'title' => 'Расположение отеля2',
    'fields' => [


    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'hotel',
        ]
      ]
    ],
  ]);
});

add_filter('acf/fields/post_object/query/name=hotel_country', 'filter_hotel_country_query', 10, 3);
function filter_hotel_country_query($args, $field, $post_id)
{
  $args['post_parent'] = 0;
  return $args;
}



