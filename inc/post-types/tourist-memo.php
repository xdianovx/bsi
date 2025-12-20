<?php
/**
 * CPT: tourist_memo (Памятка туристам)
 */

add_action('init', function () {

  register_post_type('tourist_memo', [
    'labels' => [
      'name' => 'Памятки туристам',
      'singular_name' => 'Памятка туристам',
      'add_new' => 'Добавить',
      'add_new_item' => 'Новая памятка',
      'edit_item' => 'Редактировать',
      'new_item' => 'Новая памятка',
      'view_item' => 'Смотреть',
      'search_items' => 'Искать',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Памятки туристам',
    ],

    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,

    'menu_position' => 26,
    'menu_icon' => 'dashicons-welcome-learn-more',

    'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
    'has_archive' => true,

    'rewrite' => [
      'slug' => 'tourist-memo',
      'with_front' => false,
    ],
    'query_var' => true,
  ]);

}, 10);

/**
 * ACF: страна + галерея (как у вас)
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_tourist_memo_main',
    'title' => 'Памятка — данные',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_tourist_memo_country',
        'label' => 'Страна',
        'name' => 'tourist_memo_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_tourist_memo_gallery',
        'label' => 'Галерея',
        'name' => 'tourist_memo_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 30,
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'tourist_memo',
        ]
      ],
    ],
  ]);
});

/**
 * Ограничить страны верхним уровнем
 */
add_filter('acf/fields/post_object/query/key=field_tourist_memo_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);