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

add_action('init', function () {

  // Тип обучения
  register_taxonomy('education_type', ['education'], [
    'labels' => [
      'name' => 'Типы обучения',
      'singular_name' => 'Тип обучения',
      'menu_name' => 'Типы обучения',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => ['slug' => 'education-type'],
  ]);

  // Тип размещения
  register_taxonomy('education_accommodation_type', ['education'], [
    'labels' => [
      'name' => 'Типы размещения',
      'singular_name' => 'Тип размещения',
      'menu_name' => 'Размещение',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => ['slug' => 'accommodation-type'],
  ]);

  // Языки
  register_taxonomy('education_language', ['education'], [
    'labels' => [
      'name' => 'Языки',
      'singular_name' => 'Язык',
      'menu_name' => 'Языки',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => ['slug' => 'education-language'],
  ]);

}, 20);