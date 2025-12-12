<?php
add_action('init', 'register_post_types_event');

function register_post_types_event()
{
  register_post_type('event', [
    'label' => null,
    'labels' => [
      'name' => 'События',
      'singular_name' => 'Событие',
      'add_new' => 'Добавить событие',
      'add_new_item' => 'Новое событие',
      'edit_item' => 'Редактирование события',
      'new_item' => 'Новое ____',
      'view_item' => 'Смотреть событие',
      'search_items' => 'Искать событие',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'parent_item_colon' => 'События',
      'menu_name' => 'События',
    ],
    'description' => '',
    'public' => true,
    'show_in_menu' => null,
    'show_in_rest' => null,
    'rest_base' => null,
    'menu_position' => null,
    'menu_icon' => 'dashicons-calendar-alt',
    'hierarchical' => false,
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'trackbacks', 'post-formats', 'page-attributes'], // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
    'taxonomies' => [],
    'has_archive' => true,
    'rewrite' => true,
    'query_var' => true,
  ]);

}


