<?php
/**
 * Register Education Post Type
 */

add_action('init', 'bsi_register_education_post_type');

function bsi_register_education_post_type()
{
  $labels = [
    'name' => 'Обучение',
    'singular_name' => 'Обучение',
    'add_new' => 'Добавить обучение',
    'add_new_item' => 'Добавить обучение',
    'edit_item' => 'Редактировать обучение',
    'new_item' => 'Новое обучение',
    'all_items' => 'Все обучения',
    'view_item' => 'Просмотреть обучение',
    'search_items' => 'Найти обучение',
    'not_found' => 'Обучения не найдены',
    'not_found_in_trash' => 'В корзине обучений не найдено',
    'menu_name' => 'Обучение',
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'rewrite' => ['slug' => 'education'], // slug в URL
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'menu_position' => 5,
    'menu_icon' => 'dashicons-welcome-learn-more', // иконка в меню
    'show_in_rest' => true, // поддержка Gutenberg/API
  ];

  register_post_type('education', $args);
}