<?php

add_action('init', 'bsi_register_award_cpt');

function bsi_register_award_cpt()
{
  $labels = [
    'name' => 'Награды',
    'singular_name' => 'Награда',
    'menu_name' => 'Награды',
    'add_new' => 'Добавить награду',
    'add_new_item' => 'Добавить награду',
    'edit_item' => 'Редактировать награду',
    'new_item' => 'Новая награда',
    'view_item' => 'Просмотр награды',
    'search_items' => 'Искать награды',
    'not_found' => 'Награды не найдены',
    'not_found_in_trash' => 'В корзине наград нет',
    'all_items' => 'Все награды',
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 21,
    'menu_icon' => 'dashicons-awards',
    'supports' => ['title', 'thumbnail', 'editor', 'excerpt'],
    'rewrite' => [
      'slug' => 'nagrady',
      'with_front' => false,
    ],
    'has_archive' => false,
    'publicly_queryable' => true,
    'query_var' => true,
  ];

  register_post_type('award', $args);
}


add_filter('wpseo_breadcrumb_links', function ($links) {
  if (is_singular('award')) {
    $awards_page = get_page_by_path('nagrady');

    if ($awards_page) {
      $new_links = [];
      $new_links[] = [
        'url' => home_url('/'),
        'text' => 'Главная',
      ];
      $new_links[] = [
        'url' => get_permalink($awards_page->ID),
        'text' => $awards_page->post_title,
      ];
      $new_links[] = [
        'text' => get_the_title(),
      ];

      return $new_links;
    }
  }

  return $links;
});