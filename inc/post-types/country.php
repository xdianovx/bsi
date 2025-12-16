<?php

/* Регистрация CPT: Страны */
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

/* Редирект архива стран на страницу /strany */
add_action('template_redirect', function () {
  if (!is_post_type_archive('country')) {
    return;
  }

  $countries_page = get_page_by_path('strany');
  if ($countries_page) {
    wp_redirect(get_permalink($countries_page), 301);
    exit;
  }
});

/* Хелпер: страны, у которых есть акции */
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
    $countries = get_field('promo_countries', $promo_id);

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
        $flag = get_field('flag', $country_id);
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

/* Регистрация таксономий: Регионы и Курорты */
add_action('init', function () {

  $region_post_types = apply_filters('region_taxonomy_post_types', ['hotel']);
  $resort_post_types = apply_filters('resort_taxonomy_post_types', ['hotel']);

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
      'update_item' => 'Обновить курорт',
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


add_action('save_post_country', function ($post_id, $post, $update) {
  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  if ($post->post_status !== 'publish') {
    return;
  }

  $country_title = get_the_title($post_id);
  $country_slug = $post->post_name;

  if (!$country_title || !$country_slug) {
    return;
  }

  $exists = term_exists($country_slug, 'region');

  if (is_array($exists) && !empty($exists['term_id'])) {
    $term_id = $exists['term_id'];
  } elseif (is_numeric($exists)) {
    $term_id = $exists;
  } else {
    $created = wp_insert_term($country_title, 'region', [
      'slug' => $country_slug,
      'parent' => 0,
    ]);

    if (is_wp_error($created) || empty($created['term_id'])) {
      return;
    }

    $term_id = $created['term_id'];
  }

  wp_update_term($term_id, 'region', [
    'name' => $country_title,
    'slug' => $country_slug,
    'parent' => 0,
  ]);

  update_term_meta($term_id, 'linked_country_id', $post_id);
}, 10, 3);

add_action('before_delete_post', function ($post_id) {
  if (get_post_type($post_id) !== 'country') {
    return;
  }

  $slug = get_post_field('post_name', $post_id);
  if (!$slug) {
    return;
  }

  $exists = term_exists($slug, 'region');
  $term_id = null;

  if (is_array($exists) && !empty($exists['term_id'])) {
    $term_id = $exists['term_id'];
  } elseif (is_numeric($exists)) {
    $term_id = $exists;
  }

  if ($term_id) {
    wp_delete_term($term_id, 'region');
  }
}, 10);

function get_country_id_from_region_term($term_id)
{
  $country_id = get_field('region_country', 'term_' . $term_id);
  if ($country_id) {
    return $country_id;
  }

  $ancestors = get_ancestors($term_id, 'region');
  $root_id = $term_id;

  if (!empty($ancestors)) {
    $root_id = end($ancestors);
  }

  $linked = get_term_meta($root_id, 'linked_country_id', true);
  if ($linked) {
    return $linked;
  }

  $term = get_term($term_id, 'region');
  if ($term && !is_wp_error($term) && !empty($term->slug)) {
    $country = get_page_by_path($term->slug, OBJECT, 'country');
    if ($country) {
      return $country->ID;
    }
  }

  return '';
}

add_action('created_region', function ($term_id) {
  $country_id = get_field('region_country', 'term_' . $term_id);
  if ($country_id) {
    return;
  }

  $country_id = get_country_id_from_region_term($term_id);
  if (!$country_id) {
    return;
  }

  update_field('region_country', $country_id, 'term_' . $term_id);
}, 10);

add_action('edited_region', function ($term_id) {
  $country_id = get_country_id_from_region_term($term_id);
  if (!$country_id) {
    return;
  }

  update_field('region_country', $country_id, 'term_' . $term_id);
}, 10);

/* ACF поля для терминов: привязка региона к стране, курорта к региону */
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

/* Query vars для пути */
add_filter('query_vars', function ($vars) {
  $vars[] = 'country_in_path';
  $vars[] = 'region_in_path';
  return $vars;
});

/* Реврайты: /country/{country}/{region}/ и /country/{country}/{region}/{resort}/ */
add_action('init', function () {
  $reserved = '(?:hotel|promo|visa|news|fit|akcii|novosti)';

  add_rewrite_rule(
    '^country/([^/]+)/((?!' . $reserved . '$)[^/]+)/?$',
    'index.php?taxonomy=region&term=$matches[2]&country_in_path=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/((?!' . $reserved . '$)[^/]+)/((?!' . $reserved . '$)[^/]+)/?$',
    'index.php?taxonomy=resort&term=$matches[3]&country_in_path=$matches[1]&region_in_path=$matches[2]',
    'top'
  );
}, 30);

/* Канонические ссылки терминов (чтобы не было /region/slug и /resort/slug) */
add_filter('term_link', function ($url, $term, $taxonomy) {

  if ($taxonomy === 'region') {
    $country_id = get_field('region_country', 'term_' . $term->term_id);
    if (empty($country_id)) {
      return $url;
    }

    $country_slug = get_post_field('post_name', $country_id);
    if (empty($country_slug)) {
      return $url;
    }

    return home_url('/country/' . $country_slug . '/' . $term->slug . '/');
  }

  if ($taxonomy === 'resort') {
    $region_id = get_field('resort_region', 'term_' . $term->term_id);
    if (empty($region_id)) {
      return $url;
    }

    $region_term = get_term($region_id, 'region');
    if (empty($region_term) || is_wp_error($region_term)) {
      return $url;
    }

    $country_id = get_field('region_country', 'term_' . $region_term->term_id);
    if (empty($country_id)) {
      return $url;
    }

    $country_slug = get_post_field('post_name', $country_id);
    if (empty($country_slug)) {
      return $url;
    }

    return home_url('/country/' . $country_slug . '/' . $region_term->slug . '/' . $term->slug . '/');
  }

  return $url;
}, 10, 3);

/* Приоритет дочерних страниц страны над регионами/курортами */
add_filter('request', function ($vars) {

  if (
    !empty($vars['country_in_path']) &&
    !empty($vars['taxonomy']) &&
    $vars['taxonomy'] === 'region' &&
    !empty($vars['term'])
  ) {
    $country_slug = sanitize_title($vars['country_in_path']);
    $maybe_child_slug = sanitize_title($vars['term']);

    $country_child = get_page_by_path($country_slug . '/' . $maybe_child_slug, OBJECT, 'country');

    if ($country_child) {
      unset($vars['taxonomy'], $vars['term'], $vars['country_in_path'], $vars['region_in_path']);
      $vars['post_type'] = 'country';
      $vars['p'] = $country_child->ID;
      return $vars;
    }
  }

  if (
    !empty($vars['country_in_path']) &&
    !empty($vars['region_in_path']) &&
    !empty($vars['taxonomy']) &&
    $vars['taxonomy'] === 'resort' &&
    !empty($vars['term'])
  ) {
    $country_slug = sanitize_title($vars['country_in_path']);
    $region_slug = sanitize_title($vars['region_in_path']);
    $maybe_child_slug = sanitize_title($vars['term']);

    $country_child = get_page_by_path($country_slug . '/' . $region_slug . '/' . $maybe_child_slug, OBJECT, 'country');

    if ($country_child) {
      unset($vars['taxonomy'], $vars['term'], $vars['country_in_path'], $vars['region_in_path']);
      $vars['post_type'] = 'country';
      $vars['p'] = $country_child->ID;
      return $vars;
    }
  }

  return $vars;
}, 0);

/* Валидация пути: страна в URL должна совпадать с привязками */
add_action('template_redirect', function () {

  if (is_tax('region')) {
    $term = get_queried_object();
    $country_in_path = (string) get_query_var('country_in_path');

    if (empty($country_in_path) || empty($term) || empty($term->term_id)) {
      return;
    }

    $country_id = get_field('region_country', 'term_' . $term->term_id);
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
  }

  if (is_tax('resort')) {
    $term = get_queried_object();
    $country_in_path = (string) get_query_var('country_in_path');
    $region_in_path = (string) get_query_var('region_in_path');

    if (empty($country_in_path) || empty($region_in_path) || empty($term) || empty($term->term_id)) {
      return;
    }

    $region_id = get_field('resort_region', 'term_' . $term->term_id);
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

    $country_id = get_field('region_country', 'term_' . $region_term->term_id);
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
  }
});

/* Хлебные крошки Yoast: регион и курорт с указанием страны */
add_filter('wpseo_breadcrumb_links', function ($links) {

  if (is_tax('region')) {
    $term = get_queried_object();

    $country_id = get_field('region_country', 'term_' . $term->term_id);
    $countries_page = get_page_by_path('strany');

    $new_links = [];
    $new_links[] = ['url' => home_url('/'), 'text' => 'Главная'];

    if ($countries_page) {
      $new_links[] = ['url' => get_permalink($countries_page->ID), 'text' => $countries_page->post_title ?: 'Страны'];
    } else {
      $new_links[] = ['url' => get_post_type_archive_link('country'), 'text' => 'Страны'];
    }

    if (!empty($country_id)) {
      $new_links[] = ['url' => get_permalink($country_id), 'text' => get_the_title($country_id)];
    }

    $new_links[] = ['text' => $term->name];

    return $new_links;
  }

  if (is_tax('resort')) {
    $term = get_queried_object();

    $region_id = get_field('resort_region', 'term_' . $term->term_id);
    $region_term = $region_id ? get_term($region_id, 'region') : null;

    if (empty($region_term) || is_wp_error($region_term)) {
      return $links;
    }

    $country_id = get_field('region_country', 'term_' . $region_term->term_id);
    $countries_page = get_page_by_path('strany');

    $new_links = [];
    $new_links[] = ['url' => home_url('/'), 'text' => 'Главная'];

    if ($countries_page) {
      $new_links[] = ['url' => get_permalink($countries_page->ID), 'text' => $countries_page->post_title ?: 'Страны'];
    } else {
      $new_links[] = ['url' => get_post_type_archive_link('country'), 'text' => 'Страны'];
    }

    if (!empty($country_id)) {
      $new_links[] = ['url' => get_permalink($country_id), 'text' => get_the_title($country_id)];
    }

    $new_links[] = ['url' => get_term_link($region_term), 'text' => $region_term->name];
    $new_links[] = ['text' => $term->name];

    return $new_links;
  }

  return $links;
});