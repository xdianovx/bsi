<?php

add_action('wp_ajax_popular_education_by_country', 'bsi_ajax_popular_education_by_country');
add_action('wp_ajax_nopriv_popular_education_by_country', 'bsi_ajax_popular_education_by_country');

function bsi_ajax_popular_education_by_country()
{
  $country_id = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;
  $args = [
    'post_type' => 'education',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
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
      'key' => 'education_country',
      'value' => $country_id,
      'compare' => '=',
    ];
  }

  $q = new WP_Query($args);

  // Дополнительная сортировка по menu_order для гарантии правильного порядка
  // Явно загружаем menu_order для каждого поста
  if (!empty($q->posts)) {
    foreach ($q->posts as $post) {
      if (!isset($post->menu_order)) {
        $post->menu_order = (int) get_post_field('menu_order', $post->ID);
      }
    }
    
    usort($q->posts, function ($a, $b) {
      $order_a = (int) ($a->menu_order ?? 0);
      $order_b = (int) ($b->menu_order ?? 0);
      
      if ($order_a === $order_b) {
        // Если menu_order одинаковый, сортируем по дате (новые первыми)
        return strtotime($b->post_date) - strtotime($a->post_date);
      }
      
      return $order_a <=> $order_b;
    });
  }

  ob_start();

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();

      $education_id = get_the_ID();

      $country_id_item = 0;
      if (function_exists('get_field')) {
        $country_val = get_field('education_country', $education_id);
        if ($country_val instanceof WP_Post) {
          $country_id_item = (int) $country_val->ID;
        } elseif (is_array($country_val)) {
          $country_id_item = (int) reset($country_val);
        } else {
          $country_id_item = (int) $country_val;
        }
      }

      $country_title = $country_id_item ? (string) get_the_title($country_id_item) : '';
      $country_slug = $country_id_item ? (string) get_post_field('post_name', $country_id_item) : '';

      $flag_url = '';
      if ($country_id_item && function_exists('get_field')) {
        $flag_field = get_field('flag', $country_id_item);
        if ($flag_field) {
          if (is_array($flag_field) && !empty($flag_field['url'])) {
            $flag_url = (string) $flag_field['url'];
          } elseif (is_string($flag_field)) {
            $flag_url = (string) $flag_field;
          }
        }
      }

      $image_url = '';
      $thumb = get_the_post_thumbnail_url($education_id, 'large');
      if ($thumb) {
        $image_url = (string) $thumb;
      } else {
        $gallery = function_exists('get_field') ? get_field('education_gallery', $education_id) : [];
        $gallery = is_array($gallery) ? $gallery : [];
        if (!empty($gallery[0])) {
          if (is_array($gallery[0]) && !empty($gallery[0]['ID'])) {
            $first_id = (int) $gallery[0]['ID'];
          } elseif (is_numeric($gallery[0])) {
            $first_id = (int) $gallery[0];
          }
          if ($first_id) {
            $img = wp_get_attachment_image_url($first_id, 'large');
            if ($img) {
              $image_url = (string) $img;
            }
          }
        }
      }

      $price = '';
      if (function_exists('get_field')) {
        $price_val = get_field('education_price', $education_id);

        if (is_string($price_val) && $price_val !== '') {
          $price = (string) $price_val;
        }

        $education_programs = get_field('education_programs', $education_id);
        $education_programs = is_array($education_programs) ? $education_programs : [];

        if (empty($price) && !empty($education_programs)) {
          $prices = [];
          foreach ($education_programs as $program) {
            $program_price = '';
            if (isset($program['program_price_per_week'])) {
              $program_price = (string) $program['program_price_per_week'];
            } elseif (isset($program['price_per_week'])) {
              $program_price = (string) $program['price_per_week'];
            }
            if ($program_price) {
              preg_match('/[\d\s]+/', $program_price, $matches);
              if (!empty($matches[0])) {
                $prices[] = (int) str_replace(' ', '', $matches[0]);
              }
            }
          }

          if (!empty($prices)) {
            $min_price_value = min($prices);
            $price = number_format($min_price_value, 0, ',', ' ') . ' ₽/неделя';
          }
        }

        if (!empty($price)) {
          $price = format_price_text($price);
        }
      }

      $languages = wp_get_post_terms($education_id, 'education_language', ['fields' => 'names']);
      $languages = is_wp_error($languages) ? [] : $languages;

      $programs = wp_get_post_terms($education_id, 'education_program', ['fields' => 'names']);
      $programs = is_wp_error($programs) ? [] : $programs;

      $booking_url = '';
      if (function_exists('get_field')) {
        $booking_url_val = get_field('education_booking_url', $education_id);
        if ($booking_url_val) {
          $booking_url = trim((string) $booking_url_val);
        }
      }

      $age_min = 0;
      $age_max = 0;
      $nearest_date = '';

      if (function_exists('get_field')) {
        $education_programs = get_field('education_programs', $education_id);
        $education_programs = is_array($education_programs) ? $education_programs : [];

        if (!empty($education_programs)) {
          $ages_min = [];
          $ages_max = [];
          $all_dates = [];

          foreach ($education_programs as $program) {
            $program_age_min = isset($program['program_age_min']) && $program['program_age_min'] !== '' ? (int) $program['program_age_min'] : 0;
            $program_age_max = isset($program['program_age_max']) && $program['program_age_max'] !== '' ? (int) $program['program_age_max'] : 0;

            if ($program_age_min > 0) {
              $ages_min[] = $program_age_min;
            }
            if ($program_age_max > 0) {
              $ages_max[] = $program_age_max;
            }

            $date_from = isset($program['program_checkin_date_from']) ? (string) $program['program_checkin_date_from'] : '';
            if ($date_from) {
              $all_dates[] = $date_from;
            }
          }

          if (!empty($ages_min)) {
            $age_min = min($ages_min);
          }
          if (!empty($ages_max)) {
            $age_max = max($ages_max);
          }

          if (!empty($all_dates)) {
            $today = date('Y-m-d');
            $future_dates = array_filter($all_dates, function ($date) use ($today) {
              return $date >= $today;
            });

            if (!empty($future_dates)) {
              sort($future_dates);
              $nearest_date = $future_dates[0];
            } elseif (!empty($all_dates)) {
              sort($all_dates);
              $nearest_date = $all_dates[0];
            }
          }
        }
      }

      $item = [
        'id' => $education_id,
        'url' => get_permalink($education_id),
        'image' => $image_url,
        'title' => get_the_title($education_id),
        'flag' => $flag_url,
        'country_title' => $country_title,
        'price' => $price,
        'languages' => $languages,
        'programs' => $programs,
        'country_id' => $country_id_item,
        'country_slug' => $country_slug,
        'booking_url' => $booking_url,
        'age_min' => $age_min,
        'age_max' => $age_max,
        'nearest_date' => $nearest_date,
      ];

      echo '<div class="swiper-slide" data-country="' . esc_attr($item['country_id']) . '" data-country-slug="' . esc_attr($item['country_slug']) . '">';
      set_query_var('education', $item);
      get_template_part('template-parts/education/card');
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
