<?php

add_action('init', function () {
  register_post_type('tour', [
    'labels' => [
      'name' => 'Туры',
      'singular_name' => 'Тур',
      'menu_name' => 'Туры',
      'add_new' => 'Добавить тур',
      'add_new_item' => 'Добавить тур',
      'edit_item' => 'Редактировать тур',
      'new_item' => 'Новый тур',
      'view_item' => 'Просмотр тура',
      'search_items' => 'Искать туры',
      'not_found' => 'Туры не найдены',
      'not_found_in_trash' => 'В корзине туров нет',
      'all_items' => 'Все туры',
    ],
    'public' => true,
    'show_ui' => true,
    'show_in_rest' => true,
    'menu_position' => 24,
    'menu_icon' => 'dashicons-palmtree',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'has_archive' => 'tury',
    'rewrite' => [
      'slug' => 'tury',
      'with_front' => false,
    ],
  ]);
});

add_action('init', function () {
  register_taxonomy_for_object_type('region', 'tour');
  register_taxonomy_for_object_type('resort', 'tour');
}, 50);