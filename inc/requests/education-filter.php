<?php

if (!defined('ABSPATH')) {
  exit;
}

add_action('wp_ajax_education_filter', 'bsi_ajax_education_filter');
add_action('wp_ajax_nopriv_education_filter', 'bsi_ajax_education_filter');

function bsi_ajax_education_filter(): void
{
  $program_id = 0;
  if (isset($_POST['program'])) {
    $program_id = absint(wp_unslash($_POST['program']));
  }

  $age = isset($_POST['age']) ? absint(wp_unslash($_POST['age'])) : 0;

  $language_id = 0;
  if (isset($_POST['language'])) {
    $language_id = absint(wp_unslash($_POST['language']));
  }

  $country_id = isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0;

  $duration_min = isset($_POST['duration_min']) ? absint(wp_unslash($_POST['duration_min'])) : 0;
  $duration_max = isset($_POST['duration_max']) ? absint(wp_unslash($_POST['duration_max'])) : 0;

  $type_id = 0;
  if (isset($_POST['type'])) {
    $type_id = absint(wp_unslash($_POST['type']));
  }

  $accommodation_id = 0;
  if (isset($_POST['accommodation'])) {
    $accommodation_id = absint(wp_unslash($_POST['accommodation']));
  }

  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

  $paged = isset($_POST['paged']) ? absint(wp_unslash($_POST['paged'])) : 1;
  $per_page = 12;
  $sort = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'title_asc';

  $tax_query = [];

  if ($program_id > 0) {
    $tax_query[] = [
      'taxonomy' => 'education_program',
      'field' => 'term_id',
      'terms' => [$program_id],
    ];
  }

  if ($language_id > 0) {
    $tax_query[] = [
      'taxonomy' => 'education_language',
      'field' => 'term_id',
      'terms' => [$language_id],
    ];
  }

  if ($type_id > 0) {
    $tax_query[] = [
      'taxonomy' => 'education_type',
      'field' => 'term_id',
      'terms' => [$type_id],
    ];
  }

  if ($accommodation_id > 0) {
    $tax_query[] = [
      'taxonomy' => 'education_accommodation_type',
      'field' => 'term_id',
      'terms' => [$accommodation_id],
    ];
  }

  $meta_query = [];

  // Если страна выбрана - фильтруем по стране, иначе показываем все школы
  if ($country_id > 0) {
    $meta_query[] = [
      'key' => 'education_country',
      'value' => $country_id,
      'compare' => '=',
    ];
  }

  $args = [
    'post_type' => 'education',
    'post_status' => 'publish',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
  ];

  if (!empty($tax_query)) {
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
  }

  $query = new WP_Query($args);

  $filtered_ids = [];

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      $education_id = get_the_ID();

      $has_matching_program = false;

      if ($age > 0 || $duration_min > 0 || $duration_max > 0 || $date_from || $date_to) {
        $school_programs = function_exists('get_field') ? get_field('education_programs', $education_id) : [];
        $school_programs = is_array($school_programs) ? $school_programs : [];

        foreach ($school_programs as $program) {
          $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
          $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
          $program_duration = isset($program['program_duration']) ? (int) $program['program_duration'] : 0;

          $age_match = true;
          if ($age > 0) {
            // Проверяем, попадает ли выбранный возраст в диапазон программы
            if ($program_age_min > 0 && $age < $program_age_min) {
              $age_match = false;
            }
            if ($program_age_max > 0 && $age > $program_age_max) {
              $age_match = false;
            }
            // Если у программы нет ограничений по возрасту, пропускаем проверку
            if ($program_age_min === 0 && $program_age_max === 0) {
              $age_match = true;
            }
          }

          $duration_match = true;
          if ($duration_min > 0 || $duration_max > 0) {
            if ($program_duration <= 0) {
              $duration_match = false;
            } else {
              if ($duration_min > 0 && $program_duration < $duration_min) {
                $duration_match = false;
              }
              if ($duration_max > 0 && $program_duration > $duration_max) {
                $duration_match = false;
              }
            }
          }

          $date_match = true;
          if ($date_from || $date_to) {
            $program_date_from = isset($program['program_checkin_date_from']) ? (string) $program['program_checkin_date_from'] : '';
            $program_date_to = isset($program['program_checkin_date_to']) ? (string) $program['program_checkin_date_to'] : '';

            if (!$program_date_from) {
              $date_match = false;
            } else {
              $program_from_ts = strtotime($program_date_from);
              $program_to_ts = $program_date_to ? strtotime($program_date_to) : $program_from_ts;
              $filter_from_ts = $date_from ? strtotime($date_from) : 0;
              $filter_to_ts = $date_to ? strtotime($date_to) : PHP_INT_MAX;

              if ($filter_from_ts && $program_to_ts < $filter_from_ts) {
                $date_match = false;
              } elseif ($filter_to_ts && $program_from_ts > $filter_to_ts) {
                $date_match = false;
              }
            }
          }

          if ($age_match && $duration_match && $date_match) {
            $has_matching_program = true;
            break;
          }
        }

        if (!$has_matching_program) {
          continue;
        }
      } else {
        $has_matching_program = true;
      }

      if ($has_matching_program) {
        $filtered_ids[] = $education_id;
      }
    }
    wp_reset_postdata();
  }

  if (empty($filtered_ids)) {
    wp_send_json_success([
      'html' => '<div class="education-page__empty">Школы не найдены.</div>',
      'total' => 0,
      'pages' => 0,
    ]);
  }

  // Определяем сортировку
  $orderby = 'title';
  $order = 'ASC';

  switch ($sort) {
    case 'title_desc':
      $orderby = 'title';
      $order = 'DESC';
      break;
    case 'price_asc':
    case 'price_desc':
      $orderby = 'meta_value_num';
      $order = $sort === 'price_asc' ? 'ASC' : 'DESC';
      break;
    default:
      $orderby = 'title';
      $order = 'ASC';
  }

  $query_args = [
    'post_type' => 'education',
    'post_status' => 'publish',
    'post__in' => $filtered_ids,
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => $orderby,
    'order' => $order,
  ];

  // Если сортировка по цене - добавляем meta_key
  if ($sort === 'price_asc' || $sort === 'price_desc') {
    $query_args['meta_key'] = 'education_price';
    $query_args['orderby'] = 'meta_value_num';
  }

  $final_query = new WP_Query($query_args);

  // Если сортировка по цене, но не все посты имеют цену, нужно отсортировать вручную
  if (($sort === 'price_asc' || $sort === 'price_desc') && $final_query->have_posts()) {
    $posts = $final_query->posts;
    usort($posts, function ($a, $b) use ($sort) {
      $price_a = function_exists('get_field') ? get_field('education_price', $a->ID) : '';
      $price_b = function_exists('get_field') ? get_field('education_price', $b->ID) : '';

      // Извлекаем числа из строк цен
      preg_match('/[\d\s]+/', (string) $price_a, $matches_a);
      preg_match('/[\d\s]+/', (string) $price_b, $matches_b);

      $num_a = isset($matches_a[0]) ? (int) str_replace(' ', '', $matches_a[0]) : 0;
      $num_b = isset($matches_b[0]) ? (int) str_replace(' ', '', $matches_b[0]) : 0;

      if ($sort === 'price_asc') {
        return $num_a <=> $num_b;
      } else {
        return $num_b <=> $num_a;
      }
    });

    // Пересоздаем запрос с отсортированными постами
    $final_query->posts = array_slice($posts, ($paged - 1) * $per_page, $per_page);
    $final_query->post_count = count($final_query->posts);
  }

  ob_start();
  if ($final_query->have_posts()) {
    while ($final_query->have_posts()) {
      $final_query->the_post();
      $education_id = (int) get_the_ID();

      $country_id = 0;
      if (function_exists('get_field')) {
        $country_val = get_field('education_country', $education_id);
        if ($country_val instanceof WP_Post) {
          $country_id = (int) $country_val->ID;
        } elseif (is_array($country_val)) {
          $country_id = (int) reset($country_val);
        } else {
          $country_id = (int) $country_val;
        }
      }

      $country_title = $country_id ? (string) get_the_title($country_id) : '';
      $country_slug = $country_id ? (string) get_post_field('post_name', $country_id) : '';

      $flag_url = '';
      if ($country_id && function_exists('get_field')) {
        $flag_field = get_field('flag', $country_id);
        if ($flag_field) {
          if (is_array($flag_field) && !empty($flag_field['url'])) {
            $flag_url = (string) $flag_field['url'];
          } elseif (is_string($flag_field)) {
            $flag_url = (string) $flag_field;
          }
        }
      }

      $resort_title = '';
      if (function_exists('get_field')) {
        $resort_field = get_field('education_resort', $education_id);
        if ($resort_field) {
          if ($resort_field instanceof WP_Term) {
            $resort_title = (string) $resort_field->name;
          } elseif (is_array($resort_field)) {
            $first_item = reset($resort_field);
            if ($first_item instanceof WP_Term) {
              $resort_title = (string) $first_item->name;
            } else {
              $resort_id = (int) $first_item;
              $resort_term = get_term($resort_id, 'resort');
              if ($resort_term && !is_wp_error($resort_term)) {
                $resort_title = (string) $resort_term->name;
              }
            }
          } else {
            $resort_id = (int) $resort_field;
            $resort_term = get_term($resort_id, 'resort');
            if ($resort_term && !is_wp_error($resort_term)) {
              $resort_title = (string) $resort_term->name;
            }
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
        'resort_title' => $resort_title,
        'price' => $price,
        'languages' => $languages,
        'programs' => $programs,
        'country_id' => $country_id,
        'country_slug' => $country_slug,
        'booking_url' => $booking_url,
        'age_min' => $age_min,
        'age_max' => $age_max,
        'nearest_date' => $nearest_date,
      ];

      echo '<div class="education-page__item">';
      set_query_var('education', $item);
      get_template_part('template-parts/education/card');
      echo '</div>';
    }
  }
  wp_reset_postdata();

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total' => (int) $final_query->found_posts,
    'pages' => (int) $final_query->max_num_pages,
  ]);
}

