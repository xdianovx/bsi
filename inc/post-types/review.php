<?php
// === CPT: Reviews (Отзывы) ===
add_action('init', 'bsi_register_review_cpt');
function bsi_register_review_cpt()
{

  $labels = [
    'name' => 'Отзывы',
    'singular_name' => 'Отзыв',
    'menu_name' => 'Отзывы',
    'name_admin_bar' => 'Отзыв',
    'add_new' => 'Добавить отзыв',
    'add_new_item' => 'Добавить новый отзыв',
    'edit_item' => 'Редактировать отзыв',
    'new_item' => 'Новый отзыв',
    'view_item' => 'Посмотреть отзыв',
    'search_items' => 'Поиск отзывов',
    'not_found' => 'Отзывы не найдены',
    'not_found_in_trash' => 'В корзине отзывов нет',
    'all_items' => 'Все отзывы',
    'parent_item_colon' => 'Родительский отзыв:',
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => 'sections',
    'menu_position' => 19,
    'menu_icon' => 'dashicons-testimonial',
    'supports' => [
      'title',
      'thumbnail',
    ],
    'rewrite' => [
      'slug' => 'otzyvy',
      'with_front' => false,
    ],
    'has_archive' => 'otzyvy',
    'publicly_queryable' => true,
    'show_in_rest' => true,
  ];

  register_post_type('review', $args);
}

add_filter('wpseo_breadcrumb_links', function ($links) {
  foreach ($links as &$link) {
    if (isset($link['ptarchive']) && $link['ptarchive'] === 'review') {
      // Жёстко указываем правильный URL
      $link['url'] = home_url('/otzyvy/');
    }
  }
  return $links;
});