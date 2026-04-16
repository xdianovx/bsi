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
  $sort = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'price_asc';
  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

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
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => bsi_build_tour_country_meta_query((int) $country_id),
  ];

  // НЕ ставим tax_query => null (это частая причина “странных” поломок)
  if (!empty($tax_query)) {
    // если несколько фильтров — по умолчанию AND, но можно явно:
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  $q = new WP_Query($args);

  // Массив отфильтрованных постов
  $filtered_posts = [];

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();
      $post_id = (int) get_the_ID();

      $price_from = function_exists('get_field') ? get_field('price_from', $post_id) : '';
      $price_num = function_exists('bsi_get_tour_sort_price')
        ? bsi_get_tour_sort_price($post_id)
        : null;

      // Проверяем диапазон дат (если фильтр активен)
      $dates_match = true;
      if ($date_from && $date_to && function_exists('get_field')) {
        $checkin_dates_str = get_field('tour_checkin_dates', $post_id);
        if ($checkin_dates_str) {
          $checkin_dates = array_filter(array_map('trim', preg_split('/[,;\n]/', $checkin_dates_str)));
          $has_matching_date = false;

          foreach ($checkin_dates as $date_str) {
            // Пытаемся распарсить дату в формате YYYY-MM-DD или DD.MM.YYYY
            $date_obj = DateTime::createFromFormat('Y-m-d', $date_str);
            if (!$date_obj) {
              $date_obj = DateTime::createFromFormat('d.m.Y', $date_str);
            }

            if ($date_obj) {
              $date_str_normalized = $date_obj->format('Y-m-d');
              if ($date_str_normalized >= $date_from && $date_str_normalized <= $date_to) {
                $has_matching_date = true;
                break;
              }
            }
          }

          if (!$has_matching_date) {
            $dates_match = false;
          }
        } else {
          // Если дат нет и фильтр по датам активен - пропускаем
          $dates_match = false;
        }
      }

      if (!$dates_match) {
        continue;
      }

      // Собираем данные тура для сортировки
      $tour_data = [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'price_num' => $price_num,
        'price_from' => $price_from,
      ];

      $filtered_posts[] = $tour_data;
    }
    wp_reset_postdata();
  }

  // Сортировка
  usort($filtered_posts, function ($a, $b) use ($sort) {
    switch ($sort) {
      case 'price_asc':
        return function_exists('bsi_compare_price_values')
          ? bsi_compare_price_values($a['price_num'], $b['price_num'], 'price_asc')
          : ((int) $a['price_num'] <=> (int) $b['price_num']);
      case 'price_desc':
        return function_exists('bsi_compare_price_values')
          ? bsi_compare_price_values($a['price_num'], $b['price_num'], 'price_desc')
          : ((int) $b['price_num'] <=> (int) $a['price_num']);
      case 'title_desc':
        return strcmp($b['title'], $a['title']);
      case 'title_asc':
      default:
        return strcmp($a['title'], $b['title']);
    }
  });

  // Пагинация
  $per_page = 12;
  $total_filtered = count($filtered_posts);
  $max_pages = (int) ceil($total_filtered / $per_page);
  $offset = ($paged - 1) * $per_page;
  $paginated_posts = array_slice($filtered_posts, $offset, $per_page);
  $paginated_ids = array_column($paginated_posts, 'id');

  // Если есть отфильтрованные посты, загружаем их с сохранением порядка
  if (!empty($paginated_ids)) {
    $final_args = [
      'post_type' => 'tour',
      'post_status' => 'publish',
      'posts_per_page' => $per_page,
      'post__in' => $paginated_ids,
      'orderby' => 'post__in',
    ];
    $final_query = new WP_Query($final_args);
  } else {
    $final_query = new WP_Query([
      'post_type' => 'tour',
      'post_status' => 'publish',
      'posts_per_page' => $per_page,
      'post__in' => [0], // Пусто результаты
    ]);
  }

  ob_start();
  if ($final_query->have_posts()) {
    while ($final_query->have_posts()) {
      $final_query->the_post();
      get_template_part('template-parts/tour/card-row', null, ['post_id' => get_the_ID()]);
    }
  }
  wp_reset_postdata();
  $html = ob_get_clean();

  // Генерируем пагинацию
  ob_start();
  if ($max_pages > 1) {
    echo paginate_links([
      'total'   => $max_pages,
      'current' => $paged,
      'prev_text' => '&larr; Назад',
      'next_text' => 'Вперед &rarr;',
      'mid_size' => 2,
    ]);
  }
  $pagination = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'total' => (int) $total_filtered,
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