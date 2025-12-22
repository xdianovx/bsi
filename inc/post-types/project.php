<?php

/**
 * CPT: Project (Проекты)
 * Архив: /proekty/
 * Одна запись: /proekty/{slug}/
 */

add_action('init', 'bsi_register_project_cpt');
function bsi_register_project_cpt(): void
{
  $labels = [
    'name' => 'Проекты',
    'singular_name' => 'Проект',
    'menu_name' => 'Проекты',
    'add_new' => 'Добавить проект',
    'add_new_item' => 'Добавить проект',
    'edit_item' => 'Редактировать проект',
    'new_item' => 'Новый проект',
    'view_item' => 'Просмотр проекта',
    'search_items' => 'Искать проекты',
    'not_found' => 'Проекты не найдены',
    'not_found_in_trash' => 'В корзине проектов нет',
    'all_items' => 'Все проекты',
  ];

  register_post_type('project', [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 23,
    'menu_icon' => 'dashicons-portfolio',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'has_archive' => 'proekty',
    'rewrite' => [
      'slug' => 'proekty',
      'with_front' => false,
    ],
    'publicly_queryable' => true,
    'query_var' => true,
  ]);
}

/**
 * ACF поля для Project:
 * - project_country (Страна)
 * - project_gallery (Галерея)
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_project_fields',
    'title' => 'Проект',
    'fields' => [
      [
        'key' => 'field_project_country',
        'label' => 'Страна',
        'name' => 'project_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_project_gallery',
        'label' => 'Галерея',
        'name' => 'project_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'thumbnail',
        'library' => 'all',
        'min' => 0,
        'max' => 0,
        'insert' => 'append',
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'project',
        ],
      ],
    ],
  ]);
});