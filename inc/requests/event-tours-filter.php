<?php

add_action('wp_ajax_event_tours_filter', 'event_tours_filter');
add_action('wp_ajax_nopriv_event_tours_filter', 'event_tours_filter');

function event_tours_filter()
{
  $event_tours_term_id = isset($_POST['event_tours_term_id']) ? absint(wp_unslash($_POST['event_tours_term_id'])) : 0;
  if (!$event_tours_term_id) {
    wp_send_json_error(['message' => 'no_term_id']);
  }

  $country_id = isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;
  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

  $tax_query = [
    [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => [$event_tours_term_id],
    ],
  ];

  // Фильтр по региону
  if ($region_id) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
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
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => -1, // Получаем все для фильтрации по датам
    'orderby' => 'title',
    'order' => 'ASC',
    'tax_query' => array_merge([['relation' => 'AND']], $tax_query),
  ];

  if (!empty($meta_query)) {
    $args['meta_query'] = array_merge([['relation' => 'AND']], $meta_query);
  }

  $q = new WP_Query($args);

  // Фильтрация по диапазону дат (если указан)
  $filtered_posts = [];
  $date_from_timestamp = $date_from ? strtotime($date_from) : 0;
  $date_to_timestamp = $date_to ? strtotime($date_to) : 0;
  
  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      $post_id = get_the_ID();
      
      // Если диапазон дат не указан, показываем все туры
      if (!$date_from || !$date_to) {
        $filtered_posts[] = $post_id;
        continue;
      }
      
      $checkin_dates = function_exists('get_field') ? get_field('tour_checkin_dates', $post_id) : '';
      
      // Если поле пустое, показываем тур (без фильтрации по датам)
      if (empty($checkin_dates)) {
        $filtered_posts[] = $post_id;
        continue;
      }
      
      // Пытаемся найти даты в тексте и проверить, попадает ли хотя бы одна в диапазон
      $dates_found = false;
      
      $month_names = [
        'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
        'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
        'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12
      ];
      
      $current_year = date('Y');
      
      // Парсим даты в формате "15 марта, 22 марта"
      preg_match_all('/(\d{1,2})\s+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)/ui', $checkin_dates, $matches);
      if (!empty($matches[0])) {
        foreach ($matches[1] as $idx => $day) {
          $month_name = mb_strtolower($matches[2][$idx]);
          if (isset($month_names[$month_name])) {
            $month = $month_names[$month_name];
            $year = $current_year;
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, (int)$day);
            $date_timestamp = strtotime($date_str);
            
            if ($date_timestamp >= $date_from_timestamp && $date_timestamp <= $date_to_timestamp) {
              $dates_found = true;
              break;
            }
          }
        }
      }
      
      // Парсим даты в формате DD.MM.YYYY или DD.MM
      if (!$dates_found) {
        preg_match_all('/(\d{1,2})\.(\d{1,2})(?:\.(\d{4}))?/', $checkin_dates, $date_matches);
        if (!empty($date_matches[0])) {
          foreach ($date_matches[0] as $match_idx => $match) {
            $day = (int)$date_matches[1][$match_idx];
            $month = (int)$date_matches[2][$match_idx];
            $year = !empty($date_matches[3][$match_idx]) ? (int)$date_matches[3][$match_idx] : $current_year;
            
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $date_timestamp = strtotime($date_str);
            
            if ($date_timestamp >= $date_from_timestamp && $date_timestamp <= $date_to_timestamp) {
              $dates_found = true;
              break;
            }
          }
        }
      }
      
      // Показываем тур, если даты не указаны в поле или хотя бы одна дата попадает в диапазон
      if ($dates_found) {
        $filtered_posts[] = $post_id;
      }
    }
    wp_reset_postdata();
  }
  
  // Если есть фильтрация по датам, перезапрашиваем только отфильтрованные посты
  if ($date_from && $date_to && !empty($filtered_posts)) {
    $args['post__in'] = $filtered_posts;
    $args['posts_per_page'] = 12;
    $args['orderby'] = 'title';
    $q = new WP_Query($args);
  } elseif ($date_from && $date_to && empty($filtered_posts)) {
    // Если ничего не найдено после фильтрации
    $q->posts = [];
    $q->post_count = 0;
    $q->found_posts = 0;
  } else {
    // Если фильтрация по датам не применялась, ограничиваем количество
    $args['posts_per_page'] = 12;
    $q = new WP_Query($args);
  }

  ob_start();
  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      get_template_part('template-parts/tour/card-row', null, ['post_id' => get_the_ID()]);
    }
  } else {
    echo '<div class="country-tours__empty">Туры не найдены.</div>';
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
  $event_tours_term_id = isset($_POST['event_tours_term_id']) ? absint(wp_unslash($_POST['event_tours_term_id'])) : 0;
  if (!$event_tours_term_id) {
    wp_send_json_success(['items' => []]);
  }

  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;
  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

  // Базовый запрос туров с типом "Событийные туры"
  $tax_query = [
    [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => [$event_tours_term_id],
    ],
  ];

  if ($region_id) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
    ];
  }

  $args = [
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'tax_query' => array_merge([['relation' => 'AND']], $tax_query),
  ];

  $q = new WP_Query($args);

  // Фильтрация по диапазону дат (если указан)
  $filtered_tour_ids = [];
  if ($q->have_posts()) {
    $date_from_timestamp = $date_from ? strtotime($date_from) : 0;
    $date_to_timestamp = $date_to ? strtotime($date_to) : 0;

    foreach ($q->posts as $tour_id) {
      // Если диапазон дат не указан, добавляем все туры
      if (!$date_from || !$date_to) {
        $filtered_tour_ids[] = $tour_id;
        continue;
      }

      $checkin_dates = function_exists('get_field') ? get_field('tour_checkin_dates', $tour_id) : '';
      if (empty($checkin_dates)) {
        $filtered_tour_ids[] = $tour_id;
        continue;
      }

      // Проверяем наличие дат в диапазоне (упрощенная версия)
      $month_names = [
        'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
        'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
        'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12
      ];
      $current_year = date('Y');
      $dates_found = false;

      preg_match_all('/(\d{1,2})\s+(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)/ui', $checkin_dates, $matches);
      if (!empty($matches[0])) {
        foreach ($matches[1] as $idx => $day) {
          $month_name = mb_strtolower($matches[2][$idx]);
          if (isset($month_names[$month_name])) {
            $month = $month_names[$month_name];
            $date_str = sprintf('%04d-%02d-%02d', $current_year, $month, (int)$day);
            $date_timestamp = strtotime($date_str);
            if ($date_timestamp >= $date_from_timestamp && $date_timestamp <= $date_to_timestamp) {
              $dates_found = true;
              break;
            }
          }
        }
      }

      if (!$dates_found) {
        preg_match_all('/(\d{1,2})\.(\d{1,2})(?:\.(\d{4}))?/', $checkin_dates, $date_matches);
        if (!empty($date_matches[0])) {
          foreach ($date_matches[0] as $match_idx => $match) {
            $day = (int)$date_matches[1][$match_idx];
            $month = (int)$date_matches[2][$match_idx];
            $year = !empty($date_matches[3][$match_idx]) ? (int)$date_matches[3][$match_idx] : $current_year;
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $date_timestamp = strtotime($date_str);
            if ($date_timestamp >= $date_from_timestamp && $date_timestamp <= $date_to_timestamp) {
              $dates_found = true;
              break;
            }
          }
        }
      }

      if ($dates_found || empty($checkin_dates)) {
        $filtered_tour_ids[] = $tour_id;
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
  $event_tours_term_id = isset($_POST['event_tours_term_id']) ? absint(wp_unslash($_POST['event_tours_term_id'])) : 0;
  if (!$event_tours_term_id) {
    wp_send_json_success(['dates' => []]);
  }

  $country_id = isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;

  // Базовый запрос туров с типом "Событийные туры"
  $tax_query = [
    [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => [$event_tours_term_id],
    ],
  ];

  if ($region_id) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
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
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'tax_query' => array_merge([['relation' => 'AND']], $tax_query),
  ];

  if (!empty($meta_query)) {
    $args['meta_query'] = array_merge([['relation' => 'AND']], $meta_query);
  }

  $tours_query = new WP_Query($args);
  $tour_ids = $tours_query->posts;
  wp_reset_postdata();

  if (empty($tour_ids)) {
    wp_send_json_success(['dates' => []]);
  }

  // Получаем доступные даты для всех туров
  $all_available_dates = [];
  
  require_once get_template_directory() . '/inc/helpers.php';
  
  if (!class_exists('SamoService')) {
    require_once get_template_directory() . '/inc/samo/SamoService.php';
  }

  foreach ($tour_ids as $tour_id) {
    $excursion_params = get_tour_excursion_params((int) $tour_id);
    
    if (empty($excursion_params['TOWNFROMINC']) || empty($excursion_params['STATEINC']) || empty($excursion_params['TOURS'])) {
      continue;
    }

    try {
      $result = SamoService::endpoints()->searchExcursionAll([
        'TOWNFROMINC' => $excursion_params['TOWNFROMINC'],
        'STATEINC' => $excursion_params['STATEINC'],
        'TOURS' => $excursion_params['TOURS'],
      ]);

      if (isset($result['data']['SearchExcursion_ALL']['CHECKIN_BEG'])) {
        $checkInBeg = $result['data']['SearchExcursion_ALL']['CHECKIN_BEG'];
        
        if (!empty($checkInBeg['validDates']) && !empty($checkInBeg['startDate'])) {
          // Парсим доступные даты
          $validDates = $checkInBeg['validDates'];
          $startDate = $checkInBeg['startDate'];
          
          // Формат startDate: YYYYMMDD
          if (strlen($startDate) === 8) {
            $startTimestamp = strtotime(
              substr($startDate, 0, 4) . '-' . 
              substr($startDate, 4, 2) . '-' . 
              substr($startDate, 6, 2)
            );
            
            if ($startTimestamp) {
              for ($i = 0; $i < strlen($validDates); $i++) {
                if ($validDates[$i] === '1') {
                  $date = $startTimestamp + ($i * 86400); // добавляем дни
                  $dateStr = date('Y-m-d', $date);
                  $all_available_dates[$dateStr] = true; // используем ключи для уникальности
                }
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      // Пропускаем туры с ошибками
      continue;
    }
  }

  // Преобразуем в массив и сортируем
  $dates = array_keys($all_available_dates);
  sort($dates);

  wp_send_json_success(['dates' => $dates]);
}
