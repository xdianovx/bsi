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

  $default_terms = [
    'webinar' => 'Вебинар',
    'event' => 'Мероприятие',
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
        'key' => 'field_agency_event_start_date',
        'label' => 'Дата начала',
        'name' => 'event_start_date',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'required' => 1,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_agency_event_start_time',
        'label' => 'Время начала',
        'name' => 'event_start_time',
        'type' => 'text',
        'placeholder' => 'Например: 13:00',
        'required' => 1,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_agency_event_place',
        'label' => 'Место',
        'name' => 'event_place',
        'type' => 'text',
        'placeholder' => 'Город, адрес или онлайн-площадка',
        'required' => 1,
        'wrapper' => ['width' => '34'],
      ],
      [
        'key' => 'field_agency_event_registration_closed',
        'label' => 'Закрыть запись',
        'name' => 'event_registration_closed',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_agency_event_price',
        'label' => 'Цена',
        'name' => 'event_price',
        'type' => 'text',
        'placeholder' => 'Например: 198 888 ₽',
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
        'instructions' => 'Дополнительный email для получения заявок на это мероприятие. Основной (agent@bsigroup.ru) получает всегда.',
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
