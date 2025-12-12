<?php
add_action('init', 'register_post_types_partner');

function register_post_types_partner()
{
  register_post_type('partner', [
    'label' => null,
    'labels' => [
      'name' => 'Партнеры', // основное название для типа записи
      'singular_name' => 'Партнер', // название для одной записи этого типа
      'add_new' => 'Добавить партнера', // для добавления новой записи
      'add_new_item' => 'Новый парнер', // заголовка у вновь создаваемой записи в админ-панели.
      'edit_item' => 'Редактирование парнера', // для редактирования типа записи
      'new_item' => 'Новое ____', // текст новой записи
      'view_item' => 'Смотреть парнера', // для просмотра записи этого типа.
      'search_items' => 'Искать парнетра', // для поиска по этим типам записи
      'not_found' => 'Не найдено', // если в результате поиска ничего не было найдено
      'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
      'parent_item_colon' => 'Партнер', // для родителей (у древовидных типов)
      'menu_name' => 'Партнеры', // название меню
    ],
    'description' => '',
    'public' => true,
    // 'publicly_queryable'  => null, // зависит от public
    // 'exclude_from_search' => null, // зависит от public
    // 'show_ui'             => null, // зависит от public
    // 'show_in_nav_menus'   => null, // зависит от public
    'show_in_menu' => null, // показывать ли в меню админки
    // 'show_in_admin_bar'   => null, // зависит от show_in_menu
    'show_in_rest' => null, // добавить в REST API. C WP 4.7
    'rest_base' => null, // $post_type. C WP 4.7
    'menu_position' => null,
    'menu_icon' => 'dashicons-businessman',

    //'capability_type'   => 'post',
    //'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
    //'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
    'hierarchical' => false,
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'trackbacks', 'post-formats', 'page-attributes'], // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
    'taxonomies' => [],
    'has_archive' => false,
    'rewrite' => true,
    'query_var' => true,
  ]);

}


