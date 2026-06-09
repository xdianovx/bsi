<?php
/**
 * CPT: hotel_info (Информация об отелях)
 * По образцу tourist_memo. Привязка к стране через ACF `hotel_info_country`.
 * URL раздела страны: /country/{country}/informaciya-ob-otelyah/
 * (роут зарегистрирован в inc/post-types/country.php + single-country.php →
 *  шаблон country-hotels-info.php).
 */

/**
 * CPT регистрация
 */
add_action('init', function () {
  register_post_type('hotel_info', [
    'labels' => [
      'name' => 'Информация об отелях',
      'singular_name' => 'Информация об отеле',
      'add_new' => 'Добавить',
      'add_new_item' => 'Новая запись',
      'edit_item' => 'Редактировать',
      'new_item' => 'Новая запись',
      'view_item' => 'Смотреть',
      'search_items' => 'Искать',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Информация об отелях',
    ],
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 25,
    'menu_icon' => 'dashicons-building',
    'supports' => ['title', 'editor', 'excerpt'],
    'has_archive' => false,
    'rewrite' => false, // свой country-роут
    'query_var' => true,
  ]);
}, 10);

/**
 * ACF: привязка к стране (под заголовком)
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_hotel_info_country',
    'title' => 'Информация об отелях — страна',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_hotel_info_country',
        'label' => 'Страна',
        'name' => 'hotel_info_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'hotel_info',
        ],
      ],
    ],
  ]);
});

/**
 * Ограничим страны только верхним уровнем
 */
add_filter('acf/fields/post_object/query/key=field_hotel_info_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);

/**
 * Yoast breadcrumbs: Главная > Страны > {Страна} > Информация об отелях
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
  $country_slug = get_query_var('country_hotels_info');
  if (empty($country_slug)) {
    return $links;
  }

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    return $links;
  }

  $countries_page = get_page_by_path('strany');
  $countries_url = $countries_page ? get_permalink($countries_page->ID) : get_post_type_archive_link('country');

  $new = [];
  $new[] = ['url' => home_url('/'), 'text' => 'Главная'];

  if ($countries_url) {
    $new[] = ['url' => $countries_url, 'text' => $countries_page ? ($countries_page->post_title ?: 'Страны') : 'Страны'];
  }

  $new[] = ['url' => get_permalink($country->ID), 'text' => $country->post_title];
  $new[] = ['text' => 'Информация об отелях'];

  return $new;
});
