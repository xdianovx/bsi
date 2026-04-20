<?php

add_action('wp_ajax_tours_filter', 'bsi_ajax_tours_filter');
add_action('wp_ajax_nopriv_tours_filter', 'bsi_ajax_tours_filter');

function bsi_ajax_tours_filter()
{
  $country_id = isset($_POST['country']) ? absint(wp_unslash($_POST['country'])) : 0;
  $region_id = isset($_POST['region']) ? absint(wp_unslash($_POST['region'])) : 0;
  $resort_id = isset($_POST['resort']) ? absint(wp_unslash($_POST['resort'])) : 0;
  $tour_type_id = isset($_POST['tour_type']) ? absint(wp_unslash($_POST['tour_type'])) : 0;

  // Нормализуем поисковый запрос для лучшего UX
  $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
  if (!empty($search)) {
    // Обрезаем пробелы слева и справа
    $search = trim($search);
    // Заменяем множественные пробелы на один
    $search = preg_replace('/\s+/', ' ', $search);
  }

  $price_min = isset($_POST['price_min']) ? absint(wp_unslash($_POST['price_min'])) : 0;
  $price_max = isset($_POST['price_max']) ? absint(wp_unslash($_POST['price_max'])) : 0;
  $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
  $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';
  $sort = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'price_asc';
  $per_page = isset($_POST['per_page']) ? absint(wp_unslash($_POST['per_page'])) : 12;
  $paged = isset($_POST['paged']) ? max(1, absint(wp_unslash($_POST['paged']))) : 1;
  $view = isset($_POST['view']) ? sanitize_text_field(wp_unslash($_POST['view'])) : 'grid';

  // Валидация per_page
  $per_page = in_array($per_page, [12, 24, 48], true) ? $per_page : 12;

  $tax_query = [];
  $meta_query = [];

  // Фильтр по региону
  if ($region_id) {
    $tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
    ];
  }

  // Фильтр по курорту
  if ($resort_id) {
    $tax_query[] = [
      'taxonomy' => 'resort',
      'field' => 'term_id',
      'terms' => [$resort_id],
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

  // Фильтр по стране
  if ($country_id) {
    $country_meta_query = function_exists('bsi_build_tour_country_meta_query')
      ? bsi_build_tour_country_meta_query((int) $country_id)
      : [];
    if (!empty($country_meta_query)) {
      $meta_query[] = $country_meta_query;
    }
  }

  // Базовый запрос - получаем все туры для сортировки и фильтрации
  $args = [
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
  ];

  if (!empty($tax_query)) {
    $args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
  }

  if (!empty($meta_query)) {
    $args['meta_query'] = array_merge([['relation' => 'AND']], $meta_query);
  }

  // Примечание: поиск будет сделан в PHP после загрузки постов (см. ниже)
  // Это нужно для корректной работы с кириллицей и case-insensitivity

  $query = new WP_Query($args);

  // Массив отфильтрованных постов с дополнительными данными
  $filtered_posts = [];

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      $post_id = (int) get_the_ID();

      // Дополнительная фильтрация по цене (если нужна)
      $price_from = '';
      if (function_exists('get_field')) {
        $price_from = get_field('price_from', $post_id);
      }

      $price_num = function_exists('bsi_get_tour_sort_price')
        ? bsi_get_tour_sort_price($post_id)
        : null;

      // Проверяем диапазон цены
      if ($price_min > 0 && ($price_num === null || $price_num < $price_min)) {
        continue;
      }
      if ($price_max > 0 && ($price_num === null || $price_num > $price_max)) {
        continue;
      }

      // Дополнительная фильтрация по датам (если нужна)
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

      // Дополнительная фильтрация по поиску (title и route)
      if ($search && function_exists('get_field')) {
        $tour_route = (string) get_field('tour_route', $post_id);
        $title = (string) get_the_title($post_id);

        // Используем mb_stripos для регистронезависимого поиска с правильной работой кириллицы
        // mb_stripos уже делает case-insensitive сравнение, не нужно предварительно преобразовывать в нижний регистр
        if (mb_stripos($title, $search, 0, 'UTF-8') === false &&
            mb_stripos($tour_route, $search, 0, 'UTF-8') === false) {
          continue;
        }
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

  // Готовим HTML результатов
  ob_start();
  if ($final_query->have_posts()) {
    while ($final_query->have_posts()) {
      $final_query->the_post();

      // Собираем данные для карточки тура
      $tour_id = (int) get_the_ID();
      $country_id_tour = 0;
      if (function_exists('get_field')) {
        $country_val = get_field('tour_country', $tour_id);
        if ($country_val instanceof WP_Post) {
          $country_id_tour = (int) $country_val->ID;
        } elseif (is_array($country_val)) {
          $country_id_tour = (int) reset($country_val);
        } else {
          $country_id_tour = (int) $country_val;
        }
      }

      $country_title = $country_id_tour ? get_the_title($country_id_tour) : '';
      $flag_url = '';
      if ($country_id_tour && function_exists('get_field')) {
        $flag_field = get_field('flag', $country_id_tour);
        if ($flag_field) {
          if (is_array($flag_field) && !empty($flag_field['url'])) {
            $flag_url = (string) $flag_field['url'];
          } elseif (is_string($flag_field)) {
            $flag_url = (string) $flag_field;
          }
        }
      }

      $tour_data = [
        'id' => $tour_id,
        'url' => get_permalink($tour_id),
        'title' => get_the_title($tour_id),
        'flag' => $flag_url,
        'country_title' => $country_title,
        'country_id' => $country_id_tour,
      ];

      set_query_var('tour', $tour_data);
      // Используем разные шаблоны для grid и list view
      if ($view === 'list') {
        get_template_part('template-parts/tour/card-row', null, ['post_id' => $tour_id]);
      } else {
        get_template_part('template-parts/tour/card');
      }
    }
  } else {
    echo '<div class="tours-page__empty">Туры не найдены.</div>';
  }
  wp_reset_postdata();

  $html = ob_get_clean();

  // Готовим данные для обновления фильтров опций
  $filter_options = bsi_get_tours_filter_options($country_id, $region_id, $tour_type_id, $resort_id);

  wp_send_json_success([
    'html' => $html,
    'total' => (int) $total_filtered,
    'pages' => (int) $max_pages,
    'filter_options' => $filter_options,
  ]);
}

/**
 * Получает доступные опции фильтров для туров
 */
function bsi_get_tours_filter_options($country_id = 0, $region_id = 0, $tour_type_id = 0, $resort_id = 0)
{
  $options = [
    'countries' => [],
    'regions' => [],
    'resorts' => [],
    'tour_types' => [],
  ];

  // Страны: всегда полный список стран с турами в стабильном RU-порядке.
  $countries = function_exists('bsi_get_tour_countries_sorted')
    ? bsi_get_tour_countries_sorted()
    : [];

  foreach ($countries as $country) {
    $options['countries'][] = [
      'id' => (int) $country->ID,
      'name' => $country->post_title,
    ];
  }

  // Регионы - получаем все или фильтруем по стране
  $region_args = [
    'taxonomy' => 'region',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
  ];

  if ($country_id) {
    $region_args['meta_query'] = [
      [
        'key' => 'region_country',
        'value' => $country_id,
        'compare' => '=',
      ],
    ];
  }

  $regions = get_terms($region_args);
  if ($country_id && (is_wp_error($regions) || empty($regions))) {
    unset($region_args['meta_query']);
    $regions = get_terms($region_args);
  }
  if (!is_wp_error($regions) && !empty($regions)) {
    foreach ($regions as $region) {
      // Считаем кол-во туров с этим регионом + другие фильтры
      $count_args = [
        'post_type' => 'tour',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [
          [
            'taxonomy' => 'region',
            'field' => 'term_id',
            'terms' => [$region->term_id],
            'include_children' => true,
          ],
        ],
      ];

      // Добавляем фильтры по стране, курорту, типу если они выбраны
      $additional_tax_query = [];
      $additional_meta_query = [];

      if ($resort_id) {
        $additional_tax_query[] = [
          'taxonomy' => 'resort',
          'field' => 'term_id',
          'terms' => [$resort_id],
        ];
      }
      if ($tour_type_id) {
        $additional_tax_query[] = [
          'taxonomy' => 'tour_type',
          'field' => 'term_id',
          'terms' => [$tour_type_id],
        ];
      }
      if ($country_id) {
        $country_meta_query = function_exists('bsi_build_tour_country_meta_query')
          ? bsi_build_tour_country_meta_query((int) $country_id)
          : [];
        if (!empty($country_meta_query)) {
          $additional_meta_query[] = $country_meta_query;
        }
      }

      if (!empty($additional_tax_query)) {
        $count_args['tax_query'] = array_merge(
          [['relation' => 'AND']],
          $count_args['tax_query'],
          $additional_tax_query
        );
      }

      if (!empty($additional_meta_query)) {
        $count_args['meta_query'] = array_merge([['relation' => 'AND']], $additional_meta_query);
      }

      $count_query = new WP_Query($count_args);
      $region_count = $count_query->found_posts;
      wp_reset_postdata();

      // Только добавляем регион если есть туры
      if ($region_count > 0) {
        $options['regions'][] = [
          'id' => (int) $region->term_id,
          'name' => $region->name,
          'count' => $region_count,
        ];
      }
    }
  }

  // Курорты - получаем по регионам или стране
  $resort_args = [
    'taxonomy' => 'resort',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
  ];

  if ($region_id) {
    $resort_args['meta_query'] = [
      [
        'key' => 'resort_region',
        'value' => $region_id,
        'compare' => '=',
      ],
    ];
  } elseif ($country_id) {
    // Если выбрана страна но не регион - получаем все курорты страны через регионы
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

    if (!empty($region_ids)) {
      $resort_args['meta_query'] = [
        [
          'key' => 'resort_region',
          'value' => $region_ids,
          'compare' => 'IN',
        ],
      ];
    }
  }

  $resorts = get_terms($resort_args);
  if (($region_id || $country_id) && (is_wp_error($resorts) || empty($resorts))) {
    unset($resort_args['meta_query']);
    $resorts = get_terms($resort_args);
  }
  if (!is_wp_error($resorts) && !empty($resorts)) {
    foreach ($resorts as $resort) {
      // Считаем кол-во туров с этим курортом + другие фильтры
      $count_args = [
        'post_type' => 'tour',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [
          [
            'taxonomy' => 'resort',
            'field' => 'term_id',
            'terms' => [$resort->term_id],
          ],
        ],
      ];

      // Добавляем фильтры по стране, региону, типу если они выбраны
      $additional_tax_query = [];
      $additional_meta_query = [];

      if ($region_id) {
        $additional_tax_query[] = [
          'taxonomy' => 'region',
          'field' => 'term_id',
          'terms' => [$region_id],
          'include_children' => true,
        ];
      }
      if ($tour_type_id) {
        $additional_tax_query[] = [
          'taxonomy' => 'tour_type',
          'field' => 'term_id',
          'terms' => [$tour_type_id],
        ];
      }
      if ($country_id) {
        $country_meta_query = function_exists('bsi_build_tour_country_meta_query')
          ? bsi_build_tour_country_meta_query((int) $country_id)
          : [];
        if (!empty($country_meta_query)) {
          $additional_meta_query[] = $country_meta_query;
        }
      }

      if (!empty($additional_tax_query)) {
        $count_args['tax_query'] = array_merge(
          [['relation' => 'AND']],
          $count_args['tax_query'],
          $additional_tax_query
        );
      }

      if (!empty($additional_meta_query)) {
        $count_args['meta_query'] = array_merge([['relation' => 'AND']], $additional_meta_query);
      }

      $count_query = new WP_Query($count_args);
      $resort_count = $count_query->found_posts;
      wp_reset_postdata();

      // Только добавляем курорт если есть туры
      if ($resort_count > 0) {
        $options['resorts'][] = [
          'id' => (int) $resort->term_id,
          'name' => $resort->name,
          'count' => $resort_count,
        ];
      }
    }
  }

  // Типы туров - получаем только те, что есть у туров в текущей выборке (по стране/региону/курорту)
  $tour_types_ids = [];
  $tour_types_counts = [];

  // Строим локальные фильтры из параметров функции (без tour_type_id — иначе при выборе типа пропадают остальные)
  $local_tax_query = [];
  $local_meta_query = [];

  if ($region_id) {
    $local_tax_query[] = [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => [$region_id],
      'include_children' => true,
    ];
  }
  if ($resort_id) {
    $local_tax_query[] = [
      'taxonomy' => 'resort',
      'field' => 'term_id',
      'terms' => [$resort_id],
    ];
  }
  if ($country_id) {
    $country_meta_query = function_exists('bsi_build_tour_country_meta_query')
      ? bsi_build_tour_country_meta_query((int) $country_id)
      : [];
    if (!empty($country_meta_query)) {
      $local_meta_query[] = $country_meta_query;
    }
  }

  $type_query_args = [
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ];

  if (!empty($local_tax_query)) {
    $type_query_args['tax_query'] = array_merge([['relation' => 'AND']], $local_tax_query);
  }
  if (!empty($local_meta_query)) {
    $type_query_args['meta_query'] = array_merge([['relation' => 'AND']], $local_meta_query);
  }

  $type_query = new WP_Query($type_query_args);
  if ($type_query->have_posts()) {
    foreach ($type_query->posts as $post_id) {
      $types = wp_get_post_terms($post_id, 'tour_type', ['fields' => 'ids']);
      $tour_types_ids = array_merge($tour_types_ids, $types);
    }
  }
  wp_reset_postdata();

  $tour_types_ids = array_values(array_unique($tour_types_ids));

  // Считаем кол-во туров для каждого типа
  if (!empty($tour_types_ids)) {
    foreach ($tour_types_ids as $type_id) {
      $count_args = [
        'post_type' => 'tour',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [
          [
            'taxonomy' => 'tour_type',
            'field' => 'term_id',
            'terms' => [$type_id],
          ],
        ],
      ];

      // Добавляем другие фильтры
      $additional_tax_query = [];
      $additional_meta_query = [];

      if ($region_id) {
        $additional_tax_query[] = [
          'taxonomy' => 'region',
          'field' => 'term_id',
          'terms' => [$region_id],
          'include_children' => true,
        ];
      }
      if ($resort_id) {
        $additional_tax_query[] = [
          'taxonomy' => 'resort',
          'field' => 'term_id',
          'terms' => [$resort_id],
        ];
      }
      if ($country_id) {
        $country_meta_query = function_exists('bsi_build_tour_country_meta_query')
          ? bsi_build_tour_country_meta_query((int) $country_id)
          : [];
        if (!empty($country_meta_query)) {
          $additional_meta_query[] = $country_meta_query;
        }
      }

      if (!empty($additional_tax_query)) {
        $count_args['tax_query'] = array_merge(
          [['relation' => 'AND']],
          $count_args['tax_query'],
          $additional_tax_query
        );
      }

      if (!empty($additional_meta_query)) {
        $count_args['meta_query'] = array_merge([['relation' => 'AND']], $additional_meta_query);
      }

      $count_query = new WP_Query($count_args);
      $tour_types_counts[$type_id] = $count_query->found_posts;
      wp_reset_postdata();
    }
  }

  $tour_type_args = [
    'taxonomy' => 'tour_type',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
  ];

  // Включаем только типы которые есть в результатах фильтрации
  if (!empty($tour_types_ids)) {
    $tour_type_args['include'] = $tour_types_ids;
  } else {
    // Если нет результатов - не показываем никакие типы туров
    $tour_type_args['include'] = [0];
  }

  $tour_types = get_terms($tour_type_args);

  if (!is_wp_error($tour_types) && !empty($tour_types)) {
    foreach ($tour_types as $type) {
      // Только показываем тип если есть туры с этим типом
      $type_count = isset($tour_types_counts[$type->term_id]) ? $tour_types_counts[$type->term_id] : 0;
      if ($type_count > 0) {
        $options['tour_types'][] = [
          'id' => (int) $type->term_id,
          'name' => $type->name,
          'count' => $type_count,
        ];
      }
    }
  }

  return $options;
}
