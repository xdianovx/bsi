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

/* Колонки «Превью», «Год» и «Описание» в списке наград в админке. */
add_filter('manage_award_posts_columns', function ($columns) {
  $new = [];
  foreach ($columns as $key => $value) {
    if ($key === 'title') {
      $new['award_thumb_col'] = 'Превью';
    }
    $new[$key] = $value;
    if ($key === 'title') {
      $new['award_year_col'] = 'Год';
      $new['award_desc_col'] = 'Описание';
    }
  }
  return $new;
});

add_action('manage_award_posts_custom_column', function ($column, $post_id) {
  if ($column === 'award_thumb_col') {
    echo get_the_post_thumbnail((int) $post_id, [60, 60], ['style' => 'width:60px;height:60px;object-fit:contain;']) ?: '—';
    return;
  }
  if ($column === 'award_desc_col') {
    $desc = wp_strip_all_tags((string) get_post_meta((int) $post_id, 'award_description', true));
    echo $desc !== '' ? esc_html(wp_trim_words($desc, 20)) : '—';
    return;
  }
  if ($column !== 'award_year_col') {
    return;
  }
  $year = get_post_meta((int) $post_id, 'award_year', true);
  echo $year !== '' ? esc_html($year) : '—';
}, 10, 2);

add_filter('manage_edit-award_sortable_columns', function ($columns) {
  $columns['award_year_col'] = 'award_year';
  return $columns;
});

/* Сортировка по году: награды без года не пропадают из списка, уходят в конец. */
add_action('pre_get_posts', function ($query) {
  if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'award' || $query->get('orderby') !== 'award_year') {
    return;
  }
  $query->set('meta_query', [
    'relation' => 'OR',
    'award_year_clause' => ['key' => 'award_year', 'compare' => 'EXISTS', 'type' => 'NUMERIC'],
    ['key' => 'award_year', 'compare' => 'NOT EXISTS'],
  ]);
  $query->set('orderby', ['award_year_clause' => $query->get('order') ?: 'DESC', 'title' => 'ASC']);
});

add_action('admin_head-edit.php', function () {
  if (get_current_screen()->post_type !== 'award') {
    return;
  }
  echo '<style>.column-award_thumb_col{width:76px;}.column-award_year_col{width:80px;}.column-award_desc_col{width:40%;}</style>';
});
