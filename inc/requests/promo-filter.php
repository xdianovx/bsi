<?php

add_action('wp_ajax_bsi_filter_promos', 'bsi_filter_promos');
add_action('wp_ajax_nopriv_bsi_filter_promos', 'bsi_filter_promos');

function bsi_filter_promos()
{
  $country_id = isset($_POST['country']) ? (int) $_POST['country'] : 0;

  $today = date('Ymd');

  $active_meta = [
    'relation' => 'OR',
    ['key' => 'promo_date_to', 'compare' => 'NOT EXISTS'],
    ['key' => 'promo_date_to', 'value' => '', 'compare' => '='],
    ['key' => 'promo_date_to', 'value' => $today, 'compare' => '>='],
  ];

  $query_args = [
    'post_type' => 'promo',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => $active_meta,
  ];

  if ($country_id) {
    $query_args['meta_query'] = [
      'relation' => 'AND',
      $active_meta,
      [
        'key' => 'promo_countries',
        'value' => '"' . $country_id . '"',
        'compare' => 'LIKE',
      ],
    ];
  }

  $promos_query = new WP_Query($query_args);

  ob_start();

  if ($promos_query->have_posts()) {
    while ($promos_query->have_posts()) {
      $promos_query->the_post();
      get_template_part('template-parts/promo/card');
    }
  } else {
    echo '<div class="promo-archive__empty"><p>Для выбранного направления нет активных акций.</p></div>';
  }

  wp_reset_postdata();

  $html = ob_get_clean();
  echo $html;
  wp_die();
}