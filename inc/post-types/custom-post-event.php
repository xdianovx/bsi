<?php

/**
 * Событийные туры — отдельный CPT `event`.
 *
 * Это не обычные туры (файл tour.php, CPT `tour`, таксономия `tour_type`).
 * Здесь — CPT `event` и таксономия типов (константа BSI_EVENT_TOUR_TYPE_TAXONOMY).
 * Шаблон записи: single-event.php; листинг: страница «Событийные туры».
 */

/**
 * Slug таксономии «тип событийного тура» — только у CPT event (не путать с tour_type у tour).
 */
if (!defined('BSI_EVENT_TOUR_TYPE_TAXONOMY')) {
  define('BSI_EVENT_TOUR_TYPE_TAXONOMY', 'event_tour_type');
}

if (!defined('BSI_EVENT_BOOKING_CTA_LEAD_DEFAULT')) {
  define(
    'BSI_EVENT_BOOKING_CTA_LEAD_DEFAULT',
    'Поездка на концерт или шоу под ключ: билеты, отель и логистика — заявка займёт пару минут, а согласование рассадки, трансферов и нюансов программы возьмёт на себя менеджер: свяжется с вами, подстроит маршрут под даты и бюджет и ответит на вопросы до отъезда.'
  );
}

// Добавляем event в таксономии region и resort
add_filter('region_taxonomy_post_types', function ($types) {
  $types[] = 'event';
  return array_values(array_unique($types));
}, 5);

add_filter('resort_taxonomy_post_types', function ($types) {
  $types[] = 'event';
  return array_values(array_unique($types));
}, 5);

// Тип событийного тура (отдельно от «Типов туров» у tour)
add_action('init', function () {
  register_taxonomy(BSI_EVENT_TOUR_TYPE_TAXONOMY, ['event'], [
    'labels' => [
      'name' => 'Типы событийных туров',
      'singular_name' => 'Тип событийного тура',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип',
      'new_item_name' => 'Новый тип',
      'menu_name' => 'Типы событийных туров',
      'not_found' => 'Не найдено',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => true,
    'rewrite' => false,
    'query_var' => true,
  ]);
}, 9);

// Регистрация CPT `event` (событийный тур) — не путать с CPT `tour` в этом же проекте (см. tour.php)
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
    'supports' => ['title', 'thumbnail', 'excerpt', 'page-attributes'],
    'taxonomies' => ['region', 'resort', BSI_EVENT_TOUR_TYPE_TAXONOMY, 'tour_include'],
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
  if (taxonomy_exists('tour_include')) {
    register_taxonomy_for_object_type('tour_include', 'event');
  }
}, 30);

