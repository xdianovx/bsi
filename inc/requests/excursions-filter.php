<?php
/**
 * AJAX-фильтр каталога экскурсий страны: /country/{slug}/ekskursii/
 *
 * Endpoints:
 *  - excursions_filter   — фильтрация + пагинация + сортировка списка
 *  - excursions_resorts  — обновление списка курортов по выбранному региону
 *
 * Клиент: js/modules/ajax/excursions.js (initExcursionsFilter).
 */

add_action('wp_ajax_excursions_filter', 'bsi_excursions_filter');
add_action('wp_ajax_nopriv_excursions_filter', 'bsi_excursions_filter');

function bsi_excursions_filter()
{
  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
  if (!$country_id) {
    wp_send_json_error(['message' => 'no_country']);
  }

  $region    = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;
  $resort    = isset($_POST['resort']) ? absint(wp_unslash($_POST['resort'])) : 0;
  $type      = isset($_POST['excursion_type']) ? absint(wp_unslash($_POST['excursion_type'])) : 0;
  $language  = isset($_POST['excursion_language']) ? absint(wp_unslash($_POST['excursion_language'])) : 0;

  $paged     = isset($_POST['paged']) ? max(1, absint(wp_unslash($_POST['paged']))) : 1;
  $sort      = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'price_asc';

  $tax_query = [];
  if ($region) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region],
      'include_children' => true,
    ];
  }
  if ($resort) {
    $tax_query[] = ['taxonomy' => 'resort', 'field' => 'term_id', 'terms' => [$resort]];
  }
  if ($type) {
    $tax_query[] = ['taxonomy' => 'excursion_type', 'field' => 'term_id', 'terms' => [$type], 'include_children' => true];
  }
  if ($language) {
    $tax_query[] = ['taxonomy' => 'excursion_language', 'field' => 'term_id', 'terms' => [$language]];
  }

  $args = [
    'post_type'              => 'excursion',
    'post_status'            => 'publish',
    'posts_per_page'         => -1,
    'fields'                 => 'ids',
    'no_found_rows'          => true,
    'bsi_skip_schedule'      => true,
    'update_post_meta_cache' => false,
    'meta_query'             => [
      ['key' => 'excursion_country', 'value' => $country_id, 'compare' => '='],
    ],
  ];

  if (!empty($tax_query)) {
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  $matching_ids = get_posts($args);
  if (!is_array($matching_ids)) {
    $matching_ids = [];
  }

  $filtered = [];
  foreach ($matching_ids as $eid) {
    $eid = (int) $eid;
    if ($eid <= 0) {
      continue;
    }

    $price_rub = function_exists('bsi_get_excursion_price_from_rub') ? bsi_get_excursion_price_from_rub($eid) : null;

    $filtered[] = [
      'id'        => $eid,
      'title'     => (string) get_the_title($eid),
      'price_num' => $price_rub !== null ? (int) $price_rub : null,
    ];
  }

  usort($filtered, function ($a, $b) use ($sort) {
    $a_price = $a['price_num'];
    $b_price = $b['price_num'];
    $a_has = $a_price !== null && $a_price > 0;
    $b_has = $b_price !== null && $b_price > 0;

    switch ($sort) {
      case 'price_desc':
        if (!$a_has && !$b_has) {
          return strcmp($a['title'], $b['title']);
        }
        if (!$a_has) return 1;
        if (!$b_has) return -1;
        return (int) $b_price <=> (int) $a_price;
      case 'title_desc':
        return strcmp($b['title'], $a['title']);
      case 'title_asc':
        return strcmp($a['title'], $b['title']);
      case 'price_asc':
      default:
        if (!$a_has && !$b_has) {
          return strcmp($a['title'], $b['title']);
        }
        if (!$a_has) return 1;
        if (!$b_has) return -1;
        return (int) $a_price <=> (int) $b_price;
    }
  });

  $per_page = 12;
  $total = count($filtered);
  $max_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 0;
  if ($max_pages > 0 && $paged > $max_pages) {
    $paged = $max_pages;
  }

  $offset = ($paged - 1) * $per_page;
  $page_slice = array_slice($filtered, $offset, $per_page);
  $page_ids = array_column($page_slice, 'id');

  if (!empty($page_ids)) {
    $final = new WP_Query([
      'post_type'              => 'excursion',
      'post_status'            => 'publish',
      'posts_per_page'         => $per_page,
      'post__in'               => $page_ids,
      'orderby'                => 'post__in',
      'no_found_rows'          => true,
      'bsi_skip_schedule'      => true,
    ]);
  } else {
    $final = new WP_Query([
      'post_type'      => 'excursion',
      'post_status'    => 'publish',
      'posts_per_page' => 1,
      'post__in'       => [0],
    ]);
  }

  ob_start();
  if ($final->have_posts()) {
    while ($final->have_posts()) {
      $final->the_post();
      get_template_part('template-parts/excursion/card-row', null, ['post_id' => get_the_ID()]);
    }
  } else {
    echo '<div class="country-excursions__empty">Ничего не найдено по выбранным фильтрам.</div>';
  }
  wp_reset_postdata();
  $html = ob_get_clean();

  ob_start();
  if ($max_pages > 1) {
    echo paginate_links([
      'total'     => $max_pages,
      'current'   => $paged,
      'prev_text' => '&larr; Назад',
      'next_text' => 'Вперед &rarr;',
      'mid_size'  => 2,
    ]);
  }
  $pagination = ob_get_clean();

  wp_send_json_success([
    'html'       => $html,
    'total'      => (int) $total,
    'pagination' => $pagination,
  ]);
}

add_action('wp_ajax_excursions_resorts', 'bsi_excursions_resorts');
add_action('wp_ajax_nopriv_excursions_resorts', 'bsi_excursions_resorts');

function bsi_excursions_resorts()
{
  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
  $region_id  = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;

  if (!$country_id) {
    wp_send_json_error(['message' => 'no_country']);
  }

  if ($region_id) {
    $terms = get_terms([
      'taxonomy' => 'resort',
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
      'meta_query' => [
        ['key' => 'resort_region', 'value' => $region_id, 'compare' => '='],
      ],
    ]);
  } else {
    $region_ids = get_terms([
      'taxonomy' => 'region',
      'hide_empty' => false,
      'fields' => 'ids',
      'meta_query' => [
        ['key' => 'region_country', 'value' => $country_id, 'compare' => '='],
      ],
    ]);
    $region_ids = is_array($region_ids) ? array_values(array_filter(array_map('absint', $region_ids))) : [];

    if (empty($region_ids)) {
      wp_send_json_success(['items' => []]);
    }

    $terms = get_terms([
      'taxonomy' => 'resort',
      'hide_empty' => false,
      'orderby' => 'name',
      'order' => 'ASC',
      'meta_query' => [
        ['key' => 'resort_region', 'value' => $region_ids, 'compare' => 'IN'],
      ],
    ]);
  }

  if (is_wp_error($terms) || empty($terms)) {
    wp_send_json_success(['items' => []]);
  }

  $items = [];
  foreach ($terms as $t) {
    $items[] = ['id' => (int) $t->term_id, 'text' => (string) $t->name];
  }

  wp_send_json_success(['items' => $items]);
}
