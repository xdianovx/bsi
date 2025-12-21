<?php

add_action('init', 'register_post_types_country');
function register_post_types_country()
{
  register_post_type('country', [
    'label' => null,
    'labels' => [
      'name' => 'Страны',
      'singular_name' => 'Страна',
      'add_new' => 'Добавить страну',
      'add_new_item' => 'Новая страна',
      'edit_item' => 'Редактирование страны',
      'new_item' => 'Новая страна',
      'view_item' => 'Смотреть страну',
      'search_items' => 'Искать страну',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'parent_item_colon' => 'Страна',
      'menu_name' => 'Страны',
    ],
    'public' => true,
    'show_in_menu' => null,
    'hierarchical' => true,
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'trackbacks', 'post-formats', 'page-attributes'],
    'taxonomies' => [],
    'has_archive' => true,
    'rewrite' => [
      'slug' => 'country',
      'with_front' => false,
      'hierarchical' => true,
    ],
    'query_var' => true,
  ]);
}

add_action('template_redirect', function () {
  if (!is_post_type_archive('country')) {
    return;
  }

  $countries_page = get_page_by_path('strany');
  if ($countries_page) {
    wp_redirect(get_permalink($countries_page->ID), 301);
    exit;
  }
});

function bsi_get_promo_countries()
{
  $result = [];

  $promos = get_posts([
    'post_type' => 'promo',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ]);

  if (empty($promos)) {
    return $result;
  }

  foreach ($promos as $promo_id) {
    $countries = function_exists('get_field') ? get_field('promo_countries', $promo_id) : [];

    if (empty($countries)) {
      continue;
    }

    if (!is_array($countries)) {
      $countries = [$countries];
    }

    foreach ($countries as $country_id) {
      if (empty($country_id)) {
        continue;
      }

      if (empty($result[$country_id])) {
        $flag = function_exists('get_field') ? get_field('flag', $country_id) : '';
        $flag_url = '';

        if ($flag) {
          if (is_array($flag) && !empty($flag['url'])) {
            $flag_url = esc_url($flag['url']);
          } else {
            $flag_url = esc_url($flag);
          }
        }

        $result[$country_id] = [
          'id' => $country_id,
          'title' => get_the_title($country_id),
          'flag' => $flag_url,
          'count' => 0,
        ];
      }

      $result[$country_id]['count']++;
    }
  }

  uasort($result, function ($a, $b) {
    return strcmp($a['title'], $b['title']);
  });

  return $result;
}

