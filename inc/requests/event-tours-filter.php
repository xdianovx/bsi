<?php

add_action('wp_ajax_event_tours_filter', 'event_tours_filter');
add_action('wp_ajax_nopriv_event_tours_filter', 'event_tours_filter');

function event_tours_filter()
{
  $country_id = isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;
  $tour_type_id = isset($_POST['tour_type']) ? absint(wp_unslash($_POST['tour_type'])) : 0;
  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

  $tax_query = [];

  // Фильтр по региону
  if ($region_id) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
    ];
  }

  // Фильтр по типу тура
  if ($tour_type_id) {
    $tax_query[] = [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => [$tour_type_id],
    ];
  }

  $meta_query = [];

  // Фильтр по стране
  if ($country_id) {
    $meta_query[] = [
      'key' => 'tour_country',
      'value' => $country_id,
      'compare' => '=',
    ];
  }

  $args = [
    'post_type' => 'event',
    'post_status' => 'publish',
    'posts_per_page' => -1, // Получаем все для фильтрации по датам
    'orderby' => 'title',
    'order' => 'ASC',
  ];

  if (!empty($tax_query)) {
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  if (!empty($meta_query)) {
    $args['meta_query'] = array_merge([['relation' => 'AND']], $meta_query);
  }

  $q = new WP_Query($args);

  // Фильтрация по диапазону дат через ACF repeater event_dates
  $filtered_posts = [];
  $date_from_timestamp = $date_from ? strtotime($date_from) : 0;
  $date_to_timestamp = $date_to ? strtotime($date_to) : 0;
  $has_date_filter = ($date_from && $date_to);

  if ($has_date_filter) {
    // Получаем все посты для ручной фильтрации по датам
    if ($q->have_posts()) {
      while ($q->have_posts()) {
        $q->the_post();
        $post_id = get_the_ID();

        $event_dates = function_exists('get_field') ? get_field('event_dates', $post_id) : [];

        // Если дат нет — не показываем при фильтрации по дате
        if (empty($event_dates) || !is_array($event_dates)) {
          continue;
        }

        foreach ($event_dates as $row) {
          $d = isset($row['date_value']) ? $row['date_value'] : '';
          if (!$d) continue;
          $ts = strtotime($d);
          if ($ts >= $date_from_timestamp && $ts <= $date_to_timestamp) {
            $filtered_posts[] = $post_id;
            break;
          }
        }
      }
      wp_reset_postdata();
    }

    if (!empty($filtered_posts)) {
      $args['post__in'] = $filtered_posts;
      $args['posts_per_page'] = 12;
      $q = new WP_Query($args);
    } else {
      $q->posts = [];
      $q->post_count = 0;
      $q->found_posts = 0;
    }
  } else {
    $args['posts_per_page'] = 12;
    $q = new WP_Query($args);
  }

  ob_start();
  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      get_template_part('template-parts/event/card-row', null, ['post_id' => get_the_ID()]);
    }
  } else {
    echo '<div class="country-tours__empty">События не найдены.</div>';
  }
  wp_reset_postdata();

  wp_send_json_success([
    'html' => ob_get_clean(),
    'total' => (int) $q->found_posts,
  ]);
}


add_action('wp_ajax_event_tours_regions', 'event_tours_regions');
add_action('wp_ajax_nopriv_event_tours_regions', 'event_tours_regions');