add_action('wp_ajax_country_education_filter', 'bsi_ajax_country_education_filter');
add_action('wp_ajax_nopriv_country_education_filter', 'bsi_ajax_country_education_filter');

function bsi_ajax_country_education_filter(): void
{
  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
  if (!$country_id) {
    wp_send_json_error(['message' => 'no_country']);
  }

  $programs = [];
  if (isset($_POST['program'])) {
    $raw = (array) wp_unslash($_POST['program']);
    $programs = array_values(array_filter(array_map('absint', $raw)));
  }

  $age_min = isset($_POST['age_min']) ? absint(wp_unslash($_POST['age_min'])) : 0;
  $age_max = isset($_POST['age_max']) ? absint(wp_unslash($_POST['age_max'])) : 0;

  $languages = [];
  if (isset($_POST['language'])) {
    $raw = (array) wp_unslash($_POST['language']);
    $languages = array_values(array_filter(array_map('absint', $raw)));
  }

  $duration_min = isset($_POST['duration_min']) ? absint(wp_unslash($_POST['duration_min'])) : 0;
  $duration_max = isset($_POST['duration_max']) ? absint(wp_unslash($_POST['duration_max'])) : 0;

  $types = [];
  if (isset($_POST['type'])) {
    $raw = (array) wp_unslash($_POST['type']);
    $types = array_values(array_filter(array_map('absint', $raw)));
  }

  $accommodations = [];
  if (isset($_POST['accommodation'])) {
    $raw = (array) wp_unslash($_POST['accommodation']);
    $accommodations = array_values(array_filter(array_map('absint', $raw)));
  }

  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

  $tax_query = [];

  if (!empty($programs)) {
    $tax_query[] = [
      'taxonomy' => 'education_program',
      'field' => 'term_id',
      'terms' => $programs,
    ];
  }

  if (!empty($languages)) {
    $tax_query[] = [
      'taxonomy' => 'education_language',
      'field' => 'term_id',
      'terms' => $languages,
    ];
  }

  if (!empty($types)) {
    $tax_query[] = [
      'taxonomy' => 'education_type',
      'field' => 'term_id',
      'terms' => $types,
    ];
  }

  if (!empty($accommodations)) {
    $tax_query[] = [
      'taxonomy' => 'education_accommodation_type',
      'field' => 'term_id',
      'terms' => $accommodations,
    ];
  }

  $args = [
    'post_type' => 'education',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => [
      [
        'key' => 'education_country',
        'value' => $country_id,
        'compare' => '=',
      ],
    ],
  ];

  if (!empty($tax_query)) {
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  $query = new WP_Query($args);

  $filtered_ids = [];

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      $education_id = get_the_ID();

      $has_matching_program = false;

      if ($age_min > 0 || $age_max > 0 || $duration_min > 0 || $duration_max > 0 || $date_from || $date_to) {
        $school_programs = function_exists('get_field') ? get_field('education_programs', $education_id) : [];
        $school_programs = is_array($school_programs) ? $school_programs : [];

        foreach ($school_programs as $program) {
          $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
          $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
          $program_duration = isset($program['program_duration']) ? (int) $program['program_duration'] : 0;

          $age_match = true;
          if ($age_min > 0 || $age_max > 0) {
            if ($age_min > 0 && $program_age_max > 0 && $age_min > $program_age_max) {
              $age_match = false;
            }
            if ($age_max > 0 && $program_age_min > 0 && $age_max < $program_age_min) {
              $age_match = false;
            }
          }

          $duration_match = true;
          if ($duration_min > 0 || $duration_max > 0) {
            if ($program_duration <= 0) {
              $duration_match = false;
            } else {
              if ($duration_min > 0 && $program_duration < $duration_min) {
                $duration_match = false;
              }
              if ($duration_max > 0 && $program_duration > $duration_max) {
                $duration_match = false;
              }
            }
          }

          $date_match = true;
          if ($date_from || $date_to) {
            $program_date_from = isset($program['program_checkin_date_from']) ? (string) $program['program_checkin_date_from'] : '';
            $program_date_to = isset($program['program_checkin_date_to']) ? (string) $program['program_checkin_date_to'] : '';

            if (!$program_date_from) {
              $date_match = false;
            } else {
              $program_from_ts = strtotime($program_date_from);
              $program_to_ts = $program_date_to ? strtotime($program_date_to) : $program_from_ts;
              $filter_from_ts = $date_from ? strtotime($date_from) : 0;
              $filter_to_ts = $date_to ? strtotime($date_to) : PHP_INT_MAX;

              if ($filter_from_ts && $program_to_ts < $filter_from_ts) {
                $date_match = false;
              } elseif ($filter_to_ts && $program_from_ts > $filter_to_ts) {
                $date_match = false;
              }
            }
          }

          if ($age_match && $duration_match && $date_match) {
            $has_matching_program = true;
            break;
          }
        }

        if (!$has_matching_program) {
          continue;
        }
      } else {
        $has_matching_program = true;
      }

      if ($has_matching_program) {
        $filtered_ids[] = $education_id;
      }
    }
    wp_reset_postdata();
  }

  if (empty($filtered_ids)) {
    wp_send_json_success([
      'html' => '<div class="country-education__empty">Школы не найдены.</div>',
      'total' => 0,
    ]);
  }

  $final_query = new WP_Query([
    'post_type' => 'education',
    'post_status' => 'publish',
    'post__in' => $filtered_ids,
    'posts_per_page' => 12,
    'orderby' => 'title',
    'order' => 'ASC',
  ]);

  ob_start();
  if ($final_query->have_posts()) {
    while ($final_query->have_posts()) {
      $final_query->the_post();
      echo '<div class="country-education__item">';
      get_template_part('template-parts/education/card');
      echo '</div>';
    }
  }
  wp_reset_postdata();

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total' => (int) $final_query->found_posts,
  ]);
}

