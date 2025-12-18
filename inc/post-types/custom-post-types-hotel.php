<?php

add_action('init', 'register_post_types_hotel');
function register_post_types_hotel()
{
  register_post_type('hotel', [
    'label' => null,
    'labels' => [
      'name' => 'Отели',
      'singular_name' => 'Отель',
      'add_new' => 'Добавить отель',
      'add_new_item' => 'Новый отель',
      'edit_item' => 'Редактирование отеля',
      'new_item' => 'Новый отель',
      'view_item' => 'Смотреть отель',
      'search_items' => 'Искать отель',
      'not_found' => 'Отели не найдены',
      'not_found_in_trash' => 'Отелей не найдено в корзине',
      'parent_item_colon' => 'Родительский отель',
      'menu_name' => 'Отели',
    ],
    'description' => '',
    'public' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 25,
    'menu_icon' => 'dashicons-building',
    'hierarchical' => false,
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'taxonomies' => ['city'],
    'has_archive' => true,
    'rewrite' => ['slug' => 'hotels'],
    'query_var' => true,
  ]);
}

add_action('init', 'bsi_register_hotel_taxonomies', 25);
function bsi_register_hotel_taxonomies(): void
{
  register_taxonomy('region', ['hotel'], [
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
    'meta_box_cb' => 'post_categories_meta_box',
    'rewrite' => false,
    'query_var' => true,
  ]);

  register_taxonomy('resort', ['hotel'], [
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
    'meta_box_cb' => 'post_tags_meta_box',
    'rewrite' => false,
    'query_var' => true,
  ]);

  register_taxonomy('amenity', ['hotel'], [
    'labels' => [
      'name' => 'Удобства',
      'singular_name' => 'Удобство',
      'search_items' => 'Найти удобство',
      'all_items' => 'Все удобства',
      'edit_item' => 'Редактировать удобство',
      'update_item' => 'Обновить удобство',
      'add_new_item' => 'Добавить удобство',
      'new_item_name' => 'Новое удобство',
      'menu_name' => 'Удобства',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'meta_box_cb' => 'post_tags_meta_box',
    'rewrite' => false,
    'query_var' => true,
  ]);
}

add_action('init', 'bsi_hotels_rewrite_rules');
function bsi_hotels_rewrite_rules(): void
{
  add_rewrite_rule(
    '^country/([^/]+)/hotel/?$',
    'index.php?country_hotels=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/hotel/([^/]+)/?$',
    'index.php?post_type=hotel&name=$matches[2]',
    'top'
  );
}

add_filter('query_vars', 'bsi_hotels_query_vars');
function bsi_hotels_query_vars(array $vars): array
{
  $vars[] = 'country_hotels';
  return $vars;
}

add_action('template_include', 'bsi_country_hotels_template');
function bsi_country_hotels_template(string $template): string
{
  if (get_query_var('country_hotels')) {
    $country_slug = get_query_var('country_hotels');
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      global $country_hotels_data;
      $country_hotels_data = [
        'country' => $country,
        'country_slug' => $country_slug,
      ];

      $new_template = locate_template('country-hotels.php');
      if ($new_template) {
        return $new_template;
      }
    } else {
      global $wp_query;
      $wp_query->set_404();
      status_header(404);
      return get_404_template();
    }
  }

  return $template;
}

add_filter('post_type_link', 'bsi_hotel_post_type_link', 10, 2);
function bsi_hotel_post_type_link(string $post_link, WP_Post $post): string
{
  if ($post->post_type === 'hotel' && $post->post_status === 'publish') {
    $country_id = function_exists('get_field') ? get_field('hotel_country', $post->ID) : 0;
    $country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;

    if ($country_id) {
      $country = get_post($country_id);
      if ($country) {
        $post_link = home_url("/country/{$country->post_name}/hotel/{$post->post_name}/");
      }
    }
  }

  return $post_link;
}

add_filter('wpseo_breadcrumb_links', 'bsi_breadcrumbs_country_hotels');
function bsi_breadcrumbs_country_hotels(array $links): array
{
  if (get_query_var('country_hotels')) {
    $country_slug = get_query_var('country_hotels');
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      $new_links = [];

      $new_links[] = ['url' => home_url('/'), 'text' => 'Главная'];

      $countries_archive = get_post_type_archive_link('country');
      if ($countries_archive) {
        $new_links[] = ['url' => $countries_archive, 'text' => 'Страны'];
      }

      $new_links[] = ['url' => get_permalink($country->ID), 'text' => $country->post_title];
      $new_links[] = ['text' => 'Отели'];

      return $new_links;
    }
  }

  return $links;
}

add_filter('wpseo_breadcrumb_links', 'bsi_breadcrumbs_single_hotel');
function bsi_breadcrumbs_single_hotel(array $links): array
{
  if (is_singular('hotel')) {
    $country_id = function_exists('get_field') ? get_field('hotel_country') : 0;
    $country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;

    if ($country_id) {
      $country = get_post($country_id);

      $new_links = [];

      $new_links[] = ['url' => home_url('/'), 'text' => 'Главная'];

      $countries_archive = get_post_type_archive_link('country');
      if ($countries_archive) {
        $new_links[] = ['url' => $countries_archive, 'text' => 'Страны'];
      }

      if ($country) {
        $new_links[] = ['url' => get_permalink($country->ID), 'text' => $country->post_title];
        $new_links[] = ['url' => home_url("/country/{$country->post_name}/hotel/"), 'text' => 'Отели'];
      }

      $new_links[] = ['text' => get_the_title()];

      return $new_links;
    }
  }

  return $links;
}