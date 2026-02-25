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
        'key' => 'field_education_resort',
        'label' => 'Курорт',
        'name' => 'education_resort',
        'type' => 'taxonomy',
        'taxonomy' => 'resort',
        'field_type' => 'select',
        'return_format' => 'id',
        'add_term' => 0,
        'save_terms' => 1,
        'load_terms' => 1,
        'multiple' => 0,
        'allow_null' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_price',
        'label' => 'Стоимость',
        'name' => 'education_price',
        'type' => 'text',
        'instructions' => 'Например: "100 000 руб / 1 неделя" или "от 50 000 руб"',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_age',
        'label' => 'Возраст',
        'name' => 'education_age',
        'type' => 'text',
        'instructions' => 'Например: "5+" или "от 12 лет"',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_education_class_size',
        'label' => 'В классе',
        'name' => 'education_class_size',
        'type' => 'text',
        'instructions' => 'Например: "12 человек" или "до 15 студентов"',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_education_lesson_duration',
        'label' => 'Длительность урока',
        'name' => 'education_lesson_duration',
        'type' => 'text',
        'instructions' => 'Например: "60 минут" или "45 мин"',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_education_course_duration',
        'label' => 'Продолжительность обучения',
        'name' => 'education_course_duration',
        'type' => 'text',
        'instructions' => 'Например: "2 недели" или "от 1 месяца"',
        'wrapper' => ['width' => '25'],
      ],
      [
        'key' => 'field_education_is_popular',
        'label' => 'Популярная программа',
        'name' => 'is_popular',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'instructions' => 'Покажет программу в блоке «Популярные программы образования» на главной странице. Порядок отображения можно настроить через поле «Порядок» (меняется перетаскиванием в списке постов).',
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
    'menu_order' => 1,
  ]);

  acf_add_local_field_group([
    'key' => 'group_education_contacts',
    'title' => 'Контакты и местоположение',
    'fields' => [
      [
        'key' => 'field_education_address',
        'label' => 'Адрес',
        'name' => 'education_address',
        'type' => 'text',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_education_phone',
        'label' => 'Телефон',
        'name' => 'education_phone',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_website',
        'label' => 'Сайт школы',
        'name' => 'education_website',
        'type' => 'url',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_map_coordinates',
        'label' => 'Координаты на карте',
        'name' => 'education_map_coordinates',
        'type' => 'text',
        'instructions' => 'Вставьте одну строку: широта, долгота. Например: 3.607725, 72.900417',
        'placeholder' => '55.753215, 37.622504',
        'wrapper' => ['width' => '66'],
      ],
      [
        'key' => 'field_education_map_zoom',
        'label' => 'Масштаб карты',
        'name' => 'education_map_zoom',
        'type' => 'number',
        'min' => 1,
        'max' => 20,
        'step' => 1,
        'default_value' => 14,
        'instructions' => 'От 1 (весь мир) до 20 (улица)',
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
    'menu_order' => 4,
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
    'menu_order' => 5,
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
            'key' => 'field_program_title',
            'label' => 'Название программы',
            'name' => 'program_title',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '100'],
          ],
          [
            'key' => 'field_program_price_per_week',
            'label' => 'Цена за неделю',
            'name' => 'program_price_per_week',
            'type' => 'text',
            'instructions' => 'Например: "50 000 руб" или "от 45 000 руб"',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_program_booking_url',
            'label' => 'URL для бронирования',
            'name' => 'program_booking_url',
            'type' => 'url',
            'instructions' => 'Если указан, будет использоваться для этой программы. Иначе используется общий URL бронирования.',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_program_age_min',
            'label' => 'Минимальный возраст',
            'name' => 'program_age_min',
            'type' => 'number',
            'min' => 0,
            'step' => 1,
            'wrapper' => ['width' => '25'],
          ],
          [
            'key' => 'field_program_age_max',
            'label' => 'Максимальный возраст',
            'name' => 'program_age_max',
            'type' => 'number',
            'min' => 0,
            'step' => 1,
            'wrapper' => ['width' => '25'],
          ],
          [
            'key' => 'field_program_duration',
            'label' => 'Продолжительность (недели)',
            'name' => 'program_duration',
            'type' => 'number',
            'min' => 1,
            'step' => 1,
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_program_languages',
            'label' => 'Языки обучения',
            'name' => 'program_languages',
            'type' => 'taxonomy',
            'taxonomy' => 'education_language',
            'field_type' => 'multi_select',
            'return_format' => 'id',
            'add_term' => 0,
            'save_terms' => 0,
            'load_terms' => 0,
            'instructions' => 'Выберите языки, на которых проводится обучение по этой программе',
            'wrapper' => ['width' => '50'],
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
            'key' => 'field_program_checkin_date_from',
            'label' => 'Дата заселения (от)',
            'name' => 'program_checkin_date_from',
            'type' => 'date_picker',
            'display_format' => 'd/m/Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_program_checkin_date_to',
            'label' => 'Дата заселения (до)',
            'name' => 'program_checkin_date_to',
            'type' => 'date_picker',
            'display_format' => 'd/m/Y',
            'return_format' => 'Y-m-d',
            'first_day' => 1,
            'wrapper' => ['width' => '50'],
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
          [
            'key' => 'field_program_meal_options',
            'label' => 'Питание',
            'name' => 'program_meal_options',
            'type' => 'taxonomy',
            'taxonomy' => 'education_meal_type',
            'field_type' => 'multi_select',
            'return_format' => 'id',
            'add_term' => 0,
            'save_terms' => 0,
            'load_terms' => 0,
            'wrapper' => ['width' => '100'],
          ],
          [
            'key' => 'field_program_visa_required',
            'label' => 'Нужна виза',
            'name' => 'program_visa_required',
            'type' => 'true_false',
            'ui' => 1,
            'default_value' => 0,
            'instructions' => 'Если включено, в модальном окне будет чекбокс для оформления визы',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_program_visa_price',
            'label' => 'Стоимость визы',
            'name' => 'program_visa_price',
            'type' => 'number',
            'min' => 0,
            'step' => 1,
            'instructions' => 'Цена в рублях',
            'conditional_logic' => [
              [
                [
                  'field' => 'field_program_visa_required',
                  'operator' => '==',
                  'value' => '1',
                ],
              ],
            ],
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_program_additional_services',
            'label' => 'Дополнительные услуги',
            'name' => 'program_additional_services',
            'type' => 'repeater',
            'layout' => 'table',
            'button_label' => 'Добавить услугу',
            'sub_fields' => [
              [
                'key' => 'field_service_title',
                'label' => 'Название',
                'name' => 'service_title',
                'type' => 'text',
                'required' => 1,
                'wrapper' => ['width' => '40'],
              ],
              [
                'key' => 'field_service_price',
                'label' => 'Цена',
                'name' => 'service_price',
                'type' => 'number',
                'min' => 0,
                'step' => 1,
                'instructions' => 'В рублях',
                'wrapper' => ['width' => '25'],
              ],
              [
                'key' => 'field_service_note',
                'label' => 'Примечание',
                'name' => 'service_note',
                'type' => 'text',
                'instructions' => 'Например: "Цена за 1 неделю, оплачивается на месте"',
                'wrapper' => ['width' => '35'],
              ],
            ],
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
    'menu_order' => 10,
  ]);

  acf_add_local_field_group([
    'key' => 'group_education_price_details',
    'title' => 'Стоимость',
    'fields' => [
      [
        'key' => 'field_education_price_included',
        'label' => 'В стоимость входит',
        'name' => 'education_price_included',
        'type' => 'wysiwyg',
        'instructions' => 'Вставьте список элементов. Можно использовать маркированный список (ul) или просто текст с переносами строк.',
        'tabs' => 'all',
        'toolbar' => 'basic',
        'media_upload' => 0,
        'delay' => 0,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_education_price_extra',
        'label' => 'Оплачивается дополнительно',
        'name' => 'education_price_extra',
        'type' => 'wysiwyg',
        'instructions' => 'Вставьте список элементов. Можно использовать маркированный список (ul) или просто текст с переносами строк.',
        'tabs' => 'all',
        'toolbar' => 'basic',
        'media_upload' => 0,
        'delay' => 0,
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
    'menu_order' => 3,
  ]);

  acf_add_local_field_group([
    'key' => 'group_education_booking',
    'title' => 'Бронирование',
    'fields' => [
      [
        'key' => 'field_education_booking_url',
        'label' => 'URL для бронирования',
        'name' => 'education_booking_url',
        'type' => 'url',
        'instructions' => 'Если указан, кнопка "Забронировать" будет вести на этот URL',
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
    'menu_order' => 2,
  ]);
}

add_filter('acf/fields/post_object/query/key=field_education_country', 'bsi_filter_education_country_parent_only', 10, 3);
function bsi_filter_education_country_parent_only(array $args, array $field, $post_id): array
{
  $args['post_parent'] = 0;
  return $args;
}

