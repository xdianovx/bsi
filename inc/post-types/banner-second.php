<?php

add_action('init', 'register_post_type_banner_second');

function register_post_type_banner_second()
{
  $labels = [
    'name' => 'Промо баннеры 2',
    'singular_name' => 'Промо баннер 2',
    'menu_name' => 'Промо баннеры 2',
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
    'menu_position' => 23,
    'menu_icon' => 'dashicons-format-image',
    'supports' => ['title'],
    'has_archive' => false,
    'rewrite' => false,
    'publicly_queryable' => false,
    'query_var' => true,
  ];

  register_post_type('banner_second', $args);
}
