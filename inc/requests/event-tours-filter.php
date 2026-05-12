<?php

/**
 * Событийные туры: фильтры и фасеты (AJAX).
 */

if (!function_exists('bsi_event_tours_parse_request_filters')) {
  /**
   * @return array{country_id:int,region_id:int,tour_type_id:int,resort_id:int,search:string,date_from:string,date_to:string,paged:int}
   */
  function bsi_event_tours_parse_request_filters(): array
  {
    return [
      'country_id' => isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0,
      'region_id' => isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0,
      'tour_type_id' => isset($_POST['tour_type']) ? absint(wp_unslash($_POST['tour_type'])) : 0,
      'resort_id' => isset($_POST['resort']) ? absint(wp_unslash($_POST['resort'])) : 0,
      'search' => isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '',
      'date_from' => isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '',
      'date_to' => isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '',
      'paged' => isset($_POST['paged']) ? max(1, absint(wp_unslash($_POST['paged']))) : 1,
    ];
  }
}

if (!function_exists('bsi_event_tours_filter_ids_by_event_dates')) {
  /**
   * @param int[] $post_ids
   * @return int[]
   */
  function bsi_event_tours_filter_ids_by_event_dates(array $post_ids, string $date_from, string $date_to): array
  {
    if ($date_from === '' || $date_to === '' || empty($post_ids)) {
      return [];
    }
    $from = strtotime($date_from);
    $to = strtotime($date_to);
    if (!$from || !$to) {
      return [];
    }
    $out = [];
    foreach ($post_ids as $post_id) {
      $post_id = (int) $post_id;
      $event_dates = function_exists('get_field') ? get_field('event_dates', $post_id) : [];
      if (empty($event_dates) || !is_array($event_dates)) {
        continue;
      }
      foreach ($event_dates as $row) {
        $d = isset($row['date_value']) ? $row['date_value'] : '';
        if (!$d) {
          continue;
        }
        $ts = strtotime($d);
        if ($ts >= $from && $ts <= $to) {
          $out[] = $post_id;
          break;
        }
      }
    }
    return $out;
  }
}

if (!function_exists('bsi_event_tours_build_query_args')) {
  /**
   * @param array $f   bsi_event_tours_parse_request_filters()
   * @param array $omit skip_country?, skip_resort?, skip_tour_type?
   */
  function bsi_event_tours_build_query_args(array $f, array $omit = []): array
  {
    $tax_query = [];

    if (!empty($f['region_id'])) {
      $tax_query[] = [
        'taxonomy' => 'region',
        'field' => 'term_id',
        'terms' => [(int) $f['region_id']],
        'include_children' => true,
      ];
    }

    if (!empty($f['tour_type_id']) && empty($omit['skip_tour_type'])) {
      $tax_query[] = [
        'taxonomy' => 'tour_type',
        'field' => 'term_id',
        'terms' => [(int) $f['tour_type_id']],
      ];
    }

    if (!empty($f['resort_id']) && empty($omit['skip_resort'])) {
      $tax_query[] = [
        'taxonomy' => 'resort',
        'field' => 'term_id',
        'terms' => [(int) $f['resort_id']],
      ];
    }

    $meta_query = [];
    if (!empty($f['country_id']) && empty($omit['skip_country'])) {
      $meta_query[] = [
        'key' => 'tour_country',
        'value' => (int) $f['country_id'],
        'compare' => '=',
      ];
    }

    $args = [
      'post_type' => 'event',
      'post_status' => 'publish',
      'orderby' => 'title',
      'order' => 'ASC',
      'no_found_rows' => true,
    ];

    if (!empty($tax_query)) {
      $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
    }

    if (!empty($meta_query)) {
      $args['meta_query'] = array_merge([['relation' => 'AND']], $meta_query);
    }

    if ($f['search'] !== '') {
      $args['s'] = $f['search'];
    }

    return $args;
  }
}

if (!function_exists('bsi_event_tours_get_matching_post_ids')) {
  /**
   * @param array $omit skip_country?, skip_resort?, skip_tour_type?
   * @return int[]
   */
  function bsi_event_tours_get_matching_post_ids(array $f, array $omit = [], bool $apply_date_range = false): array
  {
    $args = bsi_event_tours_build_query_args($f, $omit);
    $args['posts_per_page'] = -1;
    $args['fields'] = 'ids';

    $q = new WP_Query($args);
    $ids = array_map('intval', $q->posts);
    wp_reset_postdata();

    if ($apply_date_range && $f['date_from'] !== '' && $f['date_to'] !== '') {
      $ids = bsi_event_tours_filter_ids_by_event_dates($ids, $f['date_from'], $f['date_to']);
    }

    return $ids;
  }
}

add_action('wp_ajax_event_tours_filter', 'event_tours_filter');
add_action('wp_ajax_nopriv_event_tours_filter', 'event_tours_filter');

