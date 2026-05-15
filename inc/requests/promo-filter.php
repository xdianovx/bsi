<?php

add_action('wp_ajax_bsi_filter_promos', 'bsi_filter_promos');
add_action('wp_ajax_nopriv_bsi_filter_promos', 'bsi_filter_promos');

function bsi_filter_promos()
{
  $country_id = isset($_POST['country']) ? (int) $_POST['country'] : 0;
  $archived = isset($_POST['archived']) && (string) $_POST['archived'] === '1';

  $query_args = bsi_promo_list_query_args($archived, $country_id);
  $query_args['no_found_rows'] = true;

  $promos_query = new WP_Query($query_args);

  ob_start();

  if ($promos_query->have_posts()) {
    while ($promos_query->have_posts()) {
      $promos_query->the_post();
      get_template_part('template-parts/promo/card');
    }
  } else {
    $msg = $archived
      ? 'Для выбранного направления нет архивных акций.'
      : 'Для выбранного направления нет активных акций.';
    echo '<div class="promo-archive__empty"><p>' . esc_html($msg) . '</p></div>';
  }

  wp_reset_postdata();

  $html = ob_get_clean();
  echo $html;
  wp_die();
}