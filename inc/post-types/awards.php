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
    'public' => false,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 21,
    'menu_icon' => 'dashicons-awards',
    'supports' => ['title', 'thumbnail'],
    'has_archive' => false,
    'publicly_queryable' => false,
    'query_var' => false,
    'exclude_from_search' => true,
  ];

  register_post_type('award', $args);
}