<?php

add_action('acf/init', 'bsi_register_education_acf_groups');
function bsi_register_education_acf_groups(): void
{
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_education_main',
    'title' => 'Основная информация',
    'fields' => [
      [
        'key' => 'field_education_country',
        'label' => 'Страна обучения',
        'name' => 'education_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'required' => 1,
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_price',
        'label' => 'Стоимость',
        'name' => 'education_price',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_map_lat',
        'label' => 'Широта (lat)',
        'name' => 'education_map_lat',
        'type' => 'number',
        'step' => 0.000001,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_education_map_lng',
        'label' => 'Долгота (lng)',
        'name' => 'education_map_lng',
        'type' => 'number',
        'step' => 0.000001,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_education_map_zoom',
        'label' => 'Zoom (карта)',
        'name' => 'education_map_zoom',
        'type' => 'number',
        'min' => 1,
        'max' => 20,
        'step' => 1,
        'default_value' => 14,
        'wrapper' => ['width' => '33'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'education',
        ],
      ],
    ],
    'style' => 'seamless',
  ]);

  acf_add_local_field_group([
    'key' => 'group_education_contacts',
    'title' => 'Контакты',
    'fields' => [
      [
        'key' => 'field_education_website',
        'label' => 'Сайт школы',
        'name' => 'education_website',
        'type' => 'url',
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_education_phone',
        'label' => 'Телефон',
        'name' => 'education_phone',
        'type' => 'text',
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_education_address',
        'label' => 'Адрес',
        'name' => 'education_address',
        'type' => 'text',
        'wrapper' => ['width' => '33'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'education',
        ],
      ],
    ],
  ]);

  acf_add_local_field_group([
    'key' => 'group_education_gallery',
    'title' => 'Галерея',
    'fields' => [
      [
        'key' => 'field_education_gallery',
        'label' => 'Галерея изображений',
        'name' => 'education_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'education',
        ],
      ],
    ],
  ]);

  acf_add_local_field_group([
    'key' => 'group_education_programs',
    'title' => 'Учебные программы',
    'fields' => [
      [
        'key' => 'field_education_programs',
        'label' => 'Учебные программы',
        'name' => 'education_programs',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить программу',
        'sub_fields' => [
          [
            'key' => 'field_program_price_per_week',
            'label' => 'Цена за неделю',
            'name' => 'program_price_per_week',
            'type' => 'text',
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_program_age_min',
            'label' => 'Минимальный возраст',
            'name' => 'program_age_min',
            'type' => 'number',
            'min' => 0,
            'step' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_program_age_max',
            'label' => 'Максимальный возраст',
            'name' => 'program_age_max',
            'type' => 'number',
            'min' => 0,
            'step' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_program_duration_min',
            'label' => 'Минимальная продолжительность (недели)',
            'name' => 'program_duration_min',
            'type' => 'number',
            'min' => 1,
            'step' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_program_duration_max',
            'label' => 'Максимальная продолжительность (недели)',
            'name' => 'program_duration_max',
            'type' => 'number',
            'min' => 1,
            'step' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_program_description',
            'label' => 'Описание курса',
            'name' => 'program_description',
            'type' => 'textarea',
            'rows' => 4,
            'new_lines' => 'wpautop',
            'wrapper' => ['width' => '100'],
          ],
          [
            'key' => 'field_program_checkin_dates',
            'label' => 'Даты заселения',
            'name' => 'program_checkin_dates',
            'type' => 'repeater',
            'layout' => 'table',
            'button_label' => 'Добавить дату',
            'sub_fields' => [
              [
                'key' => 'field_checkin_date',
                'label' => 'Дата заселения',
                'name' => 'checkin_date',
                'type' => 'date_picker',
                'display_format' => 'd/m/Y',
                'return_format' => 'Y-m-d',
                'first_day' => 1,
              ],
            ],
          ],
          [
            'key' => 'field_program_accommodation_options',
            'label' => 'Варианты проживания',
            'name' => 'program_accommodation_options',
            'type' => 'taxonomy',
            'taxonomy' => 'education_accommodation_type',
            'field_type' => 'multi_select',
            'return_format' => 'id',
            'add_term' => 0,
            'save_terms' => 0,
            'load_terms' => 0,
            'wrapper' => ['width' => '100'],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'education',
        ],
      ],
    ],
  ]);

  acf_add_local_field_group([
    'key' => 'group_education_booking',
    'title' => 'Бронирование',
    'fields' => [
      [
        'key' => 'field_education_booking_button_text',
        'label' => 'Текст кнопки',
        'name' => 'education_booking_button_text',
        'type' => 'text',
        'default_value' => 'Запронировать',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_booking_url',
        'label' => 'URL для бронирования',
        'name' => 'education_booking_url',
        'type' => 'url',
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'education',
        ],
      ],
    ],
  ]);
}

add_filter('acf/fields/post_object/query/key=field_education_country', 'bsi_filter_education_country_parent_only', 10, 3);
function bsi_filter_education_country_parent_only(array $args, array $field, $post_id): array
{
  $args['post_parent'] = 0;
  return $args;
}