add_action('wp_ajax_education_filter_options', 'bsi_ajax_education_filter_options');
add_action('wp_ajax_nopriv_education_filter_options', 'bsi_ajax_education_filter_options');

function bsi_ajax_education_filter_options(): void
{
  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;

  $args = [
    'post_type' => 'education',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ];

  if ($country_id > 0) {
    $args['meta_query'] = [
      [
        'key' => 'education_country',
        'value' => $country_id,
        'compare' => '=',
      ],
    ];
  }

  $education_ids = get_posts($args);
  $education_ids = is_array($education_ids) ? array_filter(array_map('absint', $education_ids)) : [];

  if (empty($education_ids)) {
    wp_send_json_success([
      'programs' => [],
      'languages' => [],
      'types' => [],
      'accommodations' => [],
    ]);
  }

  $program_ids = [];
  $language_ids = [];
  $type_ids = [];
  $accommodation_ids = [];

  foreach ($education_ids as $education_id) {
    $program_terms = wp_get_post_terms($education_id, 'education_program', ['fields' => 'ids']);
    if (!is_wp_error($program_terms) && !empty($program_terms)) {
      $program_ids = array_merge($program_ids, $program_terms);
    }

    $language_terms = wp_get_post_terms($education_id, 'education_language', ['fields' => 'ids']);
    if (!is_wp_error($language_terms) && !empty($language_terms)) {
      $language_ids = array_merge($language_ids, $language_terms);
    }

    $type_terms = wp_get_post_terms($education_id, 'education_type', ['fields' => 'ids']);
    if (!is_wp_error($type_terms) && !empty($type_terms)) {
      $type_ids = array_merge($type_ids, $type_terms);
    }

    $accommodation_terms = wp_get_post_terms($education_id, 'education_accommodation_type', ['fields' => 'ids']);
    if (!is_wp_error($accommodation_terms) && !empty($accommodation_terms)) {
      $accommodation_ids = array_merge($accommodation_ids, $accommodation_terms);
    }
  }

  $program_ids = array_values(array_unique($program_ids));
  $language_ids = array_values(array_unique($language_ids));
  $type_ids = array_values(array_unique($type_ids));
  $accommodation_ids = array_values(array_unique($accommodation_ids));

  $programs = [];
  if (!empty($program_ids)) {
    $program_terms = get_terms([
      'taxonomy' => 'education_program',
      'include' => $program_ids,
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($program_terms) && !empty($program_terms)) {
      foreach ($program_terms as $term) {
        $programs[] = [
          'id' => (int) $term->term_id,
          'name' => $term->name,
        ];
      }
    }
  }

  $languages = [];
  if (!empty($language_ids)) {
    $language_terms = get_terms([
      'taxonomy' => 'education_language',
      'include' => $language_ids,
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($language_terms) && !empty($language_terms)) {
      foreach ($language_terms as $term) {
        $languages[] = [
          'id' => (int) $term->term_id,
          'name' => $term->name,
        ];
      }
    }
  }

  $types = [];
  if (!empty($type_ids)) {
    $type_terms = get_terms([
      'taxonomy' => 'education_type',
      'include' => $type_ids,
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($type_terms) && !empty($type_terms)) {
      foreach ($type_terms as $term) {
        $types[] = [
          'id' => (int) $term->term_id,
          'name' => $term->name,
        ];
      }
    }
  }

  $accommodations = [];
  if (!empty($accommodation_ids)) {
    $accommodation_terms = get_terms([
      'taxonomy' => 'education_accommodation_type',
      'include' => $accommodation_ids,
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($accommodation_terms) && !empty($accommodation_terms)) {
      foreach ($accommodation_terms as $term) {
        $accommodations[] = [
          'id' => (int) $term->term_id,
          'name' => $term->name,
        ];
      }
    }
  }

  wp_send_json_success([
    'programs' => $programs,
    'languages' => $languages,
    'types' => $types,
    'accommodations' => $accommodations,
  ]);
}

add_action('wp_ajax_education_programs_by_school', 'bsi_ajax_education_programs_by_school');
add_action('wp_ajax_nopriv_education_programs_by_school', 'bsi_ajax_education_programs_by_school');

function bsi_ajax_education_programs_by_school(): void
{
  $education_id = isset($_POST['education_id']) ? absint(wp_unslash($_POST['education_id'])) : 0;
  if (!$education_id) {
    wp_send_json_error(['message' => 'no_education_id']);
  }

  // Поддержка старого формата (для обратной совместимости)
  $age_min = isset($_POST['program_age_min']) ? absint(wp_unslash($_POST['program_age_min'])) : 0;
  $age_max = isset($_POST['program_age_max']) ? absint(wp_unslash($_POST['program_age_max'])) : 0;
  
  // Формат - конкретный возраст (например, "7")
  $age = isset($_POST['program_age']) ? absint(wp_unslash($_POST['program_age'])) : 0;
  if ($age > 0) {
    $age_min = $age;
    $age_max = $age;
  }

  $duration = isset($_POST['program_duration']) ? absint(wp_unslash($_POST['program_duration'])) : 0;
  
  // Поддержка старого формата (одна дата) для обратной совместимости
  $date = isset($_POST['program_date']) ? sanitize_text_field(wp_unslash($_POST['program_date'])) : '';
  
  // Новый формат - диапазон дат
  $date_from = isset($_POST['program_date_from']) ? sanitize_text_field(wp_unslash($_POST['program_date_from'])) : '';
  $date_to = isset($_POST['program_date_to']) ? sanitize_text_field(wp_unslash($_POST['program_date_to'])) : '';
  
  // Если используется старый формат (program_date), используем его как date_from
  if (!$date_from && $date) {
    $date_from = $date;
  }
  
  $sort = isset($_POST['program_sort']) ? sanitize_text_field(wp_unslash($_POST['program_sort'])) : '';

  // Фильтр по языку (один язык)
  $language_ids = [];
  if (isset($_POST['program_language'])) {
    // Поддержка старого формата (массив) для обратной совместимости
    if (is_array($_POST['program_language'])) {
      $language_ids = array_map('absint', wp_unslash($_POST['program_language']));
      $language_ids = array_filter($language_ids);
    } else {
      // Новый формат - одно значение
      $language_id = absint(wp_unslash($_POST['program_language']));
      if ($language_id > 0) {
        $language_ids = [$language_id];
      }
    }
  }

  $programs = function_exists('get_field') ? get_field('education_programs', $education_id) : [];
  $programs = is_array($programs) ? $programs : [];

  $booking_url = function_exists('get_field') ? get_field('education_booking_url', $education_id) : '';
  $booking_url = trim((string) $booking_url);

  $filtered_programs = [];

  foreach ($programs as $index => $program) {
    $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
    $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
    $program_duration = isset($program['program_duration']) ? (int) $program['program_duration'] : 0;

    $age_match = true;
    if ($age_min > 0 && $age_max > 0 && $age_min === $age_max) {
      // Выбран конкретный возраст (например, 7 лет)
      // Проверяем, попадает ли этот возраст в диапазон программы
      $selected_age = $age_min;
      
      if ($program_age_min > 0 && $selected_age < $program_age_min) {
        // Выбранный возраст меньше минимума программы
        $age_match = false;
      } elseif ($program_age_max > 0 && $selected_age > $program_age_max) {
        // Выбранный возраст больше максимума программы
        $age_match = false;
      }
      // Если у программы нет максимума, но есть минимум и выбранный возраст >= минимума - OK
      // Если у программы нет минимума, но есть максимум и выбранный возраст <= максимума - OK
    }

    $duration_match = true;
    if ($duration > 0) {
      if ($program_duration <= 0 || $program_duration !== $duration) {
        $duration_match = false;
      }
    }

    $date_match = true;
    if ($date_from || $date_to) {
      $program_date_from = isset($program['program_checkin_date_from']) ? (string) $program['program_checkin_date_from'] : '';
      $program_date_to = isset($program['program_checkin_date_to']) ? (string) $program['program_checkin_date_to'] : '';

      if (!$program_date_from) {
        $date_match = false;
      } else {
        $filter_from_ts = $date_from ? strtotime($date_from) : 0;
        $filter_to_ts = $date_to ? strtotime($date_to) : PHP_INT_MAX;
        $program_from_ts = strtotime($program_date_from);
        $program_to_ts = $program_date_to ? strtotime($program_date_to) : $program_from_ts;

        // Проверяем пересечение диапазонов:
        // Фильтр должен пересекаться с диапазоном программы
        // Фильтр попадает в программу, если:
        // - начало фильтра <= конец программы И конец фильтра >= начало программы
        if ($filter_from_ts > $program_to_ts || $filter_to_ts < $program_from_ts) {
          $date_match = false;
        }
      }
    }

    // Фильтр по языку
    $language_match = true;
    if (!empty($language_ids)) {
      $program_languages = [];
      // Получаем языки программы из таксономии education
      $education_languages = wp_get_post_terms($education_id, 'education_language', ['fields' => 'ids']);
      if (!empty($education_languages) && !is_wp_error($education_languages)) {
        $program_languages = $education_languages;
      }
      
      if (empty($program_languages)) {
        $language_match = false;
      } else {
        $language_match = !empty(array_intersect($language_ids, $program_languages));
      }
    }

    if ($age_match && $duration_match && $date_match && $language_match) {
      $filtered_programs[] = $program;
    }
  }

  // Сортировка
  if (!empty($sort) && !empty($filtered_programs)) {
    usort($filtered_programs, function ($a, $b) use ($sort) {
      if ($sort === 'price_asc' || $sort === 'price_desc') {
        $price_a = isset($a['program_price_per_week']) ? (string) $a['program_price_per_week'] : '';
        $price_b = isset($b['program_price_per_week']) ? (string) $b['program_price_per_week'] : '';
        
        // Извлекаем числа из строки цены
        preg_match('/[\d\s]+/', $price_a, $matches_a);
        preg_match('/[\d\s]+/', $price_b, $matches_b);
        
        $num_a = !empty($matches_a[0]) ? (int) str_replace(' ', '', $matches_a[0]) : 0;
        $num_b = !empty($matches_b[0]) ? (int) str_replace(' ', '', $matches_b[0]) : 0;
        
        if ($sort === 'price_asc') {
          return $num_a <=> $num_b;
        } else {
          return $num_b <=> $num_a;
        }
      } elseif ($sort === 'age_asc' || $sort === 'age_desc') {
        $age_a = isset($a['program_age_min']) ? (int) $a['program_age_min'] : 0;
        $age_b = isset($b['program_age_min']) ? (int) $b['program_age_min'] : 0;
        
        if ($sort === 'age_asc') {
          return $age_a <=> $age_b;
        } else {
          return $age_b <=> $age_a;
        }
      }
      
      return 0;
    });
  }

  // Собираем опции для фильтров из всех программ (не только отфильтрованных)
  $filter_options = [
    'ages' => [],
    'durations' => [],
  ];

  foreach ($programs as $program) {
    $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
    $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
    $program_duration = isset($program['program_duration']) ? (int) $program['program_duration'] : 0;

    // Добавляем все возраста от min до max в список доступных
    if ($program_age_min > 0) {
      $max_age = $program_age_max > 0 ? $program_age_max : $program_age_min;
      for ($age = $program_age_min; $age <= $max_age; $age++) {
        if (!in_array($age, $filter_options['ages'], true)) {
          $filter_options['ages'][] = $age;
        }
      }
    }
    
    if ($program_duration > 0 && !in_array($program_duration, $filter_options['durations'], true)) {
      $filter_options['durations'][] = $program_duration;
    }
  }

  sort($filter_options['ages']);
  sort($filter_options['durations']);

  ob_start();
  if (!empty($filtered_programs)) {
    foreach ($filtered_programs as $index => $program) {
      set_query_var('program', $program);
      set_query_var('program_index', $index);
      set_query_var('booking_url', $booking_url);
      get_template_part('template-parts/education/program-card');
    }
  } else {
    echo '<div class="education-programs__empty">Программы не найдены.</div>';
  }

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total' => count($filtered_programs),
    'filter_options' => $filter_options,
  ]);
}

add_action('wp_ajax_education_programs_filter_options', 'bsi_ajax_education_programs_filter_options');
add_action('wp_ajax_nopriv_education_programs_filter_options', 'bsi_ajax_education_programs_filter_options');

function bsi_ajax_education_programs_filter_options(): void
{
  $education_id = isset($_POST['education_id']) ? absint(wp_unslash($_POST['education_id'])) : 0;
  if (!$education_id) {
    wp_send_json_error(['message' => 'no_education_id']);
  }

  $programs = function_exists('get_field') ? get_field('education_programs', $education_id) : [];
  $programs = is_array($programs) ? $programs : [];

  $filter_options = [
    'ages' => [],
    'durations' => [],
  ];

  foreach ($programs as $program) {
    $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
    $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
    $program_duration = isset($program['program_duration']) ? (int) $program['program_duration'] : 0;

    // Добавляем все возраста от min до max в список доступных
    if ($program_age_min > 0) {
      $max_age = $program_age_max > 0 ? $program_age_max : $program_age_min;
      for ($age = $program_age_min; $age <= $max_age; $age++) {
        if (!in_array($age, $filter_options['ages'], true)) {
          $filter_options['ages'][] = $age;
        }
      }
    }
    
    if ($program_duration > 0 && !in_array($program_duration, $filter_options['durations'], true)) {
      $filter_options['durations'][] = $program_duration;
    }
  }

  sort($filter_options['ages']);
  sort($filter_options['durations']);

  wp_send_json_success($filter_options);
}