// ACF поля для событийных туров
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  // Первый экран (как на странице: герой)
  acf_add_local_field_group([
    'key' => 'group_event_hero',
    'title' => 'Первый экран',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_event_hero_cover',
        'label' => 'Обложка',
        'name' => 'event_hero_cover',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
        'instructions' => 'Фон первого экрана (отдельно от миниатюры записи и галереи ниже). Лучше широкое горизонтальное фото. Если не задано — берутся миниатюра и первая картинка галереи.',
      ],
      [
        'key' => 'field_event_hero_cover_mobile',
        'label' => 'Обложка для мобильных',
        'name' => 'event_hero_cover_mobile',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
        'instructions' => 'Вариант фона при ширине экрана не более 550px. Если не загружать — на мобильных останется основная обложка (или запасной кадр из миниатюры/галереи). Если загружена только эта картинка, без основной обложки — она же будет показана на всех ширинах.',
      ],
      [
        'key' => 'field_event_hero_date',
        'label' => 'Дата концерта (первый экран)',
        'name' => 'event_hero_date',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'required' => 0,
        'wrapper' => ['width' => '50'],
        'instructions' => 'Одна дата для блока с тегами в шапке. Полный список дат и площадок — в отдельном метабоксе «Даты и места концертов».',
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

  // Основная информация, menu_order 10 (под колонкой normal — см. bsi_event_acf_metabox_order)
  acf_add_local_field_group([
    'key' => 'group_event_main',
    'title' => 'Основная информация',
    'position' => 'normal',
    'menu_order' => 10,
    'fields' => [
      [
        'key' => 'field_event_about',
        'label' => 'О событии',
        'name' => 'event_about',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 1,
        'delay' => 0,
        'wrapper' => ['width' => '100'],
        'instructions' => 'Текст блока «О событии» на странице события (под заголовком). Основной контент страницы задаётся полями ACF и блоками ниже, отдельного поля «контент записи» нет.',
      ],
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
        'wrapper' => ['width' => '50'],
        'instructions' => 'Страна события. Регион и курорт — таксономии справа.',
      ],
      [
        'key' => 'field_event_price_from',
        'label' => 'Цена от',
        'name' => 'price_from',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например: 50000 или от 50 000 руб',
        'instructions' => 'Цена в рублях (можно указать текст). Если заданы билеты — на сайте может показываться минимум по билетам.',
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
        'key' => 'field_event_widget_phone_primary',
        'label' => 'Телефон в виджете (Москва)',
        'name' => 'event_widget_phone_primary',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '8 (495) 785-55-35',
        'instructions' => 'Отображается в блоке бронирования. Пусто — 8 (495) 785-55-35.',
      ],
      [
        'key' => 'field_event_widget_phone_secondary',
        'label' => 'Телефон в виджете (регионы)',
        'name' => 'event_widget_phone_secondary',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '8 (800) 200-55-35 (из регионов)',
        'instructions' => 'Второй номер в том же блоке. Пусто — 8 (800) 200-55-35 (из регионов).',
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
            'wrapper' => ['width' => '45'],
          ],
          [
            'key' => 'field_event_legend_price',
            'label' => 'Цена (× 1 000 000)',
            'name' => 'legend_price',
            'type' => 'number',
            'wrapper' => ['width' => '35'],
            'min' => 0,
            'step' => 1,
            'placeholder' => '236000000',
            'instructions' => '≥ 1 000 000: на сайте делится на 1 000 000 (например 236000000 → 236). Меньше 1 000 000: без деления. Валюта — поле справа.',
          ],
          [
            'key' => 'field_event_legend_currency',
            'label' => 'Валюта',
            'name' => 'legend_currency',
            'type' => 'text',
            'wrapper' => ['width' => '20'],
            'placeholder' => '$, EUR, ₽',
            'instructions' => 'Суффикс после суммы: $, EUR, ₽ и т.д.',
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

  // Даты и места концертов — отдельный метабокс, menu_order 15 (3-й по счёту: после «Первый экран» и «Основная информация»)
  acf_add_local_field_group([
    'key' => 'group_event_dates_places',
    'title' => 'Даты и места концертов',
    'position' => 'normal',
    'menu_order' => 15,
    'fields' => [
      [
        'key' => 'field_event_dates',
        'label' => 'Даты проведения',
        'name' => 'event_dates',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить дату',
        'wrapper' => ['width' => '100'],
        'instructions' => 'Строки списка на странице события (блок «Даты и места концертов», третий по порядку) и данные для каталога.',
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

  // Галерея и «Билеты» — только фронт: в админке метабоксы скрыты, данные в БД читаются через get_field на сайте.
  if (!is_admin()) {
    acf_add_local_field_group([
      'key' => 'group_event_media',
      'title' => 'Галерея',
      'position' => 'normal',
      'menu_order' => 20,
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

    acf_add_local_field_group([
      'key' => 'group_event_tickets',
      'title' => 'Билеты',
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
  }

  acf_add_local_field_group([
    'key' => 'group_event_booking',
    'title' => 'Бронирование',
    'position' => 'normal',
    'menu_order' => 30,
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
      [
        'key' => 'field_event_booking_cta_lead',
        'label' => 'Текст под заголовком (блок «Забронировать» внизу страницы)',
        'name' => 'event_booking_cta_lead',
        'type' => 'textarea',
        'rows' => 2,
        'wrapper' => ['width' => '100'],
        'default_value' => BSI_EVENT_BOOKING_CTA_LEAD_DEFAULT,
        'placeholder' => BSI_EVENT_BOOKING_CTA_LEAD_DEFAULT,
        'instructions' => 'Короткий текст над формой заявки. Для новых записей подставляется текст по умолчанию; если поле пустое и сохранено — на сайте всё равно показывается этот же текст.',
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

  // Размещение — текст для блока на странице события
  acf_add_local_field_group([
    'key' => 'group_event_accommodation',
    'title' => 'Размещение',
    'position' => 'normal',
    'menu_order' => 40,
    'fields' => [
      [
        'key' => 'field_event_extra',
        'label' => 'Текст',
        'name' => 'tour_extra',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
        'delay' => 0,
        'wrapper' => ['width' => '100'],
        'instructions' => 'Выводится в блоке «Размещение» на странице событийного тура.',
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
    'title' => 'Включено/Не включено',
    'position' => 'normal',
    'menu_order' => 50,
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
    'title' => 'FAQ',
    'position' => 'normal',
    'menu_order' => 60,
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

/**
 * Порядок метабоксов ACF для CPT event: после перетаскивания WP пишет user meta `meta-box-order_event`
 * и перебивает ACF `menu_order`. Здесь заново вшиваем блоки ACF в нужной последовательности.
 *
 * acf_after_title: Первый экран. normal: Основная информация → Даты и места концертов → Бронирование → Размещение → Вкл/Не вкл → FAQ (галерея/билеты — только фронт, в админке нет).
 */
function bsi_event_acf_metabox_order($result, $option, $user)
{
  unset($option, $user);

  if (!is_admin()) {
    return $result;
  }

  global $pagenow;
  if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') {
    return $result;
  }

  $post_type = isset($_GET['post_type']) ? sanitize_key((string) wp_unslash($_GET['post_type'])) : '';
  if ($post_type === '' && !empty($_GET['post'])) {
    $post_type = get_post_type((int) wp_unslash($_GET['post']));
  }
  if ($post_type !== 'event') {
    return $result;
  }

  if (!is_array($result)) {
    $result = [];
  }

  $acf_after_title = ['acf-group_event_hero'];
  $acf_normal = [
    'acf-group_event_main',
    'acf-group_event_dates_places',
    'acf-group_event_booking',
    'acf-group_event_accommodation',
    'acf-group_event_included',
    'acf-group_event_faq',
  ];

  $strip_acf = static function (array $ids): array {
    return array_values(array_filter(array_map('trim', $ids), static function ($id) {
      return $id !== '' && strpos($id, 'acf-group_') !== 0;
    }));
  };

  $parse = static function ($raw): array {
    if ($raw === '' || $raw === null) {
      return [];
    }
    $ids = explode(',', (string) $raw);
    $ids = array_map('trim', $ids);

    return array_values(array_filter($ids, static fn($id) => $id !== ''));
  };

  $merge_acf_after = static function (array $result, array $acf_ids) use ($parse, $strip_acf): string {
    $ids = isset($result['acf_after_title']) ? $parse($result['acf_after_title']) : [];
    $without = $strip_acf($ids);

    return implode(',', array_merge($acf_ids, $without));
  };

  $merge_normal = static function (array $result, array $acf_ids) use ($parse, $strip_acf): string {
    $ids = isset($result['normal']) ? $parse($result['normal']) : [];
    $without = $strip_acf($ids);
    $postdiv = 'postdivrich';
    $idx = array_search($postdiv, $without, true);
    if ($idx === false) {
      return implode(',', array_merge($without, $acf_ids));
    }
    $before = array_slice($without, 0, $idx + 1);
    $after = array_slice($without, $idx + 1);

    return implode(',', array_merge($before, $acf_ids, $after));
  };

  $result['acf_after_title'] = $merge_acf_after($result, $acf_after_title);
  $result['normal'] = $merge_normal($result, $acf_normal);

  return $result;
}

add_filter('get_user_option_meta-box-order_event', 'bsi_event_acf_metabox_order', 10, 3);

/**
 * Однократно: перенос старых tour_type с записей event → event_tour_type (slug и названия).
 */
add_action('admin_init', function () {
  if (!is_admin() || !current_user_can('manage_options')) {
    return;
  }
  if (get_option('bsi_event_tour_type_migrated_v1')) {
    return;
  }
  if (!taxonomy_exists(BSI_EVENT_TOUR_TYPE_TAXONOMY) || !taxonomy_exists('tour_type')) {
    return;
  }
  $events = get_posts([
    'post_type' => 'event',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ]);
  foreach ($events as $post_id) {
    $post_id = (int) $post_id;
    $terms = wp_get_post_terms($post_id, 'tour_type', ['fields' => 'all']);
    if (is_wp_error($terms) || empty($terms)) {
      wp_set_object_terms($post_id, [], 'tour_type');
      continue;
    }
    $new_ids = [];
    foreach ($terms as $term) {
      $exist = get_term_by('slug', $term->slug, BSI_EVENT_TOUR_TYPE_TAXONOMY);
      if ($exist && !is_wp_error($exist)) {
        $new_ids[] = (int) $exist->term_id;
        continue;
      }
      $created = wp_insert_term($term->name, BSI_EVENT_TOUR_TYPE_TAXONOMY, ['slug' => $term->slug]);
      if (!is_wp_error($created)) {
        $new_ids[] = (int) $created['term_id'];
      } else {
        $exist = get_term_by('slug', $term->slug, BSI_EVENT_TOUR_TYPE_TAXONOMY);
        if ($exist && !is_wp_error($exist)) {
          $new_ids[] = (int) $exist->term_id;
        }
      }
    }
    $new_ids = array_values(array_unique(array_filter($new_ids)));
    if (!empty($new_ids)) {
      wp_set_object_terms($post_id, $new_ids, BSI_EVENT_TOUR_TYPE_TAXONOMY, false);
    }
    wp_set_object_terms($post_id, [], 'tour_type');
  }
  update_option('bsi_event_tour_type_migrated_v1', time());
}, 25);

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
