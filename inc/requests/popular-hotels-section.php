<?php

add_action('wp_ajax_popular_hotels_by_country', 'bsi_ajax_popular_hotels_by_country');
add_action('wp_ajax_nopriv_popular_hotels_by_country', 'bsi_ajax_popular_hotels_by_country');

function bsi_ajax_popular_hotels_by_country()
{
  $country_id = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;
  $args = [
    'post_type' => 'hotel',
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
      'key' => 'hotel_country',
      'value' => $country_id,
      'compare' => '=',
    ];
  }

  $q = new WP_Query($args);

  ob_start();

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();

      $hotel_id = get_the_ID();
      $country = function_exists('get_field') ? get_field('hotel_country', $hotel_id) : 0;
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

      $country_title = $country ? get_the_title($country) : '';

      $region_name = '';
      $regions = get_the_terms($hotel_id, 'region');
      if (!empty($regions) && !is_wp_error($regions)) {
        $region_name = $regions[0]->name;
      }

      $resort_name = '';
      $resorts = get_the_terms($hotel_id, 'resort');
      if (!empty($resorts) && !is_wp_error($resorts)) {
        $resort_name = $resorts[0]->name;
      }

      $price_value = '';
      $price_text = '';
      if (function_exists('get_field')) {
        $price = get_field('price', $hotel_id);
        if ($price) {
          if (is_numeric($price)) {
            $price_value = number_format((float) $price, 0, '.', ' ') . ' ₽';
          } elseif (is_string($price)) {
            $price_value = $price;
          }
        }

        $price_text_val = get_field('price_text', $hotel_id);
        if ($price_text_val) {
          $price_text = (string) $price_text_val;
        }
      }

      $item = [
        'id' => $hotel_id,
        'url' => get_permalink($hotel_id),
        'image' => (string) get_the_post_thumbnail_url($hotel_id, 'large'),
        'type' => 'Отель',
        'tags' => [],
        'title' => get_the_title($hotel_id),
        'flag' => $flag_url ? esc_url($flag_url) : '',
        'country_title' => $country_title,
        'region_title' => $region_name,
        'resort_title' => $resort_name,
        'price' => $price_value,
        'price_text' => $price_text,
      ];

      echo '<div class="swiper-slide">';
      set_query_var('hotel', $item);
      get_template_part('template-parts/hotels/card');
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