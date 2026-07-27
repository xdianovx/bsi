<?php

add_action('init', 'bsi_attach_hotel_taxonomies');
function bsi_attach_hotel_taxonomies(): void
{
  if (taxonomy_exists('region')) {
    register_taxonomy_for_object_type('region', 'hotel');
  }
  if (taxonomy_exists('resort')) {
    register_taxonomy_for_object_type('resort', 'hotel');
  }
}

add_action('acf/init', 'bsi_register_hotel_acf_groups');
function bsi_register_hotel_acf_groups(): void
{
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_hotel_main',
    'title' => 'Отель',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [

      // ================= ТАБ «ОСНОВНОЕ» =================
      [
        'key' => 'field_hotel_tab_main',
        'label' => 'Основное',
        'type' => 'tab',
        'placement' => 'top',
      ],

      // --- ГЕО ---
      [
        'key' => 'field_hotel_acc_geo',
        'label' => 'ГЕО',
        'type' => 'accordion',
        'open' => 1,
      ],
      [
        'key' => 'field_hotel_geo_notice',
        'label' => '',
        'name' => 'hotel_geo_notice',
        'type' => 'message',
        'message' => 'Страна выбирается в поле ниже. Регионы и курорты выбираются стандартно в правой колонке в блоках таксономий.',
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
        'key' => 'field_hotel_map_coordinates',
        'label' => 'Координаты на карте',
        'name' => 'map_coordinates',
        'type' => 'text',
        'instructions' => 'Вставьте одну строку: широта, долгота. Например: 3.607725, 72.900417',
        'placeholder' => '55.753215, 37.622504',
        'wrapper' => ['width' => '66'],
      ],
      [
        'key' => 'field_hotel_map_zoom',
        'label' => 'Zoom (карта)',
        'name' => 'map_zoom',
        'type' => 'number',
        'min' => 1,
        'max' => 20,
        'step' => 1,
        'default_value' => 14,
        'wrapper' => ['width' => '33'],
      ],

      // --- Основная информация ---
      [
        'key' => 'field_hotel_acc_basic',
        'label' => 'Основная информация',
        'type' => 'accordion',
      ],
      [
        'key' => 'field_hotel_rating',
        'label' => 'Рейтинг (звезды)',
        'name' => 'rating',
        'type' => 'number',
        'min' => 1,
        'max' => 5,
        'step' => 1,
        'placeholder' => 'от 1 до 5',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_is_featured',
        'label' => 'Популярный отель',
        'name' => 'is_popular',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 0,
        'instructions' => 'Покажет отель в блоке «Популярные отели» (слайдер на главной/в секциях по странам).',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_opened_at',
        'label' => 'Дата постройки отеля',
        'name' => 'hotel_opened_at',
        'type' => 'date_picker',
        'display_format' => 'm/Y',
        'return_format' => 'Y-m',
        'first_day' => 1,
        'instructions' => 'Укажите месяц и год постройки отеля.',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_renovated_at',
        'label' => 'Дата последней реновации',
        'name' => 'hotel_renovated_at',
        'type' => 'date_picker',
        'display_format' => 'm/Y',
        'return_format' => 'Y-m',
        'first_day' => 1,
        'instructions' => 'Укажите месяц и год последнего ремонта/реновации.',
        'wrapper' => ['width' => '50'],
      ],

      // --- Контакты и адрес ---
      [
        'key' => 'field_hotel_acc_contacts',
        'label' => 'Контакты и адрес',
        'type' => 'accordion',
      ],
      [
        'key' => 'field_hotel_address',
        'label' => 'Адрес',
        'name' => 'address',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_phone',
        'label' => 'Телефон',
        'name' => 'phone',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_website',
        'label' => 'Сайт отеля',
        'name' => 'website',
        'type' => 'url',
        'wrapper' => ['width' => '100'],
      ],

      // --- Бронирование и цены ---
      [
        'key' => 'field_hotel_acc_booking',
        'label' => 'Бронирование и цены',
        'type' => 'accordion',
      ],
      [
        'key' => 'field_price',
        'label' => 'Стоимость',
        'name' => 'price',
        'type' => 'text',
        'instructions' => 'Минимальная цена в рублях',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_price_text',
        'label' => 'Текст к цене',
        'name' => 'price_text',
        'type' => 'text',
        'instructions' => 'Дополнительный текст к цене, например: "за 5 ночей", "за неделю" и т.д.',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_nights',
        'label' => 'Количество ночей',
        'name' => 'nights',
        'type' => 'number',
        'min' => 1,
        'instructions' => 'Количество ночей для тура',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_hotel_checkin_date',
        'label' => 'Дата начала заселения',
        'name' => 'checkin_date',
        'type' => 'date_picker',
        'display_format' => 'd/m/Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'wrapper' => ['width' => '50'],
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
        'key' => 'field_hotel_booking_url',
        'label' => 'Ссылка на бронирование',
        'name' => 'booking_url',
        'type' => 'url',
        'instructions' => 'Ссылка для бронирования тура с перелетом',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_hotel_booking_url_hotel_only',
        'label' => 'Ссылка на бронирование (только отель)',
        'name' => 'booking_url_hotel_only',
        'type' => 'url',
        'instructions' => 'Ссылка для бронирования только отеля без перелета',
        'wrapper' => ['width' => '100'],
      ],

      // --- Медиа ---
      [
        'key' => 'field_hotel_acc_media',
        'label' => 'Медиа',
        'type' => 'accordion',
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
      ],

      // --- Расстояния и дополнительная информация ---
      [
        'key' => 'field_hotel_acc_distances',
        'label' => 'Расстояния и дополнительная информация',
        'type' => 'accordion',
      ],
      [
        'key' => 'field_hotel_distances',
        'label' => 'Информация',
        'name' => 'hotel_distances',
        'type' => 'repeater',
        'layout' => 'row',
        'button_label' => 'Добавить элемент',
        'sub_fields' => [
          [
            'key' => 'field_hotel_distance_key',
            'label' => 'Ключ',
            'name' => 'key',
            'type' => 'text',
            'placeholder' => 'Например: до пляжа',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_hotel_distance_value',
            'label' => 'Значение',
            'name' => 'value',
            'type' => 'text',
            'placeholder' => 'Например: 35км',
            'wrapper' => ['width' => '50'],
          ],
        ],
      ],

      // --- Секции описания ---
      [
        'key' => 'field_hotel_acc_sections',
        'label' => 'Секции описания',
        'type' => 'accordion',
      ],
      [
        'key' => 'field_hotel_sections_notice',
        'label' => '',
        'name' => 'hotel_sections_notice',
        'type' => 'message',
        'message' => 'Каждая секция выводится на странице отеля отдельным блоком с заголовком. Пустые секции не показываются.',
        'new_lines' => 'wpautop',
        'esc_html' => 0,
      ],
      [
        'key' => 'field_hotel_sec_infrastructure',
        'label' => 'Инфраструктура',
        'name' => 'sec_infrastructure',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_hotel_sec_meals',
        'label' => 'Питание',
        'name' => 'sec_meals',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_hotel_sec_restaurants',
        'label' => 'Рестораны и бары',
        'name' => 'sec_restaurants',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_hotel_sec_spa',
        'label' => 'Spa и оздоровление',
        'name' => 'sec_spa',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_hotel_sec_sport',
        'label' => 'Спорт и развлечения',
        'name' => 'sec_sport',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_hotel_sec_kids',
        'label' => 'Для детей',
        'name' => 'sec_kids',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_hotel_sec_mice',
        'label' => 'MICE',
        'name' => 'sec_mice',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_hotel_sec_beach',
        'label' => 'Пляж',
        'name' => 'sec_beach',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],

      [
        'key' => 'field_hotel_acc_sections_end',
        'label' => '',
        'type' => 'accordion',
        'endpoint' => 1,
      ],

      // ================= ТАБ «НОМЕРА» =================
      [
        'key' => 'field_hotel_tab_rooms',
        'label' => 'Номера',
        'type' => 'tab',
        'placement' => 'top',
      ],
      [
        'key' => 'field_hotel_rooms',
        'label' => 'Карточки номеров',
        'name' => 'hotel_rooms',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить номер',
        'instructions' => 'Выводятся сеткой в секции «Номера» после текста',
        'sub_fields' => [
          [
            'key' => 'field_room_gallery',
            'label' => 'Фото номера',
            'name' => 'gallery',
            'type' => 'gallery',
            'return_format' => 'array',
            'preview_size' => 'thumbnail',
            'required' => 1,
          ],
          [
            'key' => 'field_room_name',
            'label' => 'Название',
            'name' => 'name',
            'type' => 'text',
            'required' => 1,
            'wrapper' => ['width' => '40'],
          ],
          [
            'key' => 'field_room_area',
            'label' => 'Площадь, м²',
            'name' => 'area',
            'type' => 'number',
            'min' => 0,
            'wrapper' => ['width' => '20'],
          ],
          [
            'key' => 'field_room_guests',
            'label' => 'Вместимость',
            'name' => 'guests',
            'type' => 'text',
            'placeholder' => 'Например: 2+1',
            'wrapper' => ['width' => '20'],
          ],
          [
            'key' => 'field_room_price_from',
            'label' => 'Цена «от», ₽',
            'name' => 'price_from',
            'type' => 'number',
            'min' => 0,
            'wrapper' => ['width' => '20'],
          ],
          [
            'key' => 'field_room_description',
            'label' => 'Описание',
            'name' => 'description',
            'type' => 'textarea',
            'rows' => 3,
          ],
          [
            'key' => 'field_room_amenities',
            'label' => 'Удобства',
            'name' => 'amenities',
            'type' => 'taxonomy',
            'taxonomy' => 'amenity',
            'field_type' => 'multi_select',
            'return_format' => 'id',
            'add_term' => 0,
            'save_terms' => 0,
            'load_terms' => 0,
            'instructions' => 'Общий словарь: Отели → Удобства (там же иконка термина). Заполняется один раз, здесь только выбирается.',
          ],
        ],
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
}

// Отрывок — первым блоком после заголовка (над боксом «Отель»)
add_action('add_meta_boxes_hotel', 'bsi_hotel_remove_default_excerpt_box');
function bsi_hotel_remove_default_excerpt_box(): void
{
  remove_meta_box('postexcerpt', 'hotel', 'normal');
}

add_action('edit_form_after_title', 'bsi_hotel_render_excerpt_first', 1);
function bsi_hotel_render_excerpt_first(WP_Post $post): void
{
  if ($post->post_type !== 'hotel') {
    return;
  }
  echo '<div class="postbox" style="margin-top:20px;"><div class="postbox-header"><h2 class="hndle">Отрывок</h2></div><div class="inside">';
  post_excerpt_meta_box($post);
  echo '</div></div>';
}

add_action('acf/init', 'bsi_register_amenity_term_meta');
function bsi_register_amenity_term_meta(): void
{
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_amenity_term_meta',
    'title' => 'Удобство — иконка',
    'fields' => [
      [
        'key' => 'field_amenity_icon',
        'label' => 'Иконка',
        'name' => 'amenity_icon',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'thumbnail',
        'library' => 'all',
      ],
    ],
    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'amenity',
        ],
      ],
    ],
  ]);
}

add_filter('acf/fields/post_object/query/key=field_hotel_country', 'bsi_filter_hotel_country_parent_only', 10, 3);
function bsi_filter_hotel_country_parent_only(array $args, array $field, $post_id): array
{
  $args['post_parent'] = 0;
  return $args;
}

add_filter('pre_insert_term', 'bsi_block_numeric_terms', 10, 2);
function bsi_block_numeric_terms($term, string $taxonomy)
{
  if (!in_array($taxonomy, ['region', 'resort'], true)) {
    return $term;
  }

  $name = is_array($term) ? ($term['name'] ?? '') : (string) $term;
  $name = trim((string) $name);

  if ($name !== '' && preg_match('/^\d+$/', $name)) {
    return new WP_Error('bsi_numeric_term_blocked', 'Нельзя создавать термин из одних цифр.');
  }

  return $term;
}
