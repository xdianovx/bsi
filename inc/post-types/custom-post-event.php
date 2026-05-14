<?php

// Добавляем event в таксономии region и resort
add_filter('region_taxonomy_post_types', function ($types) {
  $types[] = 'event';
  return array_values(array_unique($types));
}, 5);

add_filter('resort_taxonomy_post_types', function ($types) {
  $types[] = 'event';
  return array_values(array_unique($types));
}, 5);

// Регистрация CPT Event для событийных туров
add_action('init', function () {
  register_post_type('event', [
    'labels' => [
      'name' => 'Событийные туры',
      'singular_name' => 'Событийный тур',
      'add_new' => 'Добавить событийный тур',
      'add_new_item' => 'Новый событийный тур',
      'edit_item' => 'Редактировать событийный тур',
      'new_item' => 'Новый событийный тур',
      'view_item' => 'Смотреть событийный тур',
      'search_items' => 'Искать событийные туры',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Событийные туры',
    ],
    'description' => 'Событийные туры',
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 24,
    'menu_icon' => 'dashicons-calendar-alt',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
    'taxonomies' => ['region', 'resort', 'tour_type', 'tour_include'],
    'has_archive' => false,
    'rewrite' => ['slug' => 'event-tours', 'with_front' => false],
    'query_var' => true,
  ]);
}, 10);

// Сортировка по menu_order в админке
add_action('pre_get_posts', function ($query) {
  if (is_admin() && $query->is_main_query()) {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'event' && $screen->id === 'edit-event') {
      $query->set('orderby', 'menu_order');
      $query->set('order', 'ASC');
    }
  }
});

// Привязка таксономий к CPT event
add_action('init', function () {
  if (taxonomy_exists('region')) {
    register_taxonomy_for_object_type('region', 'event');
  }
  if (taxonomy_exists('resort')) {
    register_taxonomy_for_object_type('resort', 'event');
  }
  if (taxonomy_exists('tour_type')) {
    register_taxonomy_for_object_type('tour_type', 'event');
  }
  if (taxonomy_exists('tour_include')) {
    register_taxonomy_for_object_type('tour_include', 'event');
  }
}, 30);

