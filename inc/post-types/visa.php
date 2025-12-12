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

  $args = [
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
    'rewrite' => false, // свои ссылки, без /visa/slug/
    'publicly_queryable' => true,
    'query_var' => true,
  ];

  register_post_type('visa', $args);
}

// синхронизируем slug визы с slug страны
add_action('save_post_visa', 'sync_visa_slug_with_country', 10, 3);

function sync_visa_slug_with_country($post_id, $post, $update)
{
  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  $country_id = get_field('visa_country', $post_id);
  if (!$country_id) {
    return;
  }

  $country_slug = get_post_field('post_name', $country_id);
  if (!$country_slug) {
    return;
  }

  if ($post->post_name === $country_slug) {
    return;
  }

  remove_action('save_post_visa', 'sync_visa_slug_with_country', 10);

  wp_update_post([
    'ID' => $post_id,
    'post_name' => $country_slug,
  ]);

  add_action('save_post_visa', 'sync_visa_slug_with_country', 10, 3);
}

// /country/{slug}/visa/ -> single визы этой страны
add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/visa/?$',
    'index.php?post_type=visa&name=$matches[1]',
    'top'
  );
});

// генерим правильную ссылку на визу
add_filter('post_type_link', 'visa_permalink', 10, 2);

function visa_permalink($post_link, $post)
{
  if ($post->post_type !== 'visa') {
    return $post_link;
  }

  $country_id = get_field('visa_country', $post->ID);
  if (!$country_id) {
    return $post_link;
  }

  $country = get_post($country_id);
  if (!$country) {
    return $post_link;
  }

  return trailingslashit(home_url('/country/' . $country->post_name . '/visa'));
}

add_filter('wpseo_breadcrumb_links', function ($links) {
  if (!is_singular('visa')) {
    return $links;
  }

  $country_id = get_field('visa_country');
  if (!$country_id) {
    return $links;
  }

  $country = get_post($country_id);
  if (!$country) {
    return $links;
  }

  $new_links = [];

  // Главная
  $new_links[] = [
    'url' => home_url('/'),
    'text' => 'Главная',
  ];

  // Страны (архив country или отдельная страница)
  $countries_archive = get_post_type_archive_link('country');
  if ($countries_archive) {
    $new_links[] = [
      'url' => $countries_archive,
      'text' => 'Страны',
    ];
  } else {
    // если у тебя есть отдельная страница "strany"
    $countries_page = get_page_by_path('strany');
    if ($countries_page) {
      $new_links[] = [
        'url' => get_permalink($countries_page->ID),
        'text' => $countries_page->post_title ?: 'Страны',
      ];
    }
  }

  // Страна
  $new_links[] = [
    'url' => get_permalink($country->ID),
    'text' => $country->post_title,
  ];

  // Текущая виза (заголовок поста)
  $new_links[] = [
    'text' => get_the_title(),
  ];

  return $new_links;
});