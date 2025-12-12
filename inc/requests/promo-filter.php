<?php

add_action('wp_ajax_bsi_filter_promos', 'bsi_filter_promos');
add_action('wp_ajax_nopriv_bsi_filter_promos', 'bsi_filter_promos');

function bsi_filter_promos()
{
  $country_id = isset($_POST['country']) ? (int) $_POST['country'] : 0;

  $query_args = [
    'post_type' => 'promo',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
  ];

  if ($country_id) {
    $query_args['meta_query'] = [
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