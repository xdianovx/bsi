<?php

add_action('init', function () {
  register_post_type('offer_collection', [
    'labels' => [
      'name' => 'Лучшие предложения',
      'singular_name' => 'Подборка',
      'menu_name' => 'Лучшие предложения',
      'add_new' => 'Добавить подборку',
      'add_new_item' => 'Добавить подборку',
      'edit_item' => 'Редактировать подборку',
      'new_item' => 'Новая подборка',
      'view_item' => 'Просмотр подборки',
      'search_items' => 'Искать подборки',
      'not_found' => 'Подборки не найдены',
      'not_found_in_trash' => 'В корзине подборок нет',
      'all_items' => 'Все подборки',
    ],
    'public' => true,
    'show_ui' => true,
    'show_in_rest' => true,
    'menu_position' => 23,
    'menu_icon' => 'dashicons-star-filled',
    'supports' => ['title', 'thumbnail', 'excerpt'],
    'has_archive' => false,
  ]);
});


add_action('init', function () {
  register_taxonomy('offer_badge', ['offer_collection'], [
    'labels' => [
      'name' => 'Бейджи',
      'singular_name' => 'Бейдж',
      'search_items' => 'Найти бейдж',
      'all_items' => 'Все бейджи',
      'edit_item' => 'Редактировать бейдж',
      'update_item' => 'Обновить бейдж',
      'add_new_item' => 'Добавить бейдж',
      'new_item_name' => 'Новый бейдж',
      'menu_name' => 'Бейджи',
    ],
    'public' => false,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
  ]);
});