function event_tours_regions()
{
  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;

  // Если выбран регион — курорты по нему (но это не используется в текущей реализации)
  if ($region_id) {
    $terms = get_terms([
      'taxonomy' => 'resort',
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
      'meta_query' => [
        [
          'key' => 'resort_region',
          'value' => $region_id,
          'compare' => '=',
        ],
      ],
    ]);
  } elseif ($country_id) {
    // Если выбрана страна — все регионы этой страны
    $terms = get_terms([
      'taxonomy' => 'region',
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
      'meta_query' => [
        [
          'key' => 'region_country',
          'value' => $country_id,
          'compare' => '=',
        ],
      ],
    ]);
  } else {
    // Если страна не выбрана — возвращаем все регионы
    $terms = get_terms([
      'taxonomy' => 'region',
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
  }

  if (is_wp_error($terms) || empty($terms)) {
    wp_send_json_success(['items' => []]);
  }

  $items = [];
  foreach ($terms as $t) {
    $items[] = [
      'id' => (int) $t->term_id,
      'text' => (string) $t->name,
    ];
  }

  wp_send_json_success(['items' => $items]);
}


add_action('wp_ajax_event_tours_countries', 'event_tours_countries');
add_action('wp_ajax_nopriv_event_tours_countries', 'event_tours_countries');

function event_tours_countries()
{
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;
  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

  // Базовый запрос событийных туров
  $tax_query = [];

  if ($region_id) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
    ];
  }

  $args = [
    'post_type' => 'event',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ];

  if (!empty($tax_query)) {
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  $q = new WP_Query($args);

  // Фильтрация по диапазону дат
  $filtered_tour_ids = [];
  $has_date_filter = ($date_from && $date_to);

  if ($q->have_posts()) {
    $date_from_timestamp = $date_from ? strtotime($date_from) : 0;
    $date_to_timestamp = $date_to ? strtotime($date_to) : 0;

    foreach ($q->posts as $tour_id) {
      if (!$has_date_filter) {
        $filtered_tour_ids[] = $tour_id;
        continue;
      }

      $event_dates = function_exists('get_field') ? get_field('event_dates', $tour_id) : [];
      if (empty($event_dates) || !is_array($event_dates)) {
        continue;
      }

      foreach ($event_dates as $row) {
        $d = isset($row['date_value']) ? $row['date_value'] : '';
        if (!$d) continue;
        $ts = strtotime($d);
        if ($ts >= $date_from_timestamp && $ts <= $date_to_timestamp) {
          $filtered_tour_ids[] = $tour_id;
          break;
        }
      }
    }
    wp_reset_postdata();
  }

  // Извлекаем страны из отфильтрованных туров
  $country_ids = [];
  if (!empty($filtered_tour_ids)) {
    foreach ($filtered_tour_ids as $tour_id) {
      $country_val = function_exists('get_field') ? get_field('tour_country', $tour_id) : null;
      if ($country_val) {
        if (is_array($country_val)) {
          $country_ids = array_merge($country_ids, array_map('intval', $country_val));
        } elseif (is_numeric($country_val)) {
          $country_ids[] = (int) $country_val;
        } elseif ($country_val instanceof WP_Post) {
          $country_ids[] = (int) $country_val->ID;
        }
      }
    }
  }

  $country_ids = array_values(array_unique(array_filter($country_ids)));

  if (empty($country_ids)) {
    wp_send_json_success(['items' => []]);
  }

  // Получаем страны
  $countries = get_posts([
    'post_type' => 'country',
    'post_status' => 'publish',
    'post__in' => $country_ids,
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_parent' => 0,
  ]);

  $items = [];
  foreach ($countries as $country) {
    $items[] = [
      'id' => (int) $country->ID,
      'text' => (string) $country->post_title,
    ];
  }

  wp_send_json_success(['items' => $items]);
}


add_action('wp_ajax_event_tours_available_dates', 'event_tours_available_dates');
add_action('wp_ajax_nopriv_event_tours_available_dates', 'event_tours_available_dates');

function event_tours_available_dates()
{
  $country_id = isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;
  $tour_type_id = isset($_POST['tour_type']) ? absint(wp_unslash($_POST['tour_type'])) : 0;

  $tax_query = [];

  if ($region_id) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
    ];
  }

  if ($tour_type_id) {
    $tax_query[] = [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => [$tour_type_id],
    ];
  }

  $meta_query = [];
  if ($country_id) {
    $meta_query[] = [
      'key' => 'tour_country',
      'value' => $country_id,
      'compare' => '=',
    ];
  }

  $args = [
    'post_type' => 'event',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ];

  if (!empty($tax_query)) {
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  if (!empty($meta_query)) {
    $args['meta_query'] = array_merge([['relation' => 'AND']], $meta_query);
  }

  $tours_query = new WP_Query($args);
  $tour_ids = $tours_query->posts;
  wp_reset_postdata();

  if (empty($tour_ids)) {
    wp_send_json_success(['dates' => []]);
  }

  $all_dates = [];
  foreach ($tour_ids as $tour_id) {
    $event_dates = function_exists('get_field') ? get_field('event_dates', $tour_id) : [];
    if (!empty($event_dates) && is_array($event_dates)) {
      foreach ($event_dates as $row) {
        $d = isset($row['date_value']) ? $row['date_value'] : '';
        if ($d) {
          $all_dates[$d] = true;
        }
      }
    }
  }

  $dates = array_keys($all_dates);
  sort($dates);

  wp_send_json_success(['dates' => $dates]);
}
