<?php
// === CPT: Documentation ===
add_action('init', 'bsi_register_documentation_cpt');
function bsi_register_documentation_cpt()
{

  $labels = [
    'name' => 'Документация',
    'singular_name' => 'Документ',
    'menu_name' => 'Документация',
    'name_admin_bar' => 'Документ',
    'add_new' => 'Добавить документ',
    'add_new_item' => 'Добавить новый документ',
    'edit_item' => 'Редактировать документ',
    'new_item' => 'Новый документ',
    'view_item' => 'Посмотреть документ',
    'search_items' => 'Поиск документов',
    'not_found' => 'Документы не найдены',
    'not_found_in_trash' => 'В корзине нет документов',
    'parent_item_colon' => 'Родительский документ:',
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => true, // ВАЖНО — позволяет делать структуру как у страниц
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_position' => 18,
    'menu_icon' => 'dashicons-media-document',
    'supports' => [
      'title',
      'editor',
      'thumbnail',
      'page-attributes', // порядок + вложенность
      'revisions'
    ],
    'rewrite' => [
      'slug' => 'documentation',
      'with_front' => false,
      'hierarchical' => true,
    ],
    'has_archive' => true,
    'publicly_queryable' => true,
    'show_in_rest' => true,
  ];

  register_post_type('documentation', $args);
}