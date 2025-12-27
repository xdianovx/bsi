<?php

add_action('wp_ajax_popular_tours_by_country', 'bsi_ajax_popular_tours_by_country');
add_action('wp_ajax_nopriv_popular_tours_by_country', 'bsi_ajax_popular_tours_by_country');

function bsi_ajax_popular_tours_by_country()
{
  $country_id = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;
  $args = [
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => [
      [
        'key' => 'is_popular',
        'value' => '1',
        'compare' => '=',
      ],
    ],
  ];

  if ($country_id > 0) {
    $args['meta_query'][] = [
      'key' => 'tour_country',
      'value' => $country_id,
      'compare' => '=',
    ];
  }

  $q = new WP_Query($args);

  ob_start();

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();

      $tour_id = get_the_ID();
      $country = function_exists('get_field') ? get_field('tour_country', $tour_id) : 0;
      if ($country instanceof WP_Post)
        $country = (int) $country->ID;
      $country = (int) $country;

      $flag_url = '';
      if ($country && function_exists('get_field')) {
        $flag = get_field('flag', $country);
        if (is_array($flag) && !empty($flag['url']))
          $flag_url = $flag['url'];
        elseif (is_string($flag))
          $flag_url = $flag;
      }

      $region_name = '';
      $regions = get_the_terms($tour_id, 'region');
      if (!empty($regions) && !is_wp_error($regions)) {
        $region_name = $regions[0]->name;
      }

      $location_title = '';
      if ($country) {
        $location_title = get_the_title($country);
        if ($region_name)
          $location_title .= ', ' . $region_name;
      }

      $price_text = '';
      if (function_exists('get_field')) {
        $price = get_field('price_from', $tour_id);
        if ($price) {
          if (is_numeric($price)) {
            $price_text = 'от ' . number_format((float) $price, 0, '.', ' ') . ' руб';
          } elseif (is_string($price) && $price !== '') {
            $price_text = $price;
          }
        }
      }

      $duration = '';
      if (function_exists('get_field')) {
        $duration_val = get_field('tour_duration', $tour_id);
        if (is_string($duration_val) && $duration_val !== '') {
          $duration = $duration_val;
        }
      }

      $tour_types = [];
      $type_terms = get_the_terms($tour_id, 'tour_type');
      if (!empty($type_terms) && !is_wp_error($type_terms)) {
        $tour_types = array_map(function($term) {
          return $term->name;
        }, $type_terms);
      }

      $item = [
        'id' => $tour_id,
        'url' => get_permalink($tour_id),
        'image' => (string) get_the_post_thumbnail_url($tour_id, 'large'),
        'type' => 'Тур',
        'tags' => $tour_types,
        'title' => get_the_title($tour_id),
        'flag' => $flag_url ? esc_url($flag_url) : '',
        'location_title' => $location_title,
        'price' => $price_text,
        'duration' => $duration,
        'country_id' => $country,
        'country_slug' => $country ? get_post_field('post_name', $country) : '',
      ];

      echo '<div class="swiper-slide">';
      set_query_var('tour', $item);
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

