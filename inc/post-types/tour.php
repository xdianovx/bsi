<?php
/**
 * CPT: tour
 * Tax: tour_type, tour_include
 * Uses existing taxonomies: region, resort (registered elsewhere via filters)
 */

/**
 * 1) Добавляем tour в post_types, где подключены region/resort
 * ВАЖНО: этот файл должен быть подключен ДО кода, который регистрирует region/resort через apply_filters(...).
 */
add_filter('region_taxonomy_post_types', function ($types) {
  $types[] = 'tour';
  return array_values(array_unique($types));
}, 5);

add_filter('resort_taxonomy_post_types', function ($types) {
  $types[] = 'tour';
  return array_values(array_unique($types));
}, 5);


/**
 * 2) Таксономии тура
 * Делай раньше CPT, чтобы в админке стабильно появились метабоксы.
 */
add_action('init', function () {

  register_taxonomy('tour_type', ['tour'], [
    'labels' => [
      'name' => 'Типы туров',
      'singular_name' => 'Тип тура',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип',
      'new_item_name' => 'Новый тип',
      'menu_name' => 'Типы туров',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => true,
    'rewrite' => false,
    'query_var' => true,
  ]);

  register_taxonomy('tour_include', ['tour'], [
    'labels' => [
      'name' => 'Включено в тур',
      'singular_name' => 'Пункт включено',
      'search_items' => 'Найти',
      'all_items' => 'Все пункты',
      'edit_item' => 'Редактировать',
      'update_item' => 'Обновить',
      'add_new_item' => 'Добавить пункт',
      'new_item_name' => 'Новый пункт',
      'menu_name' => 'Включено в тур',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,

    // как теги
    'hierarchical' => false,

    'rewrite' => false,
    'query_var' => true,
  ]);

}, 9);


/**
 * 3) CPT: tour
 * rewrite выключаем — чтобы не было /tour/slug/ (нам нужен только /country/.../tours/.../)
 */
add_action('init', function () {

  register_post_type('tour', [
    'labels' => [
      'name' => 'Туры',
      'singular_name' => 'Тур',
      'add_new' => 'Добавить тур',
      'add_new_item' => 'Новый тур',
      'edit_item' => 'Редактировать тур',
      'new_item' => 'Новый тур',
      'view_item' => 'Смотреть тур',
      'search_items' => 'Искать туры',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Туры',
    ],

    'public' => true,
    'publicly_queryable' => true,

    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 23,
    'menu_icon' => 'dashicons-location-alt',

    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],

    // метабоксы таксономий
    'taxonomies' => ['region', 'resort', 'tour_type', 'tour_include'],

    'has_archive' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);

}, 10);


/**
 * 4) Привязки таксономий к tour (страхуемся от порядка подключения)
 */
add_action('init', function () {
  if (taxonomy_exists('region')) {
    register_taxonomy_for_object_type('region', 'tour');
  }
  if (taxonomy_exists('resort')) {
    register_taxonomy_for_object_type('resort', 'tour');
  }
  if (taxonomy_exists('tour_type')) {
    register_taxonomy_for_object_type('tour_type', 'tour');
  }
  if (taxonomy_exists('tour_include')) {
    register_taxonomy_for_object_type('tour_include', 'tour');
  }
}, 30);


/**
 * (опционально) Автосоздание дефолтных пунктов "Включено в тур"
 * Если не нужно — просто удали этот блок.
 */
add_action('init', function () {
  if (!taxonomy_exists('tour_include'))
    return;

  $defaults = ['Экскурсии', 'Трансфер', 'Страховка', 'Отель'];

  foreach ($defaults as $name) {
    if (!term_exists($name, 'tour_include')) {
      wp_insert_term($name, 'tour_include');
    }
  }
}, 31);


/**
 * 5) ACF: иконка для термина tour_include
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_tour_include_term',
    'title' => 'Включено в тур — иконка',
    'fields' => [
      [
        'key' => 'field_tour_include_icon',
        'label' => 'Иконка',
        'name' => 'tour_include_icon',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'thumbnail',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'tour_include',
        ],
      ],
    ],
  ]);
});

/**
 * 6) Query vars
 */
add_filter('query_vars', function ($vars) {
  if (!in_array('country_in_path', $vars, true))
    $vars[] = 'country_in_path';
  if (!in_array('country_tours', $vars, true))
    $vars[] = 'country_tours';
  return $vars;
});

/**
 * 7) Роуты:
 * - /country/{country}/tours/ -> список туров страны
 * - /country/{country}/tours/{tour}/ -> single tour
 */
add_action('init', function () {

  add_rewrite_rule(
    '^country/([^/]+)/tours/?$',
    'index.php?country_tours=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/tours/([^/]+)/?$',
    'index.php?post_type=tour&name=$matches[2]&country_in_path=$matches[1]',
    'top'
  );

}, 25);


/**
 * 8) Генерация правильной ссылки на тур
 */
add_filter('post_type_link', function ($post_link, $post) {
  if ($post->post_type !== 'tour')
    return $post_link;

  $country_id = function_exists('get_field') ? get_field('tour_country', $post->ID) : 0;
  $country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;

  if (!$country_id)
    return $post_link;

  $country_slug = get_post_field('post_name', $country_id);
  if (!$country_slug)
    return $post_link;

  return trailingslashit(home_url('/country/' . $country_slug . '/tours/' . $post->post_name));
}, 10, 2);


/**
 * 9) Валидация пути single: если страна в URL не совпадает — 404
 */
add_action('template_redirect', function () {
  if (!is_singular('tour'))
    return;

  $country_in_path = get_query_var('country_in_path');
  if (!$country_in_path)
    return;

  $tour_id = get_queried_object_id();
  if (!$tour_id)
    return;

  $country_id = function_exists('get_field') ? get_field('tour_country', $tour_id) : 0;
  $country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;

  if (!$country_id)
    return;

  $real_country_slug = get_post_field('post_name', $country_id);

  if ($real_country_slug && $real_country_slug !== $country_in_path) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }
});


/**
 * 10) Роутинг списка туров страны на country-tours.php
 */
add_action('template_redirect', function () {
  $country_slug = get_query_var('country_tours');
  if (empty($country_slug))
    return;

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }

  global $country_tours_data;
  $country_tours_data = [
    'country' => $country,
    'country_slug' => $country_slug,
  ];

  $template = locate_template('country-tours.php');
  if ($template) {
    include $template;
    exit;
  }

  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  exit;
});