add_action('init', function () {
  $region_post_types = apply_filters('region_taxonomy_post_types', ['hotel', 'tour']);
  $resort_post_types = apply_filters('resort_taxonomy_post_types', ['hotel', 'tour']);

  register_taxonomy('region', $region_post_types, [
    'labels' => [
      'name' => 'Регионы',
      'singular_name' => 'Регион',
      'search_items' => 'Найти регион',
      'all_items' => 'Все регионы',
      'edit_item' => 'Редактировать регион',
      'update_item' => 'Обновить регион',
      'add_new_item' => 'Добавить регион',
      'new_item_name' => 'Новый регион',
      'menu_name' => 'Регионы',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => true,
    'rewrite' => false,
    'query_var' => true,
  ]);

  register_taxonomy('resort', $resort_post_types, [
    'labels' => [
      'name' => 'Курорты',
      'singular_name' => 'Курорт',
      'search_items' => 'Найти курорт',
      'all_items' => 'Все курорты',
      'edit_item' => 'Редактировать курорт',
      'update_item' => 'Обновить',
      'add_new_item' => 'Добавить курорт',
      'new_item_name' => 'Новый курорт',
      'menu_name' => 'Курорты',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);
}, 20);

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_region_term_meta',
    'title' => 'Регион',
    'fields' => [
      [
        'key' => 'field_region_country',
        'label' => 'Страна',
        'name' => 'region_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'required' => 1,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'region',
        ],
      ],
    ],
  ]);

  acf_add_local_field_group([
    'key' => 'group_resort_term_meta',
    'title' => 'Курорт',
    'fields' => [
      [
        'key' => 'field_resort_region',
        'label' => 'Регион',
        'name' => 'resort_region',
        'type' => 'taxonomy',
        'taxonomy' => 'region',
        'field_type' => 'select',
        'return_format' => 'id',
        'add_term' => 0,
        'save_terms' => 0,
        'load_terms' => 0,
        'required' => 1,
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

add_filter('query_vars', function ($vars) {
  $vars[] = 'country_in_path';
  $vars[] = 'region_in_path';

  $vars[] = 'country_hotels';
  $vars[] = 'country_promos';
  $vars[] = 'country_resorts';

  $vars[] = 'country_memo';
  $vars[] = 'country_entry_rules';

  $vars[] = 'country_visa';

  return $vars;
});

add_action('init', function () {

  add_rewrite_rule(
    '^country/([^/]+)/hotel/?$',
    'index.php?post_type=country&name=$matches[1]&country_hotels=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/promo/?$',
    'index.php?post_type=country&name=$matches[1]&country_promos=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/kurorty/?$',
    'index.php?post_type=country&name=$matches[1]&country_resorts=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/pamyatka/?$',
    'index.php?post_type=country&name=$matches[1]&country_memo=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/pravila-vyezda/?$',
    'index.php?post_type=country&name=$matches[1]&country_entry_rules=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/visa/?$',
    'index.php?country_visa=$matches[1]',
    'top'
  );

}, 20);

add_action('init', function () {
  $reserved = '(?:hotel|promo|visa|tours|tour|news|fit|akcii|novosti|kurorty|pamyatka|pravila-vyezda)';

  add_rewrite_rule(
    '^country/([^/]+)/(?!' . $reserved . '(?:/|$))([^/]+)/(?!' . $reserved . '(?:/|$))([^/]+)/?$',
    'index.php?taxonomy=resort&term=$matches[3]&country_in_path=$matches[1]&region_in_path=$matches[2]',
    'top'
  );
}, 30);

add_filter('term_link', function ($url, $term, $taxonomy) {
  if ($taxonomy !== 'resort') {
    return $url;
  }

  $region_id = function_exists('get_field') ? get_field('resort_region', 'term_' . $term->term_id) : '';
  if (empty($region_id)) {
    return $url;
  }

  $region_term = get_term($region_id, 'region');
  if (empty($region_term) || is_wp_error($region_term)) {
    return $url;
  }

  $country_id = function_exists('get_field') ? get_field('region_country', 'term_' . $region_term->term_id) : '';
  if (empty($country_id)) {
    return $url;
  }

  $country_slug = get_post_field('post_name', $country_id);
  if (empty($country_slug)) {
    return $url;
  }

  return home_url('/country/' . $country_slug . '/' . $region_term->slug . '/' . $term->slug . '/');
}, 10, 3);

add_filter('request', function ($vars) {
  if (
    !empty($vars['taxonomy']) &&
    $vars['taxonomy'] === 'resort' &&
    !empty($vars['term']) &&
    !empty($vars['country_in_path']) &&
    !empty($vars['region_in_path'])
  ) {
    $country_slug = sanitize_title($vars['country_in_path']);
    $region_slug = sanitize_title($vars['region_in_path']);
    $resort_slug = sanitize_title($vars['term']);

    $maybe_country_page = get_page_by_path($country_slug . '/' . $region_slug . '/' . $resort_slug, OBJECT, 'country');
    if ($maybe_country_page) {
      unset($vars['taxonomy'], $vars['term'], $vars['country_in_path'], $vars['region_in_path']);
      $vars['post_type'] = 'country';
      $vars['p'] = $maybe_country_page->ID;
      return $vars;
    }
  }

  return $vars;
}, 0);

add_action('template_redirect', function () {
  if (!is_tax('resort')) {
    return;
  }

  $term = get_queried_object();
  $country_in_path = get_query_var('country_in_path');
  $region_in_path = get_query_var('region_in_path');

  if (empty($term) || empty($term->term_id) || empty($country_in_path) || empty($region_in_path)) {
    return;
  }

  $region_id = function_exists('get_field') ? get_field('resort_region', 'term_' . $term->term_id) : '';
  if (empty($region_id)) {
    return;
  }

  $region_term = get_term($region_id, 'region');
  if (empty($region_term) || is_wp_error($region_term)) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }

  if ($region_term->slug !== $region_in_path) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }

  $country_id = function_exists('get_field') ? get_field('region_country', 'term_' . $region_term->term_id) : '';
  if (empty($country_id)) {
    return;
  }

  $real_country_slug = get_post_field('post_name', $country_id);
  if (!empty($real_country_slug) && $real_country_slug !== $country_in_path) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }
});

add_action('template_redirect', function () {
  $country_slug = get_query_var('country_resorts');
  if (empty($country_slug)) {
    return;
  }

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }

  global $country_resorts_data;
  $country_resorts_data = [
    'country' => $country,
    'country_slug' => $country_slug,
  ];

  $template = locate_template('country-resorts.php');
  if ($template) {
    include $template;
    exit;
  }

  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  exit;
});

