<?php
/**
 * CPT: entry_rules (Правила въезда)
 */

add_action('init', function () {

  register_post_type('entry_rules', [
    'labels' => [
      'name' => 'Правила въезда',
      'singular_name' => 'Правила въезда',
      'add_new' => 'Добавить',
      'add_new_item' => 'Новое правило',
      'edit_item' => 'Редактировать',
      'new_item' => 'Новое правило',
      'view_item' => 'Смотреть',
      'search_items' => 'Искать',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Правила въезда',
    ],

    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,

    'menu_position' => 27,
    'menu_icon' => 'dashicons-clipboard',

    'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
    'has_archive' => true,

    'rewrite' => [
      'slug' => 'entry-rules',
      'with_front' => false,
    ],
    'query_var' => true,
  ]);

}, 10);

/**
 * ACF: страна + галерея
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_entry_rules_main',
    'title' => 'Правила въезда — данные',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_entry_rules_country',
        'label' => 'Страна',
        'name' => 'entry_rules_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_entry_rules_gallery',
        'label' => 'Галерея',
        'name' => 'entry_rules_gallery',
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
          'value' => 'entry_rules',
        ]
      ],
    ],
  ]);
});

add_filter('acf/fields/post_object/query/key=field_entry_rules_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);