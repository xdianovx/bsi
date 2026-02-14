<?php

add_action('init', 'register_post_type_visa');
function register_post_type_visa()
{
  $labels = [
    'name' => 'Визы',
    'singular_name' => 'Виза',
    'menu_name' => 'Визы',
    'add_new' => 'Добавить визу',
    'add_new_item' => 'Добавить визу',
    'edit_item' => 'Редактировать визу',
    'new_item' => 'Новая виза',
    'view_item' => 'Просмотр визы',
    'search_items' => 'Искать визы',
    'not_found' => 'Визы не найдены',
    'not_found_in_trash' => 'В корзине виз нет',
    'all_items' => 'Все визы',
  ];

  register_post_type('visa', [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 22,
    'menu_icon' => 'dashicons-flag',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'has_archive' => false,
    'rewrite' => false,
    'publicly_queryable' => true,
    'query_var' => true,
  ]);
}

add_filter('query_vars', function ($vars) {
  $vars[] = 'visa_type_slug';
  return $vars;
});

add_action('init', function () {
  register_taxonomy('visa_type', ['visa'], [
    'labels' => [
      'name' => 'Типы виз',
      'singular_name' => 'Тип визы',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип',
      'new_item_name' => 'Новый тип',
      'menu_name' => 'Типы виз',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => false,
    'query_var' => false,
  ]);
}, 20);

add_action('init', function () {
  if (taxonomy_exists('visa_type')) {
    register_taxonomy_for_object_type('visa_type', 'visa');
  }
}, 999);

add_action('save_post_visa', 'sync_visa_slug_with_country', 10, 3);
add_action('save_post_visa', 'sync_visa_slug_with_country', 10, 3);
function sync_visa_slug_with_country($post_id, $post, $update)
{
  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id))
    return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    return;
  if (!function_exists('get_field'))
    return;

  $country_id = get_field('visa_country', $post_id);

  if ($country_id instanceof WP_Post) {
    $country_id = (int) $country_id->ID;
  } elseif (is_array($country_id)) {
    $country_id = (int) reset($country_id);
  } else {
    $country_id = (int) $country_id;
  }

  if (!$country_id)
    return;

  $country_slug = (string) get_post_field('post_name', $country_id);
  if (!$country_slug)
    return;

  if ($post->post_name === $country_slug)
    return;

  remove_action('save_post_visa', 'sync_visa_slug_with_country', 10);

  wp_update_post([
    'ID' => $post_id,
    'post_name' => $country_slug,
  ]);

  add_action('save_post_visa', 'sync_visa_slug_with_country', 10, 3);
}


add_filter('post_type_link', function ($post_link, $post) {
  if ($post->post_type !== 'visa')
    return $post_link;
  if (!function_exists('get_field'))
    return $post_link;

  $country_id = get_field('visa_country', $post->ID);

  if ($country_id instanceof WP_Post) {
    $country_id = (int) $country_id->ID;
  } elseif (is_array($country_id)) {
    $country_id = (int) reset($country_id);
  } else {
    $country_id = (int) $country_id;
  }

  if (!$country_id)
    return $post_link;

  $country_slug = (string) get_post_field('post_name', $country_id);
  if (!$country_slug)
    return $post_link;

  return trailingslashit(home_url('/country/' . $country_slug . '/visa'));
}, 10, 2);

/**
 * Yoast breadcrumbs для виз:
 * - single visa: Главная > Страны > {Страна} > {Название визы}
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
  if (is_singular('visa')) {
    $visa_id = get_queried_object_id();
    if (!$visa_id) {
      return $links;
    }

    $country_id = function_exists('get_field') ? get_field('visa_country', $visa_id) : 0;
    
    if ($country_id instanceof WP_Post) {
      $country_id = (int) $country_id->ID;
    } elseif (is_array($country_id)) {
      $country_id = (int) reset($country_id);
    } else {
      $country_id = (int) $country_id;
    }

    if (!$country_id) {
      return $links;
    }

    $country_slug = get_post_field('post_name', $country_id);
    $country_title = get_the_title($country_id);
    if (!$country_slug || !$country_title) {
      return $links;
    }

    $countries_page = get_page_by_path('strany');
    $countries_url = $countries_page ? get_permalink($countries_page->ID) : get_post_type_archive_link('country');

    $new = [];
    $new[] = ['url' => home_url('/'), 'text' => 'Главная'];

    if ($countries_url) {
      $new[] = ['url' => $countries_url, 'text' => $countries_page ? ($countries_page->post_title ?: 'Страны') : 'Страны'];
    }

    $new[] = ['url' => get_permalink($country_id), 'text' => $country_title];
    $new[] = ['text' => get_the_title($visa_id)];

    return $new;
  }

  return $links;
});