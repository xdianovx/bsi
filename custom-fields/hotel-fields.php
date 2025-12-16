<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_hotel_info',
    'title' => 'Основная информация',
    'fields' => [
      [
        'key' => 'field_hotel_country',
        'label' => 'Страна',
        'name' => 'hotel_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'required' => 1,
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'wrapper' => [
          'width' => '25',
        ],
      ],
      [
        'key' => 'field_hotel_region',
        'label' => 'Регион',
        'name' => 'hotel_region',
        'type' => 'taxonomy',
        'taxonomy' => 'region',
        'field_type' => 'select',
        'return_format' => 'id',
        'add_term' => 0,
        'save_terms' => 1,
        'load_terms' => 1,
        'allow_null' => 1,
        'multiple' => 0,
        'ui' => 1,
        'ajax' => 1,
        'wrapper' => [
          'width' => '25',
        ],
      ],
      [
        'key' => 'field_hotel_resort',
        'label' => 'Курорт',
        'name' => 'hotel_resort',
        'type' => 'taxonomy',
        'taxonomy' => 'resort',
        'field_type' => 'select',
        'return_format' => 'id',
        'add_term' => 0,
        'save_terms' => 1,
        'load_terms' => 1,
        'allow_null' => 1,
        'multiple' => 0,
        'ui' => 1,
        'ajax' => 1,
        'conditional_logic' => [
          [
            [
              'field' => 'field_hotel_region',
              'operator' => '!=empty',
            ],
          ],
        ],
        'wrapper' => [
          'width' => '25',
        ],
      ],
      [
        'key' => 'field_hotel_city',
        'label' => 'Город',
        'name' => 'hotel_city',
        'type' => 'text',
        'required' => 0,
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
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'hotel',
        ],
      ],
    ],
  ]);
});

add_filter('acf/fields/post_object/query/name=hotel_country', function ($args, $field, $post_id) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 3);

add_filter('acf/fields/taxonomy/query/name=hotel_region', function ($args, $field, $post_id) {
  $country_id = '';

  if (!empty($_POST['acf']['field_hotel_country'])) {
    $country_id = $_POST['acf']['field_hotel_country'];
  }

  if (!$country_id && $post_id) {
    $country_id = get_field('hotel_country', $post_id);
  }

  if ($country_id) {
    if (empty($args['meta_query'])) {
      $args['meta_query'] = [];
    }

    $args['meta_query'][] = [
      'key' => 'region_country',
      'value' => $country_id,
      'compare' => '=',
    ];
  }

  return $args;
}, 10, 3);

add_filter('acf/fields/taxonomy/query/name=hotel_resort', function ($args, $field, $post_id) {
  $region_id = '';

  if (!empty($_POST['acf']['field_hotel_region'])) {
    $region_id = $_POST['acf']['field_hotel_region'];
  }

  if (!$region_id && $post_id) {
    $region_id = get_field('hotel_region', $post_id);
  }

  if (is_array($region_id)) {
    $region_id = reset($region_id);
  }

  if ($region_id) {
    if (empty($args['meta_query'])) {
      $args['meta_query'] = [];
    }

    $args['meta_query'][] = [
      'key' => 'resort_region',
      'value' => $region_id,
      'compare' => '=',
    ];
  }

  return $args;
}, 10, 3);

add_filter('acf/fields/taxonomy/result/name=hotel_region', function ($text, $term, $field, $post_id) {
  $country_id = get_field('region_country', 'term_' . $term->term_id);

  if ($country_id) {
    $country_title = get_the_title($country_id);
    if ($country_title) {
      return $country_title . ' — ' . $term->name;
    }
  }

  return $text;
}, 10, 4);

add_filter('acf/fields/taxonomy/result/name=hotel_resort', function ($text, $term, $field, $post_id) {
  $region_id = get_field('resort_region', 'term_' . $term->term_id);

  if ($region_id) {
    $region_term = get_term($region_id, 'region');
    $region_title = $region_term && !is_wp_error($region_term) ? $region_term->name : '';

    $country_id = $region_term ? get_field('region_country', 'term_' . $region_term->term_id) : '';
    $country_title = $country_id ? get_the_title($country_id) : '';

    $parts = [];
    if ($country_title)
      $parts[] = $country_title;
    if ($region_title)
      $parts[] = $region_title;
    $parts[] = $term->name;

    return implode(' — ', $parts);
  }

  return $text;
}, 10, 4);