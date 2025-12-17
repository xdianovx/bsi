<?php


add_action('wp_ajax_bsi_resort_hotels', 'bsi_resort_hotels_ajax');
add_action('wp_ajax_nopriv_bsi_resort_hotels', 'bsi_resort_hotels_ajax');

function bsi_resort_hotels_ajax()
{
  $term_id = isset($_POST['term_id']) ? (int) $_POST['term_id'] : 0;
  $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
  $per_page = isset($_POST['per_page']) ? max(1, (int) $_POST['per_page']) : 12;

  $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'title';
  $order = isset($_POST['order']) ? strtoupper(sanitize_text_field($_POST['order'])) : 'ASC';
  $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'ASC';

  if (!$term_id) {
    wp_send_json_error(['message' => 'term_id is required']);
  }

  // базовая сортировка
  $query_args = [
    'post_type' => 'hotel',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $page,
    'meta_query' => [
      [
        'key' => 'hotel_resort',
        'value' => $term_id,
        'compare' => '=',
      ],
    ],
  ];

  // orderby: title|date|rating
  if ($orderby === 'date') {
    $query_args['orderby'] = 'date';
    $query_args['order'] = $order;
  } elseif ($orderby === 'rating') {
    $query_args['orderby'] = 'meta_value_num';
    $query_args['meta_key'] = 'rating';
    $query_args['order'] = $order;
  } else {
    $query_args['orderby'] = 'title';
    $query_args['order'] = $order;
  }

  $q = new WP_Query($query_args);

  ob_start();

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();

      get_template_part('template-parts/hotels/card-row', null, [
        'hotel_id' => get_the_ID(),
      ]);
    }
  }

  wp_reset_postdata();

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'has_more' => ($page < (int) $q->max_num_pages),
  ]);
}