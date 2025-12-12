<?php

add_action('init', 'register_post_type_banner');

function register_post_type_banner()
{
  $labels = [
    'name' => 'Баннеры',
    'singular_name' => 'Баннер',
    'menu_name' => 'Баннеры',
    'add_new' => 'Добавить баннер',
    'add_new_item' => 'Добавить баннер',
    'edit_item' => 'Редактировать баннер',
    'new_item' => 'Новый баннер',
    'view_item' => 'Просмотреть баннер',
    'search_items' => 'Искать баннеры',
    'not_found' => 'Баннеры не найдены',
    'not_found_in_trash' => 'В корзине баннеров нет',
    'all_items' => 'Все баннеры',
  ];

  $args = [
    'labels' => $labels,
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => 'sections',
    'show_in_rest' => false,
    'menu_position' => 22,
    'menu_icon' => 'dashicons-format-image',
    'supports' => ['title'],
    'has_archive' => false,
    'rewrite' => false,
    'publicly_queryable' => false,
    'query_var' => true,
  ];

  register_post_type('banner', $args);
}