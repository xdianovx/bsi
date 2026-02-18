<?php

add_action('wp_ajax_country_tours_filter', 'country_tours_filter');
add_action('wp_ajax_nopriv_country_tours_filter', 'country_tours_filter');

function country_tours_filter()
{

  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
  if (!$country_id) {
    wp_send_json_error(['message' => 'no_country']);
  }

  $region = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;

  // Важно: из JS ты шлешь resort[] и tour_type[] -> в PHP это придет как $_POST['resort'] и $_POST['tour_type'] (массивы)
  $resorts = [];
  if (isset($_POST['resort'])) {
    $raw = (array) wp_unslash($_POST['resort']);
    $resorts = array_values(array_filter(array_map('absint', $raw)));
  }

  $types = [];
  if (isset($_POST['tour_type'])) {
    $raw = (array) wp_unslash($_POST['tour_type']);
    $types = array_values(array_filter(array_map('absint', $raw)));
  }

  $paged = isset($_POST['paged']) ? max(1, absint(wp_unslash($_POST['paged']))) : 1;

  $tax_query = [];

  if ($region) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region],
      'include_children' => true,
    ];
  }

  if (!empty($resorts)) {
    $tax_query[] = [
      'taxonomy' => 'resort',
      'field' => 'term_id',
      'terms' => $resorts,
    ];
  }

  if (!empty($types)) {
    $tax_query[] = [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => $types,
    ];
  }

  $args = [
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => [
      [
        'key' => 'tour_country',
        'value' => $country_id,
        'compare' => '=',
      ],
    ],
  ];

  // НЕ ставим tax_query => null (это частая причина “странных” поломок)
  if (!empty($tax_query)) {
    // если несколько фильтров — по умолчанию AND, но можно явно:
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  $q = new WP_Query($args);

  ob_start();
  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      get_template_part('template-parts/tour/card-row', null, ['post_id' => get_the_ID()]);
    }
  }
  wp_reset_postdata();
  $html = ob_get_clean();

  // Генерируем пагинацию
  ob_start();
  if ($q->max_num_pages > 1) {
    echo paginate_links([
      'total'   => $q->max_num_pages,
      'current' => $paged,
      'prev_text' => '&larr; Назад',
      'next_text' => 'Вперед &rarr;',
      'mid_size' => 2,
    ]);
  }
  $pagination = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total' => (int) $q->found_posts,
    'pagination' => $pagination,
  ]);
}


add_action('wp_ajax_country_tours_resorts', 'country_tours_resorts');
add_action('wp_ajax_nopriv_country_tours_resorts', 'country_tours_resorts');

function country_tours_resorts()
{

  $country_id = isset($_POST['country_id']) ? absint(wp_unslash($_POST['country_id'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;

  if (!$country_id) {
    wp_send_json_error(['message' => 'no_country']);
  }

  // если выбран регион — курорты по нему
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

  } else {

    // иначе: все курорты страны через регионы страны
    $region_ids = get_terms([
      'taxonomy' => 'region',
      'hide_empty' => false,
      'fields' => 'ids',
      'meta_query' => [
        [
          'key' => 'region_country',
          'value' => $country_id,
          'compare' => '=',
        ],
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
        [
          'key' => 'resort_region',
          'value' => $region_ids,
          'compare' => 'IN',
        ],
      ],
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