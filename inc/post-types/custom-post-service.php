<?php
add_action('init', 'bsi_register_services_post_type');

function bsi_register_services_post_type()
{
  $labels = [
    'name' => 'Услуги',
    'singular_name' => 'Услуга',
    'add_new' => 'Добавить услугу',
    'add_new_item' => 'Добавить новую услугу',
    'edit_item' => 'Редактировать услугу',
    'new_item' => 'Новая услуга',
    'all_items' => 'Все услуги',
    'view_item' => 'Просмотреть услугу',
    'search_items' => 'Найти услугу',
    'not_found' => 'Услуги не найдены',
    'not_found_in_trash' => 'В корзине услуг не найдено',
    'menu_name' => 'Услуги'
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,               // если нужен архивный список
    'rewrite' => ['slug' => 'service'],
    'supports' => ['title', 'editor', 'thumbnail'],
    'menu_position' => 5,
    'menu_icon' => 'dashicons-admin-generic', // иконка в админке
    'show_in_rest' => true                // поддержка Gutenberg/API
  ];

  register_post_type('service', $args);
}

