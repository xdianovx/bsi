<?php

add_action('wp_ajax_popular_tours_by_country', 'bsi_ajax_popular_tours_by_country');
add_action('wp_ajax_nopriv_popular_tours_by_country', 'bsi_ajax_popular_tours_by_country');

function bsi_ajax_popular_tours_by_country()
{
  $country_id = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;
  $homepage_tour_ids = function_exists('bsi_get_homepage_featured_tour_ids')
    ? bsi_get_homepage_featured_tour_ids()
    : [];

  $args = [
    'post_type' => 'tour',
    'post_status' => 'publish',
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
  ];

  if (!empty($homepage_tour_ids)) {
    $args['post__in'] = $homepage_tour_ids;
    $args['posts_per_page'] = -1;
    $args['orderby'] = 'post__in';
    if ($country_id > 0 && function_exists('bsi_build_tour_country_meta_query')) {
      $args['meta_query'] = [
        'relation' => 'AND',
        bsi_build_tour_country_meta_query((int) $country_id),
      ];
    }
  } else {
    $args['posts_per_page'] = 12;
    $args['orderby'] = ['menu_order' => 'ASC', 'date' => 'DESC'];
    $args['meta_query'] = [
      'relation' => 'AND',
      [
        'key' => 'is_popular',
        'value' => '1',
        'compare' => '=',
      ],
    ];
    if ($country_id > 0 && function_exists('bsi_build_tour_country_meta_query')) {
      $args['meta_query'][] = bsi_build_tour_country_meta_query((int) $country_id);
    }
  }

  $q = new WP_Query($args);

  ob_start();

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      $tour_id = (int) get_the_ID();
      $tour_data = function_exists('bsi_get_tour_card_query_var') ? bsi_get_tour_card_query_var($tour_id) : [];
      if (empty($tour_data)) {
        continue;
      }
      $country_id_attr = (int) ($tour_data['country_id'] ?? 0);
      $country_slug_attr = (string) ($tour_data['country_slug'] ?? '');
      echo '<div class="swiper-slide" data-country="' . esc_attr($country_id_attr) . '" data-country-slug="' . esc_attr($country_slug_attr) . '">';
      set_query_var('tour', $tour_data);
      get_template_part('template-parts/tour/card');
      echo '</div>';
    }
    wp_reset_postdata();
  }

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total' => (int) $q->found_posts,
  ]);
}

