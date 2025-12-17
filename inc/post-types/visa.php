<?php

/**
 * CPT: Visa
 * URL: /country/{country-slug}/visa/
 * ВАЖНО: роут ищет визу по meta visa_country, а не по slug визы.
 */

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

/**
 * (Опционально) синхронизируем slug визы со slug страны — чисто для админки/удобства.
 * На фронт-роутинг это больше НЕ влияет.
 */
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
  if (!$country_id)
    return;

  $country_slug = get_post_field('post_name', $country_id);
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

/**
 * Query var для /country/{slug}/visa/
 */
add_filter('query_vars', function ($vars) {
  $vars[] = 'country_visa';
  return $vars;
});

/**
 * Роут: /country/{country-slug}/visa/
 */
add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/visa/?$',
    'index.php?country_visa=$matches[1]',
    'top'
  );
}, 20);

/**
 * Подмена main query:
 * 1) Находим страну по slug
 * 2) Находим 1 визу по meta visa_country = ID страны
 * 3) Подставляем её как singular через p={id}
 */
add_action('pre_get_posts', function ($q) {
  if (is_admin() || !$q->is_main_query())
    return;

  $country_slug = (string) get_query_var('country_visa');
  if (!$country_slug)
    return;

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    $q->set_404();
    status_header(404);
    return;
  }

  // Ищем визу по метаполю, НЕ по slug
  $visa_ids = get_posts([
    'post_type' => 'visa',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => [
      [
        'key' => 'visa_country',
        'value' => $country->ID,
        'compare' => '=',
      ],
    ],
  ]);

  if (empty($visa_ids)) {
    $q->set_404();
    status_header(404);
    return;
  }

  $visa_id = (int) $visa_ids[0];

  // Делаем нормальный single
  $q->set('post_type', 'visa');
  $q->set('p', $visa_id);

  // Чистим мусорные vars, чтобы WP не пытался трактовать как page
  $q->set('name', '');
  $q->set('pagename', '');
  $q->set('page_id', '');

  $q->is_singular = true;
  $q->is_single = true;
  $q->is_page = false;
  $q->is_home = false;

}, 0);

/**
 * Генерим правильную ссылку на визу (везде на сайте)
 */
add_filter('post_type_link', 'visa_permalink', 10, 2);
function visa_permalink($post_link, $post)
{
  if ($post->post_type !== 'visa')
    return $post_link;

  if (!function_exists('get_field'))
    return $post_link;

  $country_id = get_field('visa_country', $post->ID);
  if (!$country_id)
    return $post_link;

  $country = get_post($country_id);
  if (!$country)
    return $post_link;

  return trailingslashit(home_url('/country/' . $country->post_name . '/visa'));
}

/**
 * Хлебные крошки Yoast для single visa
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
  if (!is_singular('visa'))
    return $links;
  if (!function_exists('get_field'))
    return $links;

  $country_id = get_field('visa_country');
  if (!$country_id)
    return $links;

  $country = get_post($country_id);
  if (!$country)
    return $links;

  $new_links = [];

  $new_links[] = ['url' => home_url('/'), 'text' => 'Главная'];

  $countries_page = get_page_by_path('strany');
  if ($countries_page) {
    $new_links[] = [
      'url' => get_permalink($countries_page->ID),
      'text' => $countries_page->post_title ?: 'Страны',
    ];
  } else {
    $new_links[] = [
      'url' => get_post_type_archive_link('country'),
      'text' => 'Страны',
    ];
  }

  $new_links[] = [
    'url' => get_permalink($country->ID),
    'text' => $country->post_title,
  ];

  $new_links[] = ['text' => get_the_title()];

  return $new_links;
});