function event_tours_filter()
{
  $f = bsi_event_tours_parse_request_filters();
  $per_page = 12;

  $has_date_filter = ($f['date_from'] !== '' && $f['date_to'] !== '');
  $ids = bsi_event_tours_get_matching_post_ids($f, [], $has_date_filter);

  $total = count($ids);
  $max_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;
  $paged = $max_pages > 0 ? min($f['paged'], $max_pages) : 1;

  $slice = array_slice($ids, ($paged - 1) * $per_page, $per_page);

  ob_start();
  if (!empty($slice)) {
    $q2 = new WP_Query([
      'post_type' => 'event',
      'post__in' => $slice,
      'orderby' => 'post__in',
      'posts_per_page' => count($slice),
      'post_status' => 'publish',
    ]);
    while ($q2->have_posts()) {
      $q2->the_post();
      get_template_part('template-parts/event/card-row', null, ['post_id' => get_the_ID()]);
    }
    wp_reset_postdata();
  } else {
    echo '<div class="country-tours__empty">События не найдены.</div>';
  }

  wp_send_json_success([
    'html' => ob_get_clean(),
    'total' => $total,
    'max_pages' => $max_pages,
    'paged' => $paged,
  ]);
}


add_action('wp_ajax_event_tours_regions', 'event_tours_regions');
add_action('wp_ajax_nopriv_event_tours_regions', 'event_tours_regions');

function event_tours_regions()
{
  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;

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
  $f = bsi_event_tours_parse_request_filters();
  $has_date_filter = ($f['date_from'] !== '' && $f['date_to'] !== '');

  $ids = bsi_event_tours_get_matching_post_ids($f, ['skip_country' => true], $has_date_filter);

  $country_ids = [];
  foreach ($ids as $tour_id) {
    $country_val = function_exists('get_field') ? get_field('tour_country', $tour_id) : null;
    if (!$country_val) {
      continue;
    }
    if (is_array($country_val)) {
      $country_ids = array_merge($country_ids, array_map('intval', $country_val));
    } elseif (is_numeric($country_val)) {
      $country_ids[] = (int) $country_val;
    } elseif ($country_val instanceof WP_Post) {
      $country_ids[] = (int) $country_val->ID;
    }
  }

  $country_ids = array_values(array_unique(array_filter($country_ids)));

  if (empty($country_ids)) {
    wp_send_json_success(['items' => []]);
  }

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
  $f = bsi_event_tours_parse_request_filters();
  $f['date_from'] = '';
  $f['date_to'] = '';

  $tour_ids = bsi_event_tours_get_matching_post_ids($f, [], false);

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


add_action('wp_ajax_event_tours_facets', 'event_tours_facets');
add_action('wp_ajax_nopriv_event_tours_facets', 'event_tours_facets');

function event_tours_facets()
{
  $f = bsi_event_tours_parse_request_filters();
  $has_date_filter = ($f['date_from'] !== '' && $f['date_to'] !== '');

  $ids_resorts = bsi_event_tours_get_matching_post_ids($f, ['skip_resort' => true], $has_date_filter);
  $resort_items = [];
  if (!empty($ids_resorts)) {
    $terms = get_terms([
      'taxonomy' => 'resort',
      'object_ids' => $ids_resorts,
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($terms) && !empty($terms)) {
      foreach ($terms as $t) {
        $resort_items[] = [
          'id' => (int) $t->term_id,
          'text' => (string) $t->name,
        ];
      }
    }
  }

  $ids_types = bsi_event_tours_get_matching_post_ids($f, ['skip_tour_type' => true], $has_date_filter);
  $type_items = [];
  if (!empty($ids_types)) {
    $terms = get_terms([
      'taxonomy' => 'tour_type',
      'object_ids' => $ids_types,
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
    ]);
    if (!is_wp_error($terms) && !empty($terms)) {
      foreach ($terms as $t) {
        $type_items[] = [
          'id' => (int) $t->term_id,
          'text' => (string) $t->name,
        ];
      }
    }
  }

  wp_send_json_success([
    'resorts' => $resort_items,
    'tour_types' => $type_items,
  ]);
}

/**
 * GET country|region|resort|tour_type совпадают с public query_var CPT/таксономий —
 * при обновлении страницы «Событийные туры» ломается главный запрос.
 * Фильтры в URL: префикс et_* (см. js/modules/ajax/event-tours.js).
 */
add_filter('request', function (array $query_vars): array {
  $pagename = isset($query_vars['pagename']) ? (string) $query_vars['pagename'] : '';
  if ($pagename === '') {
    return $query_vars;
  }
  $is_event_tours = $pagename === 'sobytiynye-tury'
    || (strlen($pagename) > 16 && substr($pagename, -16) === '/sobytiynye-tury');
  if (!$is_event_tours) {
    return $query_vars;
  }
  foreach (['country', 'region', 'resort', 'tour_type'] as $conflict_key) {
    unset($query_vars[$conflict_key]);
  }

  return $query_vars;
}, 5);
