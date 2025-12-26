<?php

if (!defined('ABSPATH')) {
  exit;
}

add_action('wp_ajax_education_filter', 'bsi_ajax_education_filter');
add_action('wp_ajax_nopriv_education_filter', 'bsi_ajax_education_filter');

function bsi_ajax_education_filter(): void
{
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

  $country_id = isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0;

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

  $paged = isset($_POST['paged']) ? absint(wp_unslash($_POST['paged'])) : 1;
  $per_page = 12;
  $sort = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'title_asc';

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

      if ($age_min > 0 || $age_max > 0 || $duration_min > 0 || $duration_max > 0 || $date_from || $date_to) {
        $school_programs = function_exists('get_field') ? get_field('education_programs', $education_id) : [];
        $school_programs = is_array($school_programs) ? $school_programs : [];

        foreach ($school_programs as $program) {
          $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
          $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
          $program_duration_min = isset($program['program_duration_min']) ? (int) $program['program_duration_min'] : 0;
          $program_duration_max = isset($program['program_duration_max']) ? (int) $program['program_duration_max'] : 0;

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
            if ($duration_min > 0 && $program_duration_max > 0 && $duration_min > $program_duration_max) {
              $duration_match = false;
            }
            if ($duration_max > 0 && $program_duration_min > 0 && $duration_max < $program_duration_min) {
              $duration_match = false;
            }
          }

          $date_match = true;
          if ($date_from || $date_to) {
            $program_dates = $program['program_checkin_dates'] ?? [];
            $program_dates = is_array($program_dates) ? $program_dates : [];

            if (empty($program_dates)) {
              $date_match = false;
            } else {
              $has_date_in_range = false;
              foreach ($program_dates as $date_item) {
                $checkin_date = is_array($date_item) ? ($date_item['checkin_date'] ?? '') : '';
                if ($checkin_date) {
                  $date_ts = strtotime($checkin_date);
                  if ($date_from && strtotime($date_from) > $date_ts) {
                    continue;
                  }
                  if ($date_to && strtotime($date_to) < $date_ts) {
                    continue;
                  }
                  $has_date_in_range = true;
                  break;
                }
              }
              $date_match = $has_date_in_range;
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
      'html' => '<div class="education-archive__empty">Школы не найдены.</div>',
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
    usort($posts, function($a, $b) use ($sort) {
      $price_a = function_exists('get_field') ? get_field('education_price', $a->ID) : '';
      $price_b = function_exists('get_field') ? get_field('education_price', $b->ID) : '';
      
      // Извлекаем числа из строк цен
      preg_match('/[\d\s]+/', (string)$price_a, $matches_a);
      preg_match('/[\d\s]+/', (string)$price_b, $matches_b);
      
      $num_a = isset($matches_a[0]) ? (int)str_replace(' ', '', $matches_a[0]) : 0;
      $num_b = isset($matches_b[0]) ? (int)str_replace(' ', '', $matches_b[0]) : 0;
      
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
      echo '<div class="education-archive__item">';
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
          $program_duration_min = isset($program['program_duration_min']) ? (int) $program['program_duration_min'] : 0;
          $program_duration_max = isset($program['program_duration_max']) ? (int) $program['program_duration_max'] : 0;

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
            if ($duration_min > 0 && $program_duration_max > 0 && $duration_min > $program_duration_max) {
              $duration_match = false;
            }
            if ($duration_max > 0 && $program_duration_min > 0 && $duration_max < $program_duration_min) {
              $duration_match = false;
            }
          }

          $date_match = true;
          if ($date_from || $date_to) {
            $program_dates = $program['program_checkin_dates'] ?? [];
            $program_dates = is_array($program_dates) ? $program_dates : [];

            if (empty($program_dates)) {
              $date_match = false;
            } else {
              $has_date_in_range = false;
              foreach ($program_dates as $date_item) {
                $checkin_date = is_array($date_item) ? ($date_item['checkin_date'] ?? '') : '';
                if ($checkin_date) {
                  $date_ts = strtotime($checkin_date);
                  if ($date_from && strtotime($date_from) > $date_ts) {
                    continue;
                  }
                  if ($date_to && strtotime($date_to) < $date_ts) {
                    continue;
                  }
                  $has_date_in_range = true;
                  break;
                }
              }
              $date_match = $has_date_in_range;
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

  $age_min = isset($_POST['program_age_min']) ? absint(wp_unslash($_POST['program_age_min'])) : 0;
  $age_max = isset($_POST['program_age_max']) ? absint(wp_unslash($_POST['program_age_max'])) : 0;
  $duration_min = isset($_POST['program_duration_min']) ? absint(wp_unslash($_POST['program_duration_min'])) : 0;
  $duration_max = isset($_POST['program_duration_max']) ? absint(wp_unslash($_POST['program_duration_max'])) : 0;
  $date = isset($_POST['program_date']) ? sanitize_text_field(wp_unslash($_POST['program_date'])) : '';

  $programs = function_exists('get_field') ? get_field('education_programs', $education_id) : [];
  $programs = is_array($programs) ? $programs : [];

  $filtered_programs = [];

  foreach ($programs as $program) {
    $program_age_min = isset($program['program_age_min']) ? (int) $program['program_age_min'] : 0;
    $program_age_max = isset($program['program_age_max']) ? (int) $program['program_age_max'] : 0;
    $program_duration_min = isset($program['program_duration_min']) ? (int) $program['program_duration_min'] : 0;
    $program_duration_max = isset($program['program_duration_max']) ? (int) $program['program_duration_max'] : 0;

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
      if ($duration_min > 0 && $program_duration_max > 0 && $duration_min > $program_duration_max) {
        $duration_match = false;
      }
      if ($duration_max > 0 && $program_duration_min > 0 && $duration_max < $program_duration_min) {
        $duration_match = false;
      }
    }

    $date_match = true;
    if ($date) {
      $program_dates = $program['program_checkin_dates'] ?? [];
      $program_dates = is_array($program_dates) ? $program_dates : [];

      if (empty($program_dates)) {
        $date_match = false;
      } else {
        $has_date_match = false;
        $date_ts = strtotime($date);
        foreach ($program_dates as $date_item) {
          $checkin_date = is_array($date_item) ? ($date_item['checkin_date'] ?? '') : '';
          if ($checkin_date && strtotime($checkin_date) >= $date_ts) {
            $has_date_match = true;
            break;
          }
        }
        $date_match = $has_date_match;
      }
    }

    if ($age_match && $duration_match && $date_match) {
      $filtered_programs[] = $program;
    }
  }

  ob_start();
  if (!empty($filtered_programs)) {
    foreach ($filtered_programs as $program) {
      echo '<div class="single-education__program-item">';
      set_query_var('program', $program);
      get_template_part('template-parts/education/program-card');
      echo '</div>';
    }
  } else {
    echo '<div class="education-programs__empty">Программы не найдены.</div>';
  }

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total' => count($filtered_programs),
  ]);
}

