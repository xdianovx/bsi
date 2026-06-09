<?php

/**
 * Событийные туры: фильтры и фасеты (AJAX).
 */

if (!defined('BSI_EVENT_TOURS_CATALOG_PER_PAGE')) {
  define('BSI_EVENT_TOURS_CATALOG_PER_PAGE', 12);
}

if (!function_exists('bsi_event_tours_prime_meta_for_ids')) {
  /**
   * Один-два запроса к wp_postmeta вместо тысячи при последующих get_field().
   *
   * @param int[] $ids
   */
  function bsi_event_tours_prime_meta_for_ids(array $ids): void
  {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if ($ids === []) {
      return;
    }
    foreach (array_chunk($ids, 500) as $chunk) {
      update_postmeta_cache($chunk);
    }
  }
}

if (!function_exists('bsi_event_tours_parse_request_filters')) {
  /**
   * @return array{country_id:int,region_id:int,tour_type_id:int,resort_id:int,search:string,date_from:string,date_to:string,paged:int,sort:string,view:string}
   */
  function bsi_event_tours_parse_request_filters(): array
  {
    $sort = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'date_asc';
    if (!in_array($sort, ['date_asc', 'date_desc', 'title_asc', 'title_desc', 'price_asc', 'price_desc'], true)) {
      $sort = 'date_asc';
    }
    $view = isset($_POST['view']) ? sanitize_text_field(wp_unslash($_POST['view'])) : 'tiles';
    if (!in_array($view, ['tiles', 'list'], true)) {
      $view = 'tiles';
    }

    return [
      'country_id' => isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0,
      'region_id' => isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0,
      'tour_type_id' => isset($_POST['tour_type']) ? absint(wp_unslash($_POST['tour_type'])) : 0,
      'resort_id' => isset($_POST['resort']) ? absint(wp_unslash($_POST['resort'])) : 0,
      'search' => isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '',
      'date_from' => isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '',
      'date_to' => isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '',
      'paged' => isset($_POST['paged']) ? max(1, absint(wp_unslash($_POST['paged']))) : 1,
      'sort' => $sort,
      'view' => $view,
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

    bsi_event_tours_prime_meta_for_ids($post_ids);

    $out = [];
    foreach ($post_ids as $post_id) {
      $post_id = (int) $post_id;
      $event_dates = function_exists('get_field') ? get_field('event_dates', $post_id) : [];
      $all_dates = [];
      if (!empty($event_dates) && is_array($event_dates)) {
        foreach ($event_dates as $row) {
          $d = isset($row['date_value']) ? $row['date_value'] : '';
          if ($d) {
            $all_dates[] = (string) $d;
          }
        }
      }
      $hero_d = function_exists('get_field') ? get_field('event_hero_date', $post_id) : '';
      if (is_string($hero_d) && $hero_d !== '') {
        $all_dates[] = $hero_d;
      }
      $all_dates = array_values(array_unique($all_dates));
      if (empty($all_dates)) {
        continue;
      }
      foreach ($all_dates as $d) {
        $ts = strtotime($d);
        if ($ts && $ts >= $from && $ts <= $to) {
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
        'taxonomy' => BSI_EVENT_TOUR_TYPE_TAXONOMY,
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

    return bsi_query_args_append_schedule($args);
  }
}

if (!function_exists('bsi_event_tours_get_matching_post_ids')) {
  /**
   * @param array $omit skip_country?, skip_resort?, skip_tour_type?
   * @return int[]
   */
  function bsi_event_tours_get_matching_post_ids(array $f, array $omit = [], bool $apply_date_range = false): array
  {
    $omit_normalized = $omit;
    ksort($omit_normalized);
    static $memo = [];
    $memo_key = md5(wp_json_encode([
      'f' => $f,
      'omit' => $omit_normalized,
      'apply_date_range' => $apply_date_range,
    ]));
    if (isset($memo[$memo_key])) {
      return $memo[$memo_key];
    }

    $args = bsi_event_tours_build_query_args($f, $omit);
    $args['posts_per_page'] = -1;
    $args['fields'] = 'ids';

    $q = new WP_Query($args);
    $ids = array_map('intval', $q->posts);
    wp_reset_postdata();

    if ($apply_date_range && $f['date_from'] !== '' && $f['date_to'] !== '') {
      $ids = bsi_event_tours_filter_ids_by_event_dates($ids, $f['date_from'], $f['date_to']);
    }

    $memo[$memo_key] = $ids;

    return $ids;
  }
}

if (!function_exists('bsi_event_tours_nearest_ts')) {
  /**
   * Ближайшая (предстоящая) дата события как timestamp.
   * Берём минимальную дату >= сегодня из event_dates / event_hero_date.
   * Если все даты в прошлом — возвращаем PHP_INT_MAX (такие уходят в конец списка «ближайшие»).
   */
  function bsi_event_tours_nearest_ts(int $post_id): int
  {
    $today = strtotime(date('Y-m-d', current_time('timestamp')));

    $all = [];
    $event_dates = function_exists('get_field') ? get_field('event_dates', $post_id) : [];
    if (!empty($event_dates) && is_array($event_dates)) {
      foreach ($event_dates as $row) {
        $d = isset($row['date_value']) ? $row['date_value'] : '';
        if ($d) {
          $ts = strtotime((string) $d);
          if ($ts) {
            $all[] = $ts;
          }
        }
      }
    }
    $hero_d = function_exists('get_field') ? get_field('event_hero_date', $post_id) : '';
    if (is_string($hero_d) && $hero_d !== '') {
      $ts = strtotime($hero_d);
      if ($ts) {
        $all[] = $ts;
      }
    }

    if (empty($all)) {
      return PHP_INT_MAX;
    }

    $upcoming = array_filter($all, static function ($ts) use ($today) {
      return $ts >= $today;
    });
    if (!empty($upcoming)) {
      return min($upcoming);
    }

    return PHP_INT_MAX;
  }
}

if (!function_exists('bsi_event_tours_sort_ids')) {
  /**
   * @param int[] $ids уже в порядке title ASC
   * @return int[]
   */
  function bsi_event_tours_sort_ids(array $ids, string $sort): array
  {
    if (empty($ids)) {
      return $ids;
    }

    if ($sort === 'title_asc') {
      return $ids;
    }
    if ($sort === 'title_desc') {
      return array_reverse($ids);
    }

    if ($sort === 'date_asc' || $sort === 'date_desc') {
      bsi_event_tours_prime_meta_for_ids($ids);
      $rows = [];
      foreach ($ids as $i => $id) {
        $rows[] = ['id' => (int) $id, 'ts' => bsi_event_tours_nearest_ts((int) $id), 'pos' => $i];
      }
      $desc = ($sort === 'date_desc');
      usort($rows, static function ($a, $b) use ($desc) {
        // События без предстоящих дат (PHP_INT_MAX) — всегда в конце, независимо от направления.
        $an = $a['ts'] === PHP_INT_MAX;
        $bn = $b['ts'] === PHP_INT_MAX;
        if ($an !== $bn) {
          return $an ? 1 : -1;
        }
        $cmp = $a['ts'] <=> $b['ts'];
        if ($cmp === 0) {
          return $a['pos'] <=> $b['pos'];
        }
        return $desc ? -$cmp : $cmp;
      });
      return array_map(static function ($r) {
        return $r['id'];
      }, $rows);
    }

    // price_asc / price_desc
    bsi_event_tours_prime_meta_for_ids($ids);
    $rows = [];
    foreach ($ids as $i => $id) {
      $price = function_exists('bsi_event_card_price') ? bsi_event_card_price((int) $id) : ['rub' => null];
      $rub = isset($price['rub']) && $price['rub'] !== null ? (int) $price['rub'] : null;
      $rows[] = ['id' => (int) $id, 'rub' => $rub, 'pos' => $i];
    }

    $desc = ($sort === 'price_desc');
    usort($rows, static function ($a, $b) use ($desc) {
      // Без цены — всегда в конце.
      $an = $a['rub'] === null;
      $bn = $b['rub'] === null;
      if ($an !== $bn) {
        return $an ? 1 : -1;
      }
      if ($an && $bn) {
        return $a['pos'] <=> $b['pos'];
      }
      $cmp = $a['rub'] <=> $b['rub'];
      if ($cmp === 0) {
        return $a['pos'] <=> $b['pos'];
      }
      return $desc ? -$cmp : $cmp;
    });

    return array_map(static function ($r) {
      return $r['id'];
    }, $rows);
  }
}

add_action('wp_ajax_event_tours_filter', 'event_tours_filter');
add_action('wp_ajax_nopriv_event_tours_filter', 'event_tours_filter');

function event_tours_filter()
{
  $f = bsi_event_tours_parse_request_filters();
  $per_page = (int) BSI_EVENT_TOURS_CATALOG_PER_PAGE;

  // Необязательное переопределение размера страницы (каталог в рамках страны — 4).
  $req_per_page = isset($_POST['per_page']) ? absint(wp_unslash($_POST['per_page'])) : 0;
  if ($req_per_page >= 1 && $req_per_page <= 48) {
    $per_page = $req_per_page;
  }

  $has_date_filter = ($f['date_from'] !== '' && $f['date_to'] !== '');
  $ids = bsi_event_tours_get_matching_post_ids($f, [], $has_date_filter);

  // Сортировка. По умолчанию $ids уже в порядке title ASC (см. build_query_args).
  $ids = bsi_event_tours_sort_ids($ids, $f['sort']);

  $total = count($ids);
  $max_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;
  $paged = $max_pages > 0 ? min($f['paged'], $max_pages) : 1;

  $slice = array_slice($ids, ($paged - 1) * $per_page, $per_page);

  // Одна карточка для обоих видов; горизонтальный (list) — через CSS-модификатор контейнера.
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
      get_template_part('template-parts/event/card', null, ['post_id' => get_the_ID()]);
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

  bsi_event_tours_prime_meta_for_ids($ids);

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

  bsi_event_tours_prime_meta_for_ids($tour_ids);

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
    $hero_d = function_exists('get_field') ? get_field('event_hero_date', $tour_id) : '';
    if (is_string($hero_d) && $hero_d !== '') {
      $all_dates[$hero_d] = true;
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
      'taxonomy' => BSI_EVENT_TOUR_TYPE_TAXONOMY,
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
 * GET country|region|resort|tour_type|event_tour_type совпадают с public query_var CPT/таксономий —
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
  foreach (['country', 'region', 'resort', 'tour_type', 'event_tour_type'] as $conflict_key) {
    unset($query_vars[$conflict_key]);
  }

  return $query_vars;
}, 5);