// ACF поля для событийных туров
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  // ГЕО
  acf_add_local_field_group([
    'key' => 'group_event_geo',
    'title' => 'Событийный тур — ГЕО',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_event_country',
        'label' => 'Страна',
        'name' => 'tour_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'wrapper' => ['width' => '100'],
        'instructions' => 'Выберите страну события. Курорты выбираются через таксономию "Курорты" справа.',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'event',
        ],
      ],
    ],
  ]);

  // Основная информация
  acf_add_local_field_group([
    'key' => 'group_event_main',
    'title' => 'Событийный тур — Основная информация',
    'position' => 'normal',
    'menu_order' => 30,
    'fields' => [
      [
        'key' => 'field_event_is_popular',
        'label' => 'Популярный',
        'name' => 'is_popular',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'instructions' => 'Если выбрано, тур отобразится в слайдере популярных событийных туров',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_event_price_from',
        'label' => 'Цена от',
        'name' => 'price_from',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: 50000 или от 50 000 руб',
        'instructions' => 'Цена в рублях (можно указать текст)',
      ],
      [
        'key' => 'field_event_nights',
        'label' => 'Количество ночей',
        'name' => 'tour_nights',
        'type' => 'number',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: 7',
        'instructions' => 'Количество ночей в туре',
      ],

      [
        'key' => 'field_event_dates',
        'label' => 'Даты проведения',
        'name' => 'event_dates',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить дату',
        'wrapper' => ['width' => '50'],
        'instructions' => 'Даты для каталога и таблицы «Даты и места» на странице события.',
        'sub_fields' => [
          [
            'key' => 'field_event_date_value',
            'label' => 'Дата',
            'name' => 'date_value',
            'type' => 'date_picker',
            'display_format' => 'd.m.Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'required' => 1,
          ],
          [
            'key' => 'field_event_date_city',
            'label' => 'Город',
            'name' => 'date_city',
            'type' => 'text',
            'wrapper' => ['width' => '25'],
            'placeholder' => 'Москва',
          ],
          [
            'key' => 'field_event_date_venue',
            'label' => 'Площадка',
            'name' => 'date_venue',
            'type' => 'text',
            'wrapper' => ['width' => '25'],
            'placeholder' => 'Стадион',
          ],
          [
            'key' => 'field_event_date_row_price',
            'label' => 'Цена от (₽)',
            'name' => 'date_row_price',
            'type' => 'number',
            'wrapper' => ['width' => '15'],
            'instructions' => 'Опционально; если пусто — мин. цена билетов поста.',
          ],
          [
            'key' => 'field_event_date_ticket_index',
            'label' => 'Индекс билета',
            'name' => 'date_ticket_index',
            'type' => 'number',
            'wrapper' => ['width' => '15'],
            'instructions' => '0 = первый тип билета в блоке «Билеты» для кнопки «Забронировать».',
            'min' => 0,
            'step' => 1,
          ],
        ],
      ],
      [
        'key' => 'field_event_hero_extra_tag',
        'label' => 'Доп. тег в шапке',
        'name' => 'event_hero_extra_tag',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: Групповой тур',
        'instructions' => 'Необязательный тег рядом с датой и типами события.',
      ],
      [
        'key' => 'field_event_venue',
        'label' => 'Место проведения',
        'name' => 'event_venue',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: Стадион Лужники',
        'instructions' => 'Название площадки или места проведения события',
      ],
      [
        'key' => 'field_event_time',
        'label' => 'Время проведения',
        'name' => 'event_time',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: 19:00',
        'instructions' => 'Время начала события',
      ],
      [
        'key' => 'field_event_tour_transport',
        'label' => 'Транспорт',
        'name' => 'tour_transport',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: Авиа, Поезд',
        'instructions' => 'Для блока кратких характеристик на странице тура',
      ],
      [
        'key' => 'field_event_venue_scheme',
        'label' => 'Схема зала / рассадки',
        'name' => 'venue_scheme',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'library' => 'all',
        'wrapper' => ['width' => '100'],
        'instructions' => 'Статичное изображение схемы мест (при необходимости замените на интерактив позже)',
      ],
      [
        'key' => 'field_event_venue_scheme_legend',
        'label' => 'Легенда к схеме (сектор — цена)',
        'name' => 'venue_scheme_legend',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить строку',
        'sub_fields' => [
          [
            'key' => 'field_event_legend_label',
            'label' => 'Сектор / зона',
            'name' => 'legend_label',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_event_legend_price',
            'label' => 'Цена',
            'name' => 'legend_price',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
            'placeholder' => 'от 50 000 ₽',
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'event',
        ],
      ],
    ],
  ]);

  // Билеты
  acf_add_local_field_group([
    'key' => 'group_event_tickets',
    'title' => 'Событийный тур — Билеты',
    'position' => 'normal',
    'menu_order' => 40,
    'fields' => [
      [
        'key' => 'field_event_tickets',
        'label' => 'Типы билетов',
        'name' => 'event_tickets',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить тип билета',
        'sub_fields' => [
          [
            'key' => 'field_event_ticket_type',
            'label' => 'Название типа билета',
            'name' => 'ticket_type',
            'type' => 'text',
            'wrapper' => ['width' => '30'],
            'placeholder' => 'VIP, Стандарт, Эконом',
            'required' => 1,
          ],
          [
            'key' => 'field_event_ticket_price',
            'label' => 'Цена билета (руб)',
            'name' => 'ticket_price',
            'type' => 'number',
            'wrapper' => ['width' => '20'],
            'placeholder' => '50000',
            'required' => 1,
          ],

          [
            'key' => 'field_event_ticket_description',
            'label' => 'Описание билета',
            'name' => 'ticket_description',
            'type' => 'textarea',
            'wrapper' => ['width' => '30'],
            'rows' => 3,
            'placeholder' => 'Что входит в этот тип билета',
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'event',
        ],
      ],
    ],
  ]);

  // Галерея
  acf_add_local_field_group([
    'key' => 'group_event_media',
    'title' => 'Событийный тур — Галерея',
    'position' => 'normal',
    'menu_order' => 10,
    'fields' => [
      [
        'key' => 'field_event_gallery',
        'label' => 'Галерея события',
        'name' => 'tour_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 30,
        'wrapper' => ['width' => '100'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'event',
        ],
      ],
    ],
  ]);

  // Бронирование
  acf_add_local_field_group([
    'key' => 'group_event_booking',
    'title' => 'Событийный тур — Бронирование',
    'position' => 'normal',
    'menu_order' => 20,
    'fields' => [
      [
        'key' => 'field_event_booking_url',
        'label' => 'Ссылка для бронирования',
        'name' => 'tour_booking_url',
        'type' => 'url',
        'wrapper' => ['width' => '100'],
        'placeholder' => 'https://...',
        'instructions' => 'Обычная ссылка для бронирования или ссылка на поиск экскурсии из Самотура.',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'event',
        ],
      ],
    ],
  ]);

  // Включено/Не включено
  acf_add_local_field_group([
    'key' => 'group_event_included',
    'title' => 'Событийный тур — Включено/Не включено',
    'position' => 'normal',
    'menu_order' => 70,
    'fields' => [
      [
        'key' => 'field_event_included',
        'label' => 'В стоимость включено',
        'name' => 'tour_included',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_event_not_included',
        'label' => 'В стоимость не включено',
        'name' => 'tour_not_included',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_event_extra',
        'label' => 'Дополнительно',
        'name' => 'tour_extra',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'event',
        ],
      ],
    ],
  ]);

  // FAQ
  acf_add_local_field_group([
    'key' => 'group_event_faq',
    'title' => 'Событийный тур — FAQ',
    'position' => 'normal',
    'menu_order' => 80,
    'fields' => [
      [
        'key' => 'field_event_faq',
        'label' => 'Вопросы и ответы',
        'name' => 'event_faq',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить вопрос',
        'sub_fields' => [
          [
            'key' => 'field_event_faq_question',
            'label' => 'Вопрос',
            'name' => 'faq_question',
            'type' => 'text',
            'required' => 1,
          ],
          [
            'key' => 'field_event_faq_answer',
            'label' => 'Ответ',
            'name' => 'faq_answer',
            'type' => 'textarea',
            'rows' => 5,
            'new_lines' => 'wpautop',
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'event',
        ],
      ],
    ],
  ]);
});

// Фильтр для ACF поля tour_country - показывать только родительские страны
add_filter('acf/fields/post_object/query/key=field_event_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);

// Breadcrumbs для Yoast SEO
add_filter('wpseo_breadcrumb_links', function ($links) {
  // Одиночный событийный тур
  if (is_singular('event')) {
    $event_id = get_queried_object_id();
    if (!$event_id)
      return $links;

    // Находим страницу "Событийные туры"
    $event_tours_page = get_page_by_path('sobytiynye-tury');
    $event_tours_url = $event_tours_page ? get_permalink($event_tours_page->ID) : home_url('/sobytiynye-tury/');

    $new = [];
    $new[] = ['url' => home_url('/'), 'text' => 'Главная'];
    $new[] = ['url' => $event_tours_url, 'text' => 'Событийные туры'];
    $new[] = ['text' => get_the_title($event_id)];

    return $new;
  }

  return $links;
});
