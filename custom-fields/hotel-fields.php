<?php

add_action('init', 'bsi_attach_hotel_taxonomies');
function bsi_attach_hotel_taxonomies(): void
{
  if (taxonomy_exists('region')) {
    register_taxonomy_for_object_type('region', 'hotel');
  }
  if (taxonomy_exists('resort')) {
    register_taxonomy_for_object_type('resort', 'hotel');
  }
}

add_action('acf/init', 'bsi_register_hotel_acf_groups');
function bsi_register_hotel_acf_groups(): void
{
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_hotel_geo',
    'title' => 'ГЕО',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_hotel_geo_notice',
        'label' => '',
        'name' => 'hotel_geo_notice',
        'type' => 'message',
        'message' => 'Страна выбирается в поле ниже. Регионы и курорты выбираются стандартно в правой колонке в блоках таксономий.',
        'new_lines' => 'wpautop',
        'esc_html' => 0,
        'wrapper' => ['width' => '100'],
      ],
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
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_hotel_map_lat',
        'label' => 'Широта (lat)',
        'name' => 'map_lat',
        'type' => 'number',
        'step' => 0.000001,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_hotel_map_lng',
        'label' => 'Долгота (lng)',
        'name' => 'map_lng',
        'type' => 'number',
        'step' => 0.000001,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_hotel_map_zoom',
        'label' => 'Zoom (карта)',
        'name' => 'map_zoom',
        'type' => 'number',
        'min' => 1,
        'max' => 20,
        'step' => 1,
        'default_value' => 14,
        'wrapper' => ['width' => '33'],
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
    'style' => 'seamless',
  ]);

  acf_add_local_field_group([
    'key' => 'group_hotel_basic',
    'title' => 'Основная информация',
    'fields' => [
      [
        'key' => 'field_hotel_rating',
        'label' => 'Рейтинг (звезды)',
        'name' => 'rating',
        'type' => 'number',
        'min' => 1,
        'max' => 5,
        'step' => 1,
        'placeholder' => 'от 1 до 5',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_is_featured',
        'label' => 'Популярный отель',
        'name' => 'is_popular',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'instructions' => 'Покажет отель в блоке «Популярные отели» (слайдер на главной/в секциях по странам).',
        'wrapper' => ['width' => '50'],
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
    'menu_order' => 1,
  ]);

  acf_add_local_field_group([
    'key' => 'group_hotel_contacts',
    'title' => 'Контакты и адрес',
    'fields' => [
      [
        'key' => 'field_hotel_address',
        'label' => 'Адрес',
        'name' => 'address',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_phone',
        'label' => 'Телефон',
        'name' => 'phone',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_website',
        'label' => 'Сайт отеля',
        'name' => 'website',
        'type' => 'url',
        'wrapper' => ['width' => '100'],
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
    'menu_order' => 2,
  ]);

  acf_add_local_field_group([
    'key' => 'group_hotel_booking',
    'title' => 'Бронирование и цены',
    'fields' => [
      [
        'key' => 'field_price',
        'label' => 'Стоимость',
        'name' => 'price',
        'type' => 'text',
        'instructions' => 'Минимальная цена в рублях',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_price_text',
        'label' => 'Текст к цене',
        'name' => 'price_text',
        'type' => 'text',
        'instructions' => 'Дополнительный текст к цене, например: "за 5 ночей", "за неделю" и т.д.',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_nights',
        'label' => 'Количество ночей',
        'name' => 'nights',
        'type' => 'number',
        'min' => 1,
        'instructions' => 'Количество ночей для тура',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_checkin_date',
        'label' => 'Дата начала заселения',
        'name' => 'checkin_date',
        'type' => 'date_picker',
        'display_format' => 'd/m/Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_check_in_time',
        'label' => 'Время заезда',
        'name' => 'check_in_time',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '14:00',
      ],
      [
        'key' => 'field_check_out_time',
        'label' => 'Время выезда',
        'name' => 'check_out_time',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '12:00',
      ],
      [
        'key' => 'field_hotel_booking_url',
        'label' => 'Ссылка на бронирование',
        'name' => 'booking_url',
        'type' => 'url',
        'instructions' => 'Сюда можно вставлять ссылку на Booking / сайт отеля / форму бронирования.',
        'wrapper' => ['width' => '100'],
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
    'menu_order' => 3,
  ]);

  acf_add_local_field_group([
    'key' => 'group_hotel_media',
    'title' => 'Медиа',
    'fields' => [
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
    'menu_order' => 4,
  ]);
}

add_action('acf/init', 'bsi_register_amenity_term_meta');
function bsi_register_amenity_term_meta(): void
{
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_amenity_term_meta',
    'title' => 'Удобство — иконка',
    'fields' => [
      [
        'key' => 'field_amenity_icon',
        'label' => 'Иконка',
        'name' => 'amenity_icon',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'thumbnail',
        'library' => 'all',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'amenity',
        ],
      ],
    ],
  ]);
}

add_filter('acf/fields/post_object/query/key=field_hotel_country', 'bsi_filter_hotel_country_parent_only', 10, 3);
function bsi_filter_hotel_country_parent_only(array $args, array $field, $post_id): array
{
  $args['post_parent'] = 0;
  return $args;
}

add_filter('pre_insert_term', 'bsi_block_numeric_terms', 10, 2);
function bsi_block_numeric_terms($term, string $taxonomy)
{
  if (!in_array($taxonomy, ['region', 'resort'], true)) {
    return $term;
  }

  $name = is_array($term) ? ($term['name'] ?? '') : (string) $term;
  $name = trim((string) $name);

  if ($name !== '' && preg_match('/^\d+$/', $name)) {
    return new WP_Error('bsi_numeric_term_blocked', 'Нельзя создавать термин из одних цифр.');
  }

  return $term;
}