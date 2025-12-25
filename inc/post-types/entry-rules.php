<?php
/**
 * CPT: entry_rules (Правила въезда)
 * URL: /country/{country}/entry-rules/
 */

/**
 * Query vars
 */
add_filter('query_vars', function ($vars) {
  if (!in_array('country_entry_rules', $vars, true))
    $vars[] = 'country_entry_rules';
  return $vars;
});

/**
 * CPT регистрация
 */
add_action('init', function () {
  register_post_type('entry_rules', [
    'labels' => [
      'name' => 'Правила въезда',
      'singular_name' => 'Правила въезда',
      'add_new' => 'Добавить',
      'add_new_item' => 'Новые правила',
      'edit_item' => 'Редактировать',
      'new_item' => 'Новые правила',
      'view_item' => 'Смотреть',
      'search_items' => 'Искать',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Правила въезда',
    ],
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 25,
    'menu_icon' => 'dashicons-clipboard',
    'supports' => ['title', 'editor', 'excerpt'],
    'has_archive' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);
}, 10);

/**
 * Роут: /country/{country}/entry-rules/
 */
add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/entry-rules/?$',
    'index.php?country_entry_rules=$matches[1]',
    'top'
  );
}, 25);

/**
 * Роутинг на шаблон country-entry-rules.php
 * global $country_entry_rules_data = ['country'=>WP_Post, 'rules'=>WP_Post|null, 'country_slug'=>string]
 */
add_action('template_redirect', function () {
  $country_slug = (string) get_query_var('country_entry_rules');
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
    'post_type' => 'entry_rules',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'meta_query' => [
      [
        'key' => 'entry_rules_country',
        'value' => (int) $country->ID,
        'compare' => '=',
      ],
    ],
    'orderby' => 'date',
    'order' => 'DESC',
  ]);

  $rules_post = ($q->have_posts()) ? $q->posts[0] : null;
  wp_reset_postdata();

  global $country_entry_rules_data;
  $country_entry_rules_data = [
    'country' => $country,
    'country_slug' => $country_slug,
    'rules' => $rules_post,
  ];

  $template = locate_template('country-entry-rules.php');
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
    'key' => 'group_entry_rules_country',
    'title' => 'Правила въезда — страна',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_entry_rules_country',
        'label' => 'Страна',
        'name' => 'entry_rules_country',
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
          'value' => 'entry_rules',
        ],
      ],
    ],
  ]);
});

/**
 * Ограничим страны только верхним уровнем
 */
add_filter('acf/fields/post_object/query/key=field_entry_rules_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);

/**
 * Yoast breadcrumbs для правил въезда:
 * - country entry rules: Главная > Страны > {Страна} > Правила въезда
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
  $country_slug = get_query_var('country_entry_rules');
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
  $new[] = ['text' => 'Правила въезда'];

  return $new;
});