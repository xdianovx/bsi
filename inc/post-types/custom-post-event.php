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
    // excerpt убран из админки (2026-10-08): подзаголовок hero — своё поле, description — по шаблону. Старые значения в БД остаются.
    'supports' => ['title', 'thumbnail', 'page-attributes'],
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
        'wrapper' => ['width' => '100'],
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
        'key' => 'field_event_hero_subtitle',
        'label' => 'Подзаголовок',
        'name' => 'event_hero_subtitle',
        'type' => 'textarea',
        'rows' => 2,
        'maxlength' => 160,
        'new_lines' => '',
        'wrapper' => ['width' => '100'],
        'placeholder' => 'Билеты и тур на концерт 6 и 7 марта',
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
      ],
      [
        'key' => 'field_event_hero_date',
        'label' => 'Дата начала (первый экран)',
        'name' => 'event_hero_date',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'required' => 0,
        'wrapper' => ['width' => '25'],
        'instructions' => 'Дата для блока с тегами в шапке. Полный список — в метабоксе «Даты и места концертов».',
      ],
      [
        'key' => 'field_event_hero_date_end',
        'label' => 'Дата окончания (первый экран)',
        'name' => 'event_hero_date_end',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'required' => 0,
        'wrapper' => ['width' => '25'],
        'instructions' => 'Только для многодневных (напр. этап Формулы-1). Пусто — одна дата. В шапке покажется диапазон «31.05 – 2.06.2026».',
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

  // Куда приходят заявки (решение 14.10 — наверх, сразу после первого экрана; отдельный email на билеты убран —
  // билеты и тур ведёт один человек). Логика — ajax-event-ticket-form.php.
  acf_add_local_field_group([
    'key' => 'group_event_emails',
    'title' => 'Куда приходят заявки',
    'position' => 'normal',
    'menu_order' => 1,
    'fields' => [
      [
        'key' => 'field_event_notify_email',
        'label' => 'Доп. email — копия всех заявок',
        'name' => 'event_notify_email',
        'type' => 'email',
        'placeholder' => 'email@example.com',
        'instructions' => 'Получает копию каждой заявки этого события (билеты, тур, консультация). Общие адреса тоже получают.',
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

  // «О событии» (бывшее «Основное»): тексты — после тура и состава (порядок заполнения 14.10, см. bsi_event_acf_metabox_order).
  // Скрытые из админки поля этой группы — см. bsi_event_hidden_field_keys().
  acf_add_local_field_group([
    'key' => 'group_event_main',
    'title' => 'О событии',
    'position' => 'normal',
    'menu_order' => 55,
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
      ],
      [
        'key' => 'field_event_important',
        'label' => 'Важно знать',
        'name' => 'event_important',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'basic',
        'media_upload' => 0,
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
        'key' => 'field_event_venue_scheme_type',
        'label' => 'Тип схемы',
        'name' => 'venue_scheme_type',
        'type' => 'select',
        'choices' => [
          'stadium' => 'Стадион',
          'hall' => 'Зал',
          'arena' => 'Арена',
          'theatre' => 'Театр',
          'track' => 'Трасса',
          'court' => 'Корт',
          'custom' => 'Другое (ввести вручную)',
        ],
        'default_value' => 'stadium',
        'allow_null' => 0,
        'return_format' => 'value',
        'wrapper' => ['width' => '50'],
        'instructions' => 'Определяет заголовок блока на фронте: «Схема стадиона», «Схема зала» и т.д.',
      ],
      [
        'key' => 'field_event_venue_scheme_title',
        'label' => 'Заголовок вручную',
        'name' => 'venue_scheme_title',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Схема площадки',
        'instructions' => 'Пишется целиком, как показать на сайте: «Схема ипподрома», «Схема трибун».',
        'conditional_logic' => [
          [
            ['field' => 'field_event_venue_scheme_type', 'operator' => '==', 'value' => 'custom'],
          ],
        ],
      ],
      [
        'key' => 'field_event_venue_scheme_legend',
        'label' => 'Легенда к схеме (устарело)',
        'instructions' => 'Для старых записей. Новые цены заполняйте в «Билеты: секторы и цены» — легенда соберётся оттуда. При открытии старой записи секторы подставляются из легенды автоматически.',
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
            'wrapper' => ['width' => '30'],
          ],
          [
            'key' => 'field_event_legend_date',
            'label' => 'Дата',
            'name' => 'legend_date',
            'type' => 'date_picker',
            'display_format' => 'd.m.Y',
            'return_format' => 'd.m.Y',
            'first_day' => 1,
            'wrapper' => ['width' => '20'],
            'instructions' => 'Дата сектора (необязательно).',
          ],
          [
            'key' => 'field_event_legend_price',
            'label' => 'Цена',
            'name' => 'legend_price',
            'type' => 'number',
            'wrapper' => ['width' => '30'],
            'min' => 0,
            'step' => 1,
            'placeholder' => '236000',
            'instructions' => 'Цена за сектор в валюте справа. На сайте конвертируется в рубли; чекбокс «Показать в валюте» переключает обратно.',
          ],
          [
            'key' => 'field_event_legend_currency',
            'label' => 'Валюта',
            'name' => 'legend_currency',
            'type' => 'select',
            'wrapper' => ['width' => '20'],
            'choices' => [
              'RUB' => 'RUB (₽)',
              'USD' => 'USD ($)',
              'EUR' => 'EUR (€)',
              'GBP' => 'GBP (£)',
            ],
            'default_value' => 'RUB',
            'return_format' => 'value',
            'instructions' => 'Валюта суммы. Пересчёт в рубли по курсу ЦБР; чекбокс «Показать в валюте» переключает обратно.',
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

  // Когда и где, menu_order 15. Площадка и город — одни на событие (решение 2026-10-08), у даты — только дата и время.
  acf_add_local_field_group([
    'key' => 'group_event_dates_places',
    'title' => 'Когда и где',
    'position' => 'normal',
    'menu_order' => 15,
    'fields' => [
      [
        'key' => 'field_event_venue',
        'label' => 'Площадка',
        'name' => 'event_venue',
        'type' => 'text',
        'instructions' => 'Одна на все даты. Если в разные дни разные стадионы — впишите стадион у каждой даты в таблице ниже, колонка «Площадка этого дня».',
        'wrapper' => ['width' => '50'],
        'placeholder' => 'Например, National Stadium',
      ],
      [
        'key' => 'field_event_resort',
        'label' => 'Город',
        'name' => 'event_resort',
        'type' => 'taxonomy',
        'taxonomy' => 'resort',
        'field_type' => 'select',
        'allow_null' => 1,
        'add_term' => 1,
        'save_terms' => 1,
        'load_terms' => 1,
        'return_format' => 'id',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_event_dates',
        'label' => 'Даты',
        'name' => 'event_dates',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить дату',
        'wrapper' => ['width' => '100'],
        'sub_fields' => [
          [
            'key' => 'field_event_date_value',
            'label' => 'Дата начала',
            'name' => 'date_value',
            'type' => 'date_picker',
            'display_format' => 'd.m.Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'required' => 1,
            'wrapper' => ['width' => '28'],
          ],
          [
            'key' => 'field_event_date_value_end',
            'label' => 'Дата окончания',
            'name' => 'date_value_end',
            'type' => 'date_picker',
            'display_format' => 'd.m.Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'required' => 0,
            'wrapper' => ['width' => '28'],
          ],
          [
            'key' => 'field_event_date_time',
            'label' => 'Начало',
            'name' => 'date_time',
            'type' => 'text',
            'wrapper' => ['width' => '14'],
            'placeholder' => '20:00',
            'maxlength' => 5,
          ],
          [
            'key' => 'field_event_date_city',
            'label' => 'Город',
            'name' => 'date_city',
            'type' => 'text',
            'wrapper' => ['width' => '14'],
            'placeholder' => 'Москва',
          ],
          [
            'key' => 'field_event_date_venue',
            'label' => 'Площадка этого дня',
            'name' => 'date_venue',
            'type' => 'text',
            'instructions' => 'Пусто — как в поле «Площадка» выше',
            'wrapper' => ['width' => '30'],
            'placeholder' => 'Как выше',
          ],
          [
            'key' => 'field_event_date_row_price',
            'label' => 'Цена от (число)',
            'name' => 'date_row_price',
            'type' => 'number',
            'step' => '0.01',
            'wrapper' => ['width' => '11'],
            'instructions' => 'Для старых записей без секторов. Если заполнены «Билеты: секторы и цены» — не нужно.',
          ],
          [
            'key' => 'field_event_date_row_currency',
            'label' => 'Валюта цены',
            'name' => 'date_row_price_currency',
            'type' => 'select',
            'wrapper' => ['width' => '12'],
            'choices' => [
              'RUB' => 'RUB (₽)',
              'USD' => 'USD ($)',
              'EUR' => 'EUR (€)',
              'GBP' => 'GBP (£)',
            ],
            'default_value' => 'RUB',
            'return_format' => 'value',
            'wrapper' => ['width' => '9'],
          ],
          [
            'key' => 'field_event_date_tour_url',
            'label' => 'Ссылка на тур в Самотуре',
            'name' => 'date_tour_url',
            'type' => 'url',
            'wrapper' => ['width' => '28'],
            'placeholder' => 'https://online.bsigroup.ru/search_crosstour?...',
            'instructions' => 'Тур под эту дату — скопируйте ссылку из Самотура как есть. Пусто — на эту дату используется общая ссылка из «Бронирования», а если и её нет — только билеты.',
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

  // Билеты: секторы и цены — один список на всё событие, не по дням.
  $event_currency_choices = [
    'RUB' => 'RUB (₽)',
    'USD' => 'USD ($)',
    'EUR' => 'EUR (€)',
    'GBP' => 'GBP (£)',
    'AED' => 'AED (дирхам ОАЭ)',
  ];
  acf_add_local_field_group([
    'key' => 'group_event_sector_prices',
    'title' => 'Билеты: секторы и цены',
    'position' => 'normal',
    'menu_order' => 17,
    'fields' => [
      // Схема — над секторами: цвет сектора берётся с неё пипеткой (inc/admin/event-admin.php).
      [
        'key' => 'field_event_venue_scheme',
        'label' => 'Схема зала',
        'name' => 'venue_scheme',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'library' => 'all',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_event_sectors',
        'label' => 'Секторы',
        'name' => 'event_sectors',
        'type' => 'repeater',
        'layout' => 'block',
        'collapsed' => 'field_event_sector_name',
        'button_label' => 'Добавить сектор',
        'instructions' => 'Цена сектора действует на все даты. Цены в валюте, на сайте — в рублях по курсу ЦБ.',
        'sub_fields' => [
          [
            'key' => 'field_event_sector_name',
            'label' => 'Сектор',
            'name' => 'sector_name',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '25'],
            'placeholder' => 'CAT 1',
          ],
          [
            'key' => 'field_event_sector_color',
            'label' => 'Цвет на схеме',
            'name' => 'sector_color',
            'type' => 'color_picker',
            'wrapper' => ['width' => '25'],
          ],
          [
            'key' => 'field_event_sector_description',
            'label' => 'Описание',
            'name' => 'sector_description',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
            'placeholder' => 'Средний ярус напротив сцены',
          ],
          [
            'key' => 'field_event_sector_price',
            'label' => 'Цена на все даты',
            'name' => 'sector_price',
            'type' => 'number',
            'min' => 0,
            'step' => '0.01',
            'wrapper' => ['width' => '25'],
          ],
          [
            'key' => 'field_event_sector_currency',
            'label' => 'Валюта',
            'name' => 'sector_currency',
            'type' => 'select',
            'choices' => $event_currency_choices,
            'default_value' => 'USD',
            'return_format' => 'value',
            'wrapper' => ['width' => '25'],
          ],
          [
            'key' => 'field_event_sector_pass_price',
            'label' => 'Абонемент на все дни',
            'name' => 'sector_pass_price',
            'type' => 'number',
            'min' => 0,
            'step' => '0.01',
            'wrapper' => ['width' => '25'],
            'placeholder' => 'Необязательно',
          ],
          [
            'key' => 'field_event_sector_tour_extra',
            'label' => 'Доплата к туру',
            'name' => 'sector_tour_extra',
            'type' => 'number',
            'min' => 0,
            'step' => '0.01',
            'wrapper' => ['width' => '25'],
            'placeholder' => 'Авто',
          ],
          [
            'key' => 'field_event_sector_date_prices',
            'label' => 'Своя цена на дату',
            'name' => 'sector_date_prices',
            'type' => 'repeater',
            'layout' => 'table',
            'button_label' => 'Другая цена на дату',
            'wrapper' => ['width' => '100'],
            'sub_fields' => [
              [
                'key' => 'field_event_sdp_date',
                'label' => 'Дата',
                'name' => 'sdp_date',
                'type' => 'date_picker',
                'display_format' => 'd.m.Y',
                'return_format' => 'Y-m-d',
                'first_day' => 1,
                'required' => 1,
              ],
              [
                'key' => 'field_event_sdp_price',
                'label' => 'Цена',
                'name' => 'sdp_price',
                'type' => 'number',
                'min' => 0,
                'step' => '0.01',
                'required' => 1,
              ],
            ],
          ],
        ],
      ],
      [
        'key' => 'field_event_tour_sector',
        'label' => 'Сектор, входящий в тур',
        'name' => 'event_tour_sector',
        'type' => 'select',
        'choices' => [],
        'allow_null' => 1,
        'return_format' => 'value',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_event_no_separate_tickets',
        'label' => 'Билеты отдельно не продаём',
        'name' => 'event_no_separate_tickets',
        'type' => 'true_false',
        'ui' => 1,
        'wrapper' => ['width' => '25'],
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
    'title' => 'Тур',
    'position' => 'normal',
    'menu_order' => 16,
    'fields' => [
      [
        'key' => 'field_event_data_source',
        'label' => 'Источник данных тура',
        'name' => 'event_data_source',
        'type' => 'select',
        'choices' => [
          'auto' => 'Авто (Самотур, если заведён; иначе вручную)',
          'manual' => 'Только вручную',
        ],
        'default_value' => 'auto',
        'allow_null' => 0,
        'ui' => 1,
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_event_booking_url',
        'label' => 'Ссылка для бронирования',
        'name' => 'tour_booking_url',
        'type' => 'url',
        'wrapper' => ['width' => '100'],
        'placeholder' => 'https://...',
      ],
      [
        'key' => 'field_event_booking_urls_more',
        'label' => 'Ещё туры Само (другие даты)',
        'name' => 'tour_booking_urls_more',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить ссылку',
        'instructions' => 'Если в Само на каждую дату концерта заведён свой тур: в поле выше — тур на первую дату, здесь — туры на остальные даты. Клиент выбирает дату концерта и видит заезды нужного тура.',
        'wrapper' => ['width' => '100'],
        'sub_fields' => [
          [
            'key' => 'field_event_booking_urls_more_date',
            'label' => 'Дата концерта',
            'name' => 'concert_date',
            'type' => 'date_picker',
            'display_format' => 'd.m.Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'required' => 1,
            'wrapper' => ['width' => '25'],
          ],
          [
            'key' => 'field_event_booking_urls_more_url',
            'label' => 'Ссылка на тур Само',
            'name' => 'url',
            'type' => 'url',
            'required' => 1,
            'placeholder' => 'https://online.bsigroup.ru/search_crosstour?...',
          ],
        ],
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

  // Проживание: отели по пакетам Само (Эконом / Стандарт / 5* / VIP). Цены пакетов — из Само, без Само — «по запросу».
  acf_add_local_field_group([
    'key' => 'group_event_accommodation',
    'title' => 'Проживание',
    'position' => 'normal',
    'menu_order' => 40,
    'fields' => [
      [
        'key' => 'field_event_accommodation',
        'label' => 'Варианты проживания',
        'name' => 'event_accommodation',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить вариант проживания',
        'wrapper' => ['width' => '100'],
        'min' => 0,
        'sub_fields' => [
          [
            'key' => 'field_event_accommodation_name',
            'label' => 'Название отеля',
            'name' => 'accommodation_hotel_name',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
            'required' => 1,
            'placeholder' => 'Hotel Mariotte',
          ],
          [
            'key' => 'field_event_accommodation_package',
            'label' => 'Пакет в Само',
            'name' => 'accommodation_package',
            'type' => 'select',
            'choices' => [
              'econom' => 'Эконом 3–4★',
              'standard' => 'Стандарт 4–4+★',
              'five' => '5★',
              'vip' => 'VIP 5★',
            ],
            'allow_null' => 1,
            'return_format' => 'value',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_event_accommodation_stars',
            'label' => 'Звёзды',
            'name' => 'accommodation_stars',
            'type' => 'number',
            'wrapper' => ['width' => '25'],
            'min' => 0,
            'max' => 5,
            'step' => 1,
            'placeholder' => '0–5',
          ],
          [
            'key' => 'field_event_accommodation_stars_plus',
            'label' => 'С плюсом (4+)',
            'name' => 'accommodation_stars_plus',
            'type' => 'true_false',
            'wrapper' => ['width' => '25'],
            'ui' => 1,
          ],
          [
            'key' => 'field_event_accommodation_description',
            'label' => 'Описание',
            'name' => 'accommodation_description',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
            'placeholder' => '15 минут пешком до стадиона, завтраки',
          ],
          [
            'key' => 'field_event_accommodation_price',
            'label' => 'Цена от (число)',
            'name' => 'accommodation_price',
            'type' => 'number',
            'wrapper' => ['width' => '70'],
            'step' => '0.01',
            'min' => 0,
            'instructions' => 'В валюте из поля справа. Пусто — цена не выводится.',
          ],
          [
            'key' => 'field_event_accommodation_currency',
            'label' => 'Валюта цены',
            'name' => 'accommodation_price_currency',
            'type' => 'select',
            'wrapper' => ['width' => '30'],
            'choices' => [
              'RUB' => 'RUB (₽)',
              'USD' => 'USD ($)',
              'EUR' => 'EUR (€)',
              'GBP' => 'GBP (£)',
            ],
            'default_value' => 'RUB',
            'return_format' => 'value',
          ],
          [
            'key' => 'field_event_accommodation_date_from',
            'label' => 'Заезд',
            'name' => 'accommodation_date_from',
            'type' => 'date_picker',
            'wrapper' => ['width' => '50'],
            'display_format' => 'd.m.Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'instructions' => 'Дата заселения. Используется для расчёта дней/ночей и сортировки.',
          ],
          [
            'key' => 'field_event_accommodation_date_to',
            'label' => 'Выезд',
            'name' => 'accommodation_date_to',
            'type' => 'date_picker',
            'wrapper' => ['width' => '50'],
            'display_format' => 'd.m.Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'instructions' => 'Дата выезда. Если пусто — диапазон и ночи не выводятся.',
          ],
        ],
      ],
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
        'instructions' => 'Доп. примечание под списком вариантов проживания (или альтернатива, если репитер пуст).',
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

  // Программа тура (по дням) — старый блок, временно выводится до переноса контента в конструктор.
  acf_add_local_field_group([
    'key' => 'group_event_program',
    'title' => 'Программа тура',
    'position' => 'normal',
    'menu_order' => 45,
    'fields' => [
      [
        'key' => 'field_event_tour_program',
        'label' => 'Программа тура (по дням)',
        'name' => 'tour_program',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить день',
        'instructions' => 'Временный блок до переноса в конструктор. На сайте — после «Важно знать».',
        'sub_fields' => [
          [
            'key' => 'field_event_tour_program_day_title',
            'label' => 'Заголовок дня',
            'name' => 'day_title',
            'type' => 'text',
            'wrapper' => ['width' => '30'],
            'placeholder' => 'День 1 / Прилет / ...',
          ],
          [
            'key' => 'field_event_tour_program_day_content',
            'label' => 'Описание дня',
            'name' => 'day_content',
            'type' => 'wysiwyg',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 0,
            'wrapper' => ['width' => '70'],
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

  // В стоимость входит / Оплачивается отдельно — списки «пункт + пояснение (+ цена)». Старые WYSIWYG скрыты, переносятся скриптом.
  acf_add_local_field_group([
    'key' => 'group_event_included',
    'title' => 'В стоимость входит / Оплачивается отдельно',
    'position' => 'normal',
    'menu_order' => 50,
    'fields' => [
      [
        'key' => 'field_event_included_items',
        'label' => 'В стоимость входит',
        'name' => 'tour_included_items',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить пункт',
        'sub_fields' => [
          [
            'key' => 'field_event_included_item_text',
            'label' => 'Пункт',
            'name' => 'text',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '50'],
            'placeholder' => 'Групповой трансфер отель — стадион — отель',
          ],
          [
            'key' => 'field_event_included_item_note',
            'label' => 'Пояснение',
            'name' => 'note',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
            'placeholder' => 'С англоговорящим водителем',
            'instructions' => 'Серым под пунктом. Вместо скобок.',
          ],
        ],
      ],
      [
        'key' => 'field_event_extra_items',
        'label' => 'Оплачивается отдельно',
        'name' => 'tour_extra_items',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить пункт',
        'sub_fields' => [
          [
            'key' => 'field_event_extra_item_text',
            'label' => 'Пункт',
            'name' => 'text',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '35'],
            'placeholder' => 'Медицинская страховка',
          ],
          [
            'key' => 'field_event_extra_item_note',
            'label' => 'Пояснение',
            'name' => 'note',
            'type' => 'text',
            'wrapper' => ['width' => '35'],
            'placeholder' => 'Оплачивается в отеле при заселении',
          ],
          [
            'key' => 'field_event_extra_item_price_mode',
            'label' => 'Цена',
            'name' => 'price_mode',
            'type' => 'select',
            'choices' => [
              'none' => 'Без цены',
              'from' => 'от …',
              'exact' => 'Точная',
              'request' => 'По запросу',
            ],
            'default_value' => 'none',
            'return_format' => 'value',
            'wrapper' => ['width' => '15'],
          ],
          [
            'key' => 'field_event_extra_item_price',
            'label' => 'Сумма, ₽',
            'name' => 'price',
            'type' => 'number',
            'min' => 0,
            'step' => 1,
            'wrapper' => ['width' => '15'],
            'conditional_logic' => [
              [['field' => 'field_event_extra_item_price_mode', 'operator' => '==', 'value' => 'from']],
              [['field' => 'field_event_extra_item_price_mode', 'operator' => '==', 'value' => 'exact']],
            ],
          ],
        ],
      ],
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
        'key' => 'field_event_additional',
        'label' => 'Дополнительно',
        'name' => 'event_additional',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
        'instructions' => 'Временный блок до переноса в конструктор. На сайте — после «Важно знать» и «Программы тура».',
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
    'title' => 'Вопросы и ответы',
    'position' => 'normal',
    'menu_order' => 60,
    'fields' => [
      [
        'key' => 'field_event_faq_mode',
        'label' => 'Какие вопросы показывать',
        'name' => 'event_faq_mode',
        'type' => 'select',
        'choices' => [
          'merge' => 'Общие + свои',
          'own' => 'Только свои',
        ],
        'default_value' => 'merge',
        'return_format' => 'value',
        'wrapper' => ['width' => '50'],
        'instructions' => 'Общие вопросы — «Продукты → Событийные туры: настройки», по типу события.',
      ],
      [
        'key' => 'field_event_faq_hide',
        'label' => 'Скрыть общие вопросы',
        'name' => 'event_faq_hide',
        'type' => 'checkbox',
        'choices' => [], // bsi_event_common_faq_choices()
        'return_format' => 'value',
        'layout' => 'vertical',
        'wrapper' => ['width' => '50'],
        'conditional_logic' => [
          [['field' => 'field_event_faq_mode', 'operator' => '==', 'value' => 'merge']],
        ],
      ],
      [
        'key' => 'field_event_faq',
        'label' => 'Свои вопросы',
        'name' => 'event_faq',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить вопрос',
        'instructions' => 'Новые вопросы встают перед общими. Чтобы исправить общий ответ — выберите его в «Заменяет общий вопрос».',
        'sub_fields' => [
          [
            'key' => 'field_event_faq_replaces',
            'label' => 'Заменяет общий вопрос',
            'name' => 'faq_replaces',
            'type' => 'select',
            'choices' => [], // bsi_event_common_faq_choices()
            'allow_null' => 1,
            'return_format' => 'value',
          ],
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

  // Статус и служебное
  acf_add_local_field_group([
    'key' => 'group_event_status',
    'title' => 'Статус и заявки',
    'position' => 'normal',
    'menu_order' => 70,
    'fields' => [
      [
        'key' => 'field_event_status',
        'label' => 'Статус продаж',
        'name' => 'event_status',
        'type' => 'select',
        'choices' => [
          'auto' => 'Идёт продажа (завершится само после последней даты)',
          'closed' => 'Продажи закрыты',
          'cancelled' => 'Событие отменено',
        ],
        'default_value' => 'auto',
        'allow_null' => 0,
        'return_format' => 'value',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_event_old_paths',
        'label' => 'Старые адреса (301)',
        'name' => 'event_old_paths',
        'type' => 'textarea',
        'rows' => 3,
        'new_lines' => '',
        'placeholder' => "/event-tours/konczert-stray-kids-v-singapure-06-03-2027/",
        'instructions' => 'Если у события были другие адреса (например, дубли), вставьте их сюда — по одному в строке. Кто зайдёт по старому адресу, попадёт на эту страницу.',
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

  // Настройки раздела: общий FAQ, получатели заявок, телефоны
  acf_add_local_field_group([
    'key' => 'group_event_settings',
    'title' => 'Настройки событийных туров',
    'fields' => [
      [
        'key' => 'field_event_settings_tab_faq',
        'label' => 'Общие вопросы',
        'type' => 'tab',
      ],
      [
        'key' => 'field_event_common_faq',
        'label' => 'Общие вопросы и ответы',
        'name' => 'event_common_faq',
        'type' => 'repeater',
        'layout' => 'block',
        'collapsed' => 'field_event_common_faq_question',
        'button_label' => 'Добавить вопрос',
        'instructions' => 'Показываются на всех событиях выбранных типов. В событии вопрос можно скрыть или заменить своим ответом.',
        'sub_fields' => [
          [
            'key' => 'field_event_common_faq_id',
            'label' => 'ID',
            'name' => 'faq_id',
            'type' => 'text',
            'readonly' => 1,
            'wrapper' => ['class' => 'acf-hidden'],
          ],
          [
            'key' => 'field_event_common_faq_question',
            'label' => 'Вопрос',
            'name' => 'question',
            'type' => 'text',
            'required' => 1,
          ],
          [
            'key' => 'field_event_common_faq_answer',
            'label' => 'Ответ',
            'name' => 'answer',
            'type' => 'textarea',
            'rows' => 4,
            'new_lines' => 'wpautop',
          ],
          [
            'key' => 'field_event_common_faq_types',
            'label' => 'Для типов событий',
            'name' => 'types',
            'type' => 'taxonomy',
            'taxonomy' => BSI_EVENT_TOUR_TYPE_TAXONOMY,
            'field_type' => 'multi_select',
            'add_term' => 0,
            'save_terms' => 0,
            'load_terms' => 0,
            'return_format' => 'id',
            'allow_null' => 1,
            'instructions' => 'Пусто — для всех событий.',
          ],
        ],
      ],
      [
        'key' => 'field_event_settings_tab_contacts',
        'label' => 'Заявки и телефоны',
        'type' => 'tab',
      ],
      [
        'key' => 'field_event_default_emails',
        'label' => 'Получатели заявок',
        'name' => 'event_default_emails',
        'type' => 'text',
        'placeholder' => 'o.ser@bsigroup.ru, sales@bsigroup.ru',
        'instructions' => 'Через запятую. Заявки на туры и консультации; на билеты — сюда же, если у события не задан «Email для заявок на билеты».',
      ],
      [
        'key' => 'field_event_phone_primary',
        'label' => 'Телефон (Москва)',
        'name' => 'event_phone_primary',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '8 (495) 785-55-35',
      ],
      [
        'key' => 'field_event_phone_secondary',
        'label' => 'Телефон (регионы)',
        'name' => 'event_phone_secondary',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
        'placeholder' => '8 (800) 200-55-35',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'options_page',
          'operator' => '==',
          'value' => 'bsi-event-settings',
        ],
      ],
    ],
  ]);
});

/**
 * Страница настроек «Продукты → Событийные туры: настройки».
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_options_sub_page')) {
    return;
  }
  acf_add_options_sub_page([
    'page_title' => 'Настройки событийных туров',
    'menu_title' => 'Событийные туры: настройки',
    'menu_slug' => 'bsi-event-settings',
    // В меню выводится пунктом хаба «Продукты» (inc/admin/menu-hubs.php): ACF регистрирует страницу раньше хаба.
    'parent_slug' => 'edit.php?post_type=event',
    // Редактор и выше: здесь получатели заявок — авторам и участникам менять нельзя.
    'capability' => 'edit_others_posts',
    'post_id' => 'bsi_event_settings',
  ]);
});

// Стабильный ID общего вопроса: на него ссылаются «Скрыть» и «Заменяет» в событиях — текст вопроса можно править.
add_filter('acf/update_value/key=field_event_common_faq_id', function ($value) {
  $value = trim((string) $value);

  return $value !== '' ? $value : substr(str_replace('-', '', wp_generate_uuid4()), 0, 10);
});

/**
 * Общие вопросы для выпадающих списков события: [faq_id => вопрос].
 */
function bsi_event_common_faq_choices(): array
{
  $rows = get_field('event_common_faq', 'bsi_event_settings');
  $out = [];
  foreach (is_array($rows) ? $rows : [] as $row) {
    $id = trim((string) ($row['faq_id'] ?? ''));
    $q = trim((string) ($row['question'] ?? ''));
    if ($id !== '' && $q !== '') {
      $out[$id] = $q;
    }
  }

  return $out;
}

add_filter('acf/load_field/key=field_event_faq_hide', function ($field) {
  $field['choices'] = bsi_event_common_faq_choices();

  return $field;
});
add_filter('acf/load_field/key=field_event_faq_replaces', function ($field) {
  $field['choices'] = bsi_event_common_faq_choices();

  return $field;
});

/**
 * Поля, убранные из админки по макетам 2026-10-08 (wiki/docs/event-page-tz.md, §2.3).
 * Определения остаются, чтобы старые записи выводились до переноса; значения в БД не трогаются.
 */
function bsi_event_hidden_field_keys(): array
{
  return [
    'field_event_hero_cover_mobile', // мобильный hero — фото полосой над текстом, хватает основной обложки
    'field_event_hero_date',
    'field_event_hero_date_end',
    'field_event_price_from',
    'field_event_nights',
    'field_event_time',
    'field_event_widget_phone_primary',
    'field_event_widget_phone_secondary',
    'field_event_venue_scheme_type',
    'field_event_venue_scheme_title',
    'field_event_venue_scheme_legend',
    'field_event_date_city',
    'field_event_date_row_price',
    'field_event_date_row_currency',
    'field_event_date_tour_url',
    'field_event_booking_cta_lead',
    'field_event_accommodation_price',
    'field_event_accommodation_currency',
    'field_event_accommodation_date_from',
    'field_event_accommodation_date_to',
    'field_event_extra',
    'field_event_included',
    'field_event_not_included',
  ];
}

add_filter('acf/prepare_field', function ($field) {
  if (!is_array($field) || empty($field['key'])) {
    return $field;
  }

  return in_array($field['key'], bsi_event_hidden_field_keys(), true) ? false : $field;
});

/**
 * Порядок метабоксов ACF для CPT event: после перетаскивания WP пишет user meta `meta-box-order_event`
 * и перебивает ACF `menu_order`. Здесь заново вшиваем блоки ACF в нужной последовательности.
 *
 * acf_after_title: Первый экран. normal: Основное → Когда и где → Билеты → Тур → Проживание → Входит / отдельно → Вопросы → Статус и заявки (галерея, билеты, программа — только фронт).
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
  // Порядок заполнения (решение 14.10): Первый экран (обложка, подзаголовок, страна) → Куда приходят заявки → Когда и где → Тур (Само) →
  // Билеты → Проживание → Программа → Входит / Оплачивается → О событии → Вопросы → Статус и заявки.
  $acf_normal = [
    'acf-group_event_emails',
    'acf-group_event_dates_places',
    'acf-group_event_booking',
    'acf-group_event_sector_prices',
    'acf-group_event_accommodation',
    'acf-group_event_program',
    'acf-group_event_included',
    'acf-group_event_main',
    'acf-group_event_faq',
    'acf-group_event_status',
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