add_action('template_redirect', function () {
  $country_slug = (string) get_query_var('country_memo');
  if (empty($country_slug)) {
    return;
  }

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }

  global $country_memo_data;
  $country_memo_data = [
    'country' => $country,
    'country_slug' => $country_slug,
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

add_action('template_redirect', function () {
  $country_slug = (string) get_query_var('country_entry_rules');
  if (empty($country_slug)) {
    return;
  }

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }

  global $country_entry_rules_data;
  $country_entry_rules_data = [
    'country' => $country,
    'country_slug' => $country_slug,
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

add_action('pre_get_posts', function ($q) {
  if (is_admin() || !$q->is_main_query()) {
    return;
  }

  $country_slug = (string) get_query_var('country_visa');
  if (!$country_slug) {
    return;
  }

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    $q->set_404();
    status_header(404);
    return;
  }

  $visa_type_ids = [];
  if (!empty($_GET['visa_type'])) {
    $raw = $_GET['visa_type'];
    $raw = is_array($raw) ? $raw : [$raw];
    $visa_type_ids = array_values(array_filter(array_map('intval', $raw)));
  }

  $args = [
    'post_type' => 'visa',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => [
      ['key' => 'visa_country', 'value' => (int) $country->ID, 'compare' => '='],
    ],
  ];

  if (!empty($visa_type_ids)) {
    $args['tax_query'] = [
      [
        'taxonomy' => 'visa_type',
        'field' => 'term_id',
        'terms' => $visa_type_ids,
        'operator' => 'IN',
      ],
    ];
  }

  $visa_ids = get_posts($args);

  if (empty($visa_ids)) {
    $q->set_404();
    status_header(404);
    return;
  }

  $visa_id = (int) $visa_ids[0];

  $q->set('post_type', 'visa');
  $q->set('p', $visa_id);

  $q->set('name', '');
  $q->set('pagename', '');
  $q->set('page_id', '');

  $q->is_singular = true;
  $q->is_single = true;
  $q->is_page = false;
  $q->is_home = false;

}, 0);

add_filter('wpseo_breadcrumb_links', function ($links) {

  if (is_singular('visa')) {
    return $links;
  }

  if (is_tax('resort')) {
    $term = get_queried_object();

    $region_id = function_exists('get_field') ? get_field('resort_region', 'term_' . $term->term_id) : 0;
    $region_term = $region_id ? get_term((int) $region_id, 'region') : null;

    $country_id = 0;
    if ($region_term && !is_wp_error($region_term)) {
      $country_id = function_exists('get_field') ? (int) get_field('region_country', 'term_' . $region_term->term_id) : 0;
    }

    $countries_page = get_page_by_path('strany');

    $new_links = [];
    $new_links[] = ['url' => home_url('/'), 'text' => 'Главная'];

    if ($countries_page) {
      $new_links[] = ['url' => get_permalink($countries_page->ID), 'text' => $countries_page->post_title ?: 'Страны'];
    } else {
      $new_links[] = ['url' => get_post_type_archive_link('country'), 'text' => 'Страны'];
    }

    if ($country_id) {
      $new_links[] = ['url' => get_permalink($country_id), 'text' => get_the_title($country_id)];
    }

    $new_links[] = ['text' => $term->name];
    return $new_links;
  }

  return $links;
});