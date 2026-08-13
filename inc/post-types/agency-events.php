<?php

add_action('init', 'bsi_register_agency_event_post_type');
function bsi_register_agency_event_post_type()
{
  $labels = [
    'name' => 'Мероприятия для агентств',
    'singular_name' => 'Мероприятие для агентств',
    'menu_name' => 'Мероприятия агентствам',
    'add_new' => 'Добавить мероприятие',
    'add_new_item' => 'Добавить мероприятие',
    'edit_item' => 'Редактировать мероприятие',
    'new_item' => 'Новое мероприятие',
    'view_item' => 'Смотреть мероприятие',
    'search_items' => 'Найти мероприятие',
    'not_found' => 'Мероприятия не найдены',
    'not_found_in_trash' => 'В корзине нет мероприятий',
  ];

  register_post_type('agency_event', [
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 23,
    'menu_icon' => 'dashicons-calendar-alt',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'],
    'has_archive' => false,
    'rewrite' => ['slug' => 'agency-events', 'with_front' => false],
  ]);
}

add_action('init', 'bsi_register_agency_event_kind_taxonomy', 20);
function bsi_register_agency_event_kind_taxonomy()
{
  register_taxonomy('agency_event_kind', ['agency_event'], [
    'labels' => [
      'name' => 'Типы мероприятий',
      'singular_name' => 'Тип мероприятия',
      'menu_name' => 'Типы мероприятий',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'add_new_item' => 'Добавить тип',
      'search_items' => 'Найти тип',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => ['slug' => 'agency-event-kind', 'with_front' => false],
  ]);
}

add_action('init', 'bsi_register_agency_event_direction_taxonomy', 21);
function bsi_register_agency_event_direction_taxonomy()
{
  register_taxonomy('agency_event_direction', ['agency_event'], [
    'labels' => [
      'name' => 'Направления',
      'singular_name' => 'Направление',
      'menu_name' => 'Направления',
      'all_items' => 'Все направления',
      'edit_item' => 'Редактировать направление',
      'add_new_item' => 'Добавить направление',
      'search_items' => 'Найти направление',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => ['slug' => 'agency-event-direction', 'with_front' => false],
  ]);
}

add_action('init', 'bsi_ensure_agency_event_kind_terms', 25);
function bsi_ensure_agency_event_kind_terms()
{
  if (!taxonomy_exists('agency_event_kind')) {
    return;
  }

  // Слаг `event` исторический: тип называется «Отраслевое».
  $default_terms = [
    'webinar' => 'Вебинар',
    'seminar' => 'Семинар',
    'vorkshop' => 'Воркшоп',
    'event' => 'Отраслевое',
    'promo-tour' => 'Рекламный тур',
  ];

  foreach ($default_terms as $slug => $name) {
    if (!term_exists($slug, 'agency_event_kind')) {
      wp_insert_term($name, 'agency_event_kind', ['slug' => $slug]);
    }
  }
}

add_action('acf/init', 'bsi_register_agency_event_fields');
function bsi_register_agency_event_fields()
{
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_agency_event_fields',
    'title' => 'Мероприятие для агентств — карточка',
    'position' => 'normal',
    'menu_order' => 10,
    'fields' => [
      [
        'key' => 'field_agency_event_heading_when',
        'label' => '',
        'name' => '',
        'type' => 'message',
        'message' => '<strong>Когда</strong>',
        'new_lines' => '',
        'esc_html' => 0,
      ],
      [
        'key' => 'field_agency_event_start_date',
        'label' => 'Дата начала',
        'name' => 'event_start_date',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_start_time',
        'label' => 'Время начала',
        'name' => 'event_start_time',
        'type' => 'text',
        'placeholder' => '13:00',
        'instructions' => 'Необязательно. Пусто — время на сайте не показывается.',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_end_date',
        'label' => 'Дата окончания',
        'name' => 'event_end_date',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'instructions' => 'Только для мероприятий длиннее одного дня. На сайте даты покажутся диапазоном: «16–18 августа 2026».',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_heading_where',
        'label' => '',
        'name' => '',
        'type' => 'message',
        'message' => '<strong>Где</strong>',
        'new_lines' => '',
        'esc_html' => 0,
      ],
      [
        'key' => 'field_agency_event_city',
        'label' => 'Город',
        'name' => 'event_city',
        'type' => 'text',
        'placeholder' => 'Москва',
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_place',
        'label' => 'Адрес / площадка',
        'name' => 'event_place',
        'type' => 'text',
        'placeholder' => 'Адрес или онлайн-площадка',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_heading_registration',
        'label' => '',
        'name' => '',
        'type' => 'message',
        'message' => '<strong>Участие</strong>',
        'new_lines' => '',
        'esc_html' => 0,
      ],
      [
        'key' => 'field_agency_event_price',
        'label' => 'Цена',
        'name' => 'event_price',
        'type' => 'text',
        'placeholder' => '198 888 ₽',
        'instructions' => 'Пишется как есть: «Бесплатно», «590 $», «12 000 ₽».',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_registration_closed',
        'label' => 'Закрыть запись',
        'name' => 'event_registration_closed',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'instructions' => 'Вместо кнопки «Регистрация» покажется «Запись закрыта».',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_registration_url',
        'label' => 'Внешняя ссылка регистрации',
        'name' => 'event_registration_url',
        'type' => 'url',
        'instructions' => 'Если заполнено — кнопка «Регистрация» откроет эту ссылку в новой вкладке вместо формы.',
        'placeholder' => 'https://',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_notify_email',
        'label' => 'Email для заявок',
        'name' => 'event_notify_email',
        'type' => 'email',
        'instructions' => 'Дополнительный email для заявок с этого мероприятия. Основной (agent@bsigroup.ru) получает всегда.',
        'placeholder' => 'email@example.com',
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'agency_event',
        ],
      ],
    ],
  ]);
}