/**
 * 11) ACF поля тура
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_tour_country',
    'title' => 'Тур — страна',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_tour_country',
        'label' => 'Страна',
        'name' => 'tour_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_tour_booking_url',
        'label' => 'Ссылка для бронирования',
        'name' => 'tour_booking_url',
        'type' => 'url',
        'wrapper' => ['width' => '100'],
        'placeholder' => 'https://...',
        'instructions' => 'Обычная ссылка для бронирования или ссылка на поиск экскурсии из Самотура (https://online.bsigroup.ru/search_excursion?TOWNFROMINC=1&STATEINC=32&TOURINC=635). Если это ссылка на поиск экскурсии, будут автоматически загружены цены по звездности отелей.',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'tour',
        ],
      ],
    ],
  ]);

  acf_add_local_field_group([
    'key' => 'group_tour_fields',
    'title' => 'Тур — поля',
    'position' => 'normal',
    'menu_order' => 10,
    'fields' => [
      [
        'key' => 'field_tour_is_popular',
        'label' => 'Популярный',
        'name' => 'is_popular',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'instructions' => 'Если выбрано, тур отобразится в слайдере популярных туров',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_tour_price_from',
        'label' => 'Цена от',
        'name' => 'price_from',
        'type' => 'number',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: 50000',
        'instructions' => 'Цена в рублях (только число)',
      ],
      [
        'key' => 'field_tour_gallery',
        'label' => 'Галерея тура',
        'name' => 'tour_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 30,
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_tour_duration',
        'label' => 'Продолжительность',
        'name' => 'tour_duration',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: 7 дней / 6 ночей',
      ],
      [
        'key' => 'field_tour_route',
        'label' => 'Маршрут',
        'name' => 'tour_route',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Денпасар – Джимбаран – Убуд – Север Бали – ...',
      ],
      [
        'key' => 'field_tour_program',
        'label' => 'Программа тура (по дням)',
        'name' => 'tour_program',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить день',
        'sub_fields' => [
          [
            'key' => 'field_tour_program_day_title',
            'label' => 'Заголовок дня',
            'name' => 'day_title',
            'type' => 'text',
            'wrapper' => ['width' => '30'],
            'placeholder' => 'День 1 / Прилет / ...',
          ],
          [
            'key' => 'field_tour_program_day_content',
            'label' => 'Описание дня',
            'name' => 'day_content',
            'type' => 'wysiwyg',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 0,
            'wrapper' => ['width' => '70'],
          ],
        ],
      ],
      [
        'key' => 'field_tour_included',
        'label' => 'В стоимость включено',
        'name' => 'tour_included',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_tour_not_included',
        'label' => 'В стоимость не включено',
        'name' => 'tour_not_included',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_tour_extra',
        'label' => 'Дополнительно',
        'name' => 'tour_extra',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'tour',
        ],
      ],
    ],
  ]);
});


add_filter('acf/fields/post_object/query/key=field_tour_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);


/**
 * 12) Yoast breadcrumbs:
 * - single tour: Главная > Страны > {Страна} > Туры > {Тур}
 * - country tours list: Главная > Страны > {Страна} > Туры
 */
