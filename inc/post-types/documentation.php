<?php
// === CPT: Agent section items (formerly documentation) ===
add_action('init', 'bsi_register_documentation_cpt');
function bsi_register_documentation_cpt()
{
  $labels = [
    'name' => 'Агентствам',
    'singular_name' => 'Материал агентствам',
    'menu_name' => 'Агентствам',
    'name_admin_bar' => 'Материал агентствам',
    'add_new' => 'Добавить материал',
    'add_new_item' => 'Добавить материал',
    'edit_item' => 'Редактировать материал',
    'new_item' => 'Новый материал',
    'view_item' => 'Посмотреть материал',
    'search_items' => 'Поиск материалов',
    'not_found' => 'Материалы не найдены',
    'not_found_in_trash' => 'В корзине нет материалов',
    'parent_item_colon' => 'Родительский материал:',
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_position' => 18,
    'menu_icon' => 'dashicons-groups',
    'supports' => [
      'title',
      'editor',
      'thumbnail',
      'page-attributes',
      'revisions'
    ],
    'rewrite' => [
      'slug' => 'agentstvam',
      'with_front' => false,
      'hierarchical' => true,
    ],
    'has_archive' => true,
    'publicly_queryable' => true,
    'show_in_nav_menus' => true,
    'show_in_rest' => true,
  ];

  register_post_type('documentation', $args);
}

add_action('init', 'bsi_register_agency_item_type_taxonomy', 15);
function bsi_register_agency_item_type_taxonomy()
{
  $labels = [
    'name' => 'Типы материалов',
    'singular_name' => 'Тип материала',
    'search_items' => 'Найти тип',
    'all_items' => 'Все типы',
    'edit_item' => 'Редактировать тип',
    'update_item' => 'Обновить тип',
    'add_new_item' => 'Добавить тип',
    'new_item_name' => 'Название типа',
    'menu_name' => 'Тип материала',
  ];

  register_taxonomy('agency_item_type', ['documentation'], [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => [
      'slug' => 'agency-item-type',
      'with_front' => false,
    ],
  ]);
}

add_action('init', 'bsi_ensure_agency_document_term', 25);
function bsi_ensure_agency_document_term()
{
  if (!taxonomy_exists('agency_item_type')) {
    return;
  }

  if (!term_exists('document', 'agency_item_type')) {
    wp_insert_term('Документ', 'agency_item_type', ['slug' => 'document']);
  }
}

add_filter('wpseo_breadcrumb_links', 'bsi_agency_documentation_breadcrumbs', 999);
function bsi_agency_documentation_breadcrumbs($links)
{
  if (!is_singular('documentation')) {
    return $links;
  }

  $post_id = get_queried_object_id();
  if (!$post_id) {
    return $links;
  }

  $agency_page = get_page_by_path('turagenstvam');
  if (!$agency_page) {
    $agency_page = get_page_by_path('agentstvam');
  }
  $agency_url = $agency_page ? get_permalink($agency_page->ID) : home_url('/agentstvam/');

  return [
    [
      'url' => home_url('/'),
      'text' => 'Главная',
    ],
    [
      'url' => $agency_url,
      'text' => 'Агентствам',
    ],
    [
      'text' => get_the_title($post_id),
    ],
  ];
}