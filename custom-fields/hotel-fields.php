<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  /* Группа: ГЕО */
  acf_add_local_field_group([
    'key' => 'group_hotel_geo',
    'title' => 'ГЕО',
    'fields' => [
      [
        'key' => 'field_hotel_geo_notice',
        'label' => '',
        'name' => 'hotel_geo_notice',
        'type' => 'message',
        'message' => 'Сначала выбираем страну в выпадающем списке, затем нажимаем на кнопку "Загрузить регионы". После загрузки регионов выбираем регион и нажимаем на кнопку "Загрузить курорты". После загрузки курортов выбираем нужный курорт. 
        
        <strong>Важно:</strong> Сейчас прижется выбирать регионы и курорты из неотфильтрованного списка, я это устраню, но позже, пока придется так. 😭
        
         <strong>Видео инструкция:</strong> тут будет ссылка на видео, которое научит вас заполнять отель правильно. ',

        'new_lines' => 'wpautop',
        'esc_html' => 0,
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_hotel_country',
        'label' => 'Страна',
        'name' => 'hotel_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'required' => 1,
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_hotel_region',
        'label' => 'Регион',
        'name' => 'hotel_region',
        'type' => 'taxonomy',
        'taxonomy' => 'region',
        'field_type' => 'select',
        'return_format' => 'id',
        'add_term' => 0,
        'save_terms' => 1,
        'load_terms' => 1,
        'allow_null' => 1,
        'multiple' => 0,
        'ui' => 1,
        'ajax' => 0, // ВАЖНО: выключаем ACF ajax
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_hotel_resort',
        'label' => 'Курорт',
        'name' => 'hotel_resort',
        'type' => 'taxonomy',
        'taxonomy' => 'resort',
        'field_type' => 'select',
        'return_format' => 'id',
        'add_term' => 0,
        'save_terms' => 1,
        'load_terms' => 1,
        'allow_null' => 1,
        'multiple' => 0,
        'ui' => 1,
        'ajax' => 0, // ВАЖНО: выключаем ACF ajax
        'conditional_logic' => [
          [
            [
              'field' => 'field_hotel_region',
              'operator' => '!=empty',
            ],
          ],
        ],
        'wrapper' => ['width' => '33'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'hotel',
        ],
      ],
    ],
    'style' => 'seamless',
  ]);

  /* Группа: Основная информация */
  acf_add_local_field_group([
    'key' => 'group_hotel_info',
    'title' => 'Основная информация',
    'fields' => [
      [
        'key' => 'field_hotel_rating',
        'label' => 'Рейтинг',
        'name' => 'rating',
        'type' => 'number',
        'min' => 1,
        'max' => 5,
        'step' => 1,
        'placeholder' => 'от 1 до 5',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_is_featured',
        'label' => 'Популярный?',
        'name' => 'is_popular',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_hotel_address',
        'label' => 'Адрес',
        'name' => 'address',
        'type' => 'text',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_hotel_phone',
        'label' => 'Телефон',
        'name' => 'phone',
        'type' => 'text',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_website',
        'label' => 'Сайт отеля',
        'name' => 'website',
        'type' => 'url',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_price',
        'label' => 'Стоимость',
        'name' => 'price',
        'type' => 'text',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_hotel_gallery',
        'label' => 'Галерея отеля',
        'name' => 'gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 20,
      ],
      [
        'key' => 'field_check_in_time',
        'label' => 'Время заезда',
        'name' => 'check_in_time',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '14:00',
      ],
      [
        'key' => 'field_check_out_time',
        'label' => 'Время выезда',
        'name' => 'check_out_time',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '12:00',
      ],
      [
        'key' => 'field_wifi',
        'label' => 'Wi-Fi',
        'name' => 'wifi',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Бесплатный',
      ],
      [
        'key' => 'field_breakfast',
        'label' => 'Завтрак',
        'name' => 'breakfast',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Включен/Дополнительно',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'hotel',
        ],
      ],
    ],
  ]);
});


/* Ограничение списка стран (только верхний уровень) */
add_filter('acf/fields/post_object/query/key=field_hotel_country', function ($args, $field, $post_id) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 3);


/* AJAX: отдать регионы по стране / курорты по региону */
add_action('wp_ajax_bsi_geo_terms', function () {
  if (!current_user_can('edit_posts')) {
    wp_send_json_error(['message' => 'no_access']);
  }

  $nonce = $_POST['nonce'] ?? '';
  if (!wp_verify_nonce($nonce, 'bsi_geo')) {
    wp_send_json_error(['message' => 'bad_nonce']);
  }

  $taxonomy = sanitize_text_field($_POST['taxonomy'] ?? '');

  if ($taxonomy === 'region') {
    $country_id = sanitize_text_field($_POST['country_id'] ?? '');
    if (!$country_id) {
      wp_send_json_success([]);
    }

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

    if (is_wp_error($terms) || empty($terms)) {
      wp_send_json_success([]);
    }

    $out = array_map(function ($t) {
      return ['id' => $t->term_id, 'text' => $t->name];
    }, $terms);

    wp_send_json_success($out);
  }

  if ($taxonomy === 'resort') {
    $region_id = sanitize_text_field($_POST['region_id'] ?? '');
    if (!$region_id) {
      wp_send_json_success([]);
    }

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

    if (is_wp_error($terms) || empty($terms)) {
      wp_send_json_success([]);
    }

    $out = array_map(function ($t) {
      return ['id' => $t->term_id, 'text' => $t->name];
    }, $terms);

    wp_send_json_success($out);
  }

  wp_send_json_error(['message' => 'bad_taxonomy']);
});

/* Подключение JS в админке отеля + локализация ajaxUrl/nonce */
add_action('admin_enqueue_scripts', function ($hook) {
  if (!in_array($hook, ['post.php', 'post-new.php'], true))
    return;

  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->post_type !== 'hotel')
    return;

  $path = get_template_directory() . '/assets/admin/hotel-geo-cascade.js';
  $ver = file_exists($path) ? filemtime($path) : time();

  wp_enqueue_script(
    'hotel-geo-cascade',
    get_template_directory_uri() . '/assets/admin/hotel-geo-cascade.js',
    ['acf-input'],
    $ver,
    true
  );

  wp_localize_script('hotel-geo-cascade', 'BSI_GEO', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('bsi_geo'),
  ]);
});