add_filter('wpseo_breadcrumb_links', function ($links) {

  $country_slug = get_query_var('country_tours');
  if (!empty($country_slug)) {
    $country = get_page_by_path($country_slug, OBJECT, 'country');
    if (!$country)
      return $links;

    $countries_page = get_page_by_path('strany');

    $new = [];
    $new[] = ['url' => home_url('/'), 'text' => 'Главная'];

    if ($countries_page) {
      $new[] = ['url' => get_permalink($countries_page->ID), 'text' => $countries_page->post_title ?: 'Страны'];
    } else {
      $new[] = ['url' => get_post_type_archive_link('country'), 'text' => 'Страны'];
    }

    $new[] = ['url' => get_permalink($country->ID), 'text' => $country->post_title];
    $new[] = ['text' => 'Туры'];

    return $new;
  }

  if (is_singular('tour')) {
    $tour_id = get_queried_object_id();
    if (!$tour_id)
      return $links;

    $country_id = function_exists('get_field') ? get_field('tour_country', $tour_id) : 0;
    $country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;

    if (!$country_id)
      return $links;

    $country_slug = get_post_field('post_name', $country_id);
    $country_title = get_the_title($country_id);
    if (!$country_slug || !$country_title)
      return $links;

    $countries_page = get_page_by_path('strany');
    $countries_url = $countries_page ? get_permalink($countries_page->ID) : get_post_type_archive_link('country');

    $tour_list_url = home_url('/country/' . $country_slug . '/tours/');

    $new = [];
    $new[] = ['url' => home_url('/'), 'text' => 'Главная'];

    if ($countries_url) {
      $new[] = ['url' => $countries_url, 'text' => $countries_page ? ($countries_page->post_title ?: 'Страны') : 'Страны'];
    }

    $new[] = ['url' => get_permalink($country_id), 'text' => $country_title];
    $new[] = ['url' => $tour_list_url, 'text' => 'Туры'];
    $new[] = ['text' => get_the_title($tour_id)];

    return $new;
  }

  return $links;
});