add_action('save_post_agency_event', 'bsi_agency_event_sync_start_ts', 20, 3);
function bsi_agency_event_sync_start_ts($post_id, $post, $update)
{
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
    return;
  }
  if (!($post instanceof WP_Post) || $post->post_type !== 'agency_event') {
    return;
  }

  $start_date = function_exists('get_field') ? trim((string) get_field('event_start_date', $post_id)) : '';
  $start_time = function_exists('get_field') ? trim((string) get_field('event_start_time', $post_id)) : '';
  $end_date = function_exists('get_field') ? trim((string) get_field('event_end_date', $post_id)) : '';

  // Время необязательно: без него началом считается начало дня.
  $start_ts = bsi_agency_event_calc_start_ts($start_date, $start_time);
  // Мероприятие «идёт» до конца последнего дня — дня окончания либо дня начала.
  $end_ts = bsi_agency_event_calc_end_ts($start_date, $end_date);

  if ($start_ts > 0) {
    update_post_meta($post_id, 'event_start_ts', (string) $start_ts);
  } else {
    delete_post_meta($post_id, 'event_start_ts');
  }

  if ($end_ts > 0) {
    update_post_meta($post_id, 'event_end_ts', (string) $end_ts);
  } else {
    delete_post_meta($post_id, 'event_end_ts');
  }
}

add_action('init', 'bsi_agency_events_backfill_start_ts');
function bsi_agency_events_backfill_start_ts()
{
  if (!is_user_logged_in() || !current_user_can('edit_posts')) {
    return;
  }
  if (get_option('bsi_agency_events_end_ts_backfilled')) {
    return;
  }

  // Записи без event_end_ts: метка появилась вместе с полем «Дата окончания».
  $q = new WP_Query([
    'post_type' => 'agency_event',
    'post_status' => 'any',
    'posts_per_page' => 200,
    'fields' => 'ids',
    'no_found_rows' => true,
    'meta_query' => [
      [
        'key' => 'event_end_ts',
        'compare' => 'NOT EXISTS',
      ],
    ],
  ]);

  if (!empty($q->posts)) {
    foreach ($q->posts as $event_id) {
      bsi_agency_event_sync_start_ts((int) $event_id, get_post((int) $event_id), true);
    }
  }

  update_option('bsi_agency_events_end_ts_backfilled', 1, false);
}
