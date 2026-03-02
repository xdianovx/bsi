<?php
add_action('init', 'register_post_types_news');

function register_post_types_news()
{
  register_post_type('news', [
    'label' => null,
    'labels' => [
      'name' => 'Новости',
      'singular_name' => 'Новость',
      'add_new' => 'Добавить новость',
      'add_new_item' => 'Новая новость',
      'edit_item' => 'Редактирование новости',
      'new_item' => 'Новая новость',
      'view_item' => 'Смотреть новость',
      'search_items' => 'Искать новость',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'parent_item_colon' => 'Новость',
      'menu_name' => 'Новости',
    ],
    'description' => '',
    'public' => true,
    'show_in_menu' => null,
    'show_in_rest' => null,
    'rest_base' => null,
    'menu_position' => null,
    'menu_icon' => 'dashicons-media-text',
    'hierarchical' => false,
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'trackbacks', 'post-formats', 'page-attributes'],
    'taxonomies' => [],
    'has_archive' => false,
    'rewrite' => [
      'slug' => 'news',
      'with_front' => false,
    ],
    'publicly_queryable' => true,
    'query_var' => true,
  ]);
}

add_action('init', 'bsi_register_news_type_taxonomy');

function bsi_register_news_type_taxonomy()
{
  register_taxonomy('news_type', ['news'], [
    'labels' => [
      'name' => 'Типы новостей',
      'singular_name' => 'Тип новости',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип новости',
      'new_item_name' => 'Новый тип новости',
      'menu_name' => 'Типы новостей',
    ],
    'show_in_nav_menus' => true,
    'public' => true,
    'hierarchical' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => [
      'slug' => 'tip-novosti',
      'with_front' => false,
    ],
  ]);
}
add_filter('wpseo_breadcrumb_links', function ($links) {
  if (!is_singular('news')) {
    return $links;
  }

  $post_id = get_queried_object_id();
  $country_ids = function_exists('get_field') ? get_field('news_countries', $post_id) : null;
  if (!empty($country_ids) && is_array($country_ids)) {
    $country_id = (int) reset($country_ids);
  } elseif (!empty($country_ids)) {
    $country_id = (int) $country_ids;
  } else {
    $country_id = 0;
  }

  $new_links = [];
  $new_links[] = ['url' => home_url('/'), 'text' => 'Главная'];

  if ($country_id) {
    $country = get_post($country_id);
    if ($country && $country->post_type === 'country') {
      $countries_page = get_page_by_path('strany');
      if ($countries_page) {
        $new_links[] = ['url' => get_permalink($countries_page->ID), 'text' => $countries_page->post_title ?: 'Страны'];
      } else {
        $new_links[] = ['url' => get_post_type_archive_link('country'), 'text' => 'Страны'];
      }
      $new_links[] = ['url' => get_permalink($country_id), 'text' => get_the_title($country_id)];
      $new_links[] = ['url' => home_url('/country/' . $country->post_name . '/novosti/'), 'text' => 'Новости'];
      $new_links[] = ['text' => get_the_title()];
      return $new_links;
    }
  }

  $news_page_id = 2223; // сюда подставь ID страницы "Новости"
  $news_page = get_post($news_page_id);

  if (!$news_page) {
    return $links;
  }

  $new_links[] = [
    'url' => get_permalink($news_page->ID),
    'text' => get_the_title($news_page->ID),
  ];
  $new_links[] = [
    'text' => get_the_title(),
  ];

  return $new_links;
}, 20);