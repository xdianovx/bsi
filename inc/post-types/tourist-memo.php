<?php
/**
 * CPT: tourist_memo (Памятка туристам)
 * URL: /country/{country}/memo/
 */

/**
 * Query vars
 */
add_filter('query_vars', function ($vars) {
  if (!in_array('country_memo', $vars, true))
    $vars[] = 'country_memo';
  return $vars;
});

/**
 * CPT регистрация
 */
add_action('init', function () {
  register_post_type('tourist_memo', [
    'labels' => [
      'name' => 'Памятки туристам',
      'singular_name' => 'Памятка туристам',
      'add_new' => 'Добавить',
      'add_new_item' => 'Новая памятка',
      'edit_item' => 'Редактировать',
      'new_item' => 'Новая памятка',
      'view_item' => 'Смотреть',
      'search_items' => 'Искать',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Памятки туристам',
    ],
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 24,
    'menu_icon' => 'dashicons-media-text',
    'supports' => ['title', 'editor', 'excerpt'],
    'has_archive' => false,
    'rewrite' => false, // важно: у нас свой country роут
    'query_var' => true,
  ]);
}, 10);

/**
 * Роут: /country/{country}/memo/
 */
add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/memo/?$',
    'index.php?country_memo=$matches[1]',
    'top'
  );
}, 25);

/**
 * Роутинг на шаблон country-memo.php
 * Внутри шаблона получишь:
 * global $country_memo_data = ['country' => WP_Post, 'memo' => WP_Post|null, 'country_slug' => string]
 */
add_action('template_redirect', function () {
  $country_slug = (string) get_query_var('country_memo');
  if (!$country_slug)
    return;

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }

  $q = new WP_Query([
    'post_type' => 'tourist_memo',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'meta_query' => [
      [
        'key' => 'memo_country',
        'value' => (int) $country->ID,
        'compare' => '=',
      ],
    ],
    'orderby' => 'date',
    'order' => 'DESC',
  ]);

  $memo_post = ($q->have_posts()) ? $q->posts[0] : null;
  wp_reset_postdata();

  global $country_memo_data;
  $country_memo_data = [
    'country' => $country,
    'country_slug' => $country_slug,
    'memo' => $memo_post,
  ];

  $template = locate_template('country-memo.php');
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
 * ACF: привязка к стране (под заголовком)
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_tourist_memo_country',
    'title' => 'Памятка — страна',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_memo_country',
        'label' => 'Страна',
        'name' => 'memo_country',
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
          'value' => 'tourist_memo',
        ],
      ],
    ],
  ]);
});

/**
 * Ограничим страны только верхним уровнем
 */
add_filter('acf/fields/post_object/query/key=field_memo_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);

/**
 * Yoast breadcrumbs для памяток туристам:
 * - country memo: Главная > Страны > {Страна} > Памятка туристам
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
  $country_slug = get_query_var('country_memo');
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
  $new[] = ['text' => 'Памятка туристам'];

  return $new;
});