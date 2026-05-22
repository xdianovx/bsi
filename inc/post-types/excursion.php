<?php

/**
 * CPT `excursion` — экскурсии страны.
 *
 * - Single URL: /excursion/{slug}/ (плоский, как event)
 * - Каталог в стране: /country/{slug}/ekskursii/ (query_var `country_excursions`,
 *   rewrite зарегистрирован в inc/post-types/country.php, шаблон country-excursions.php,
 *   роутер в single-country.php)
 * - Связь со страной — ACF `excursion_country` (post_object на CPT country)
 * - Бронирование — только внешний URL (ACF `excursion_booking_url`)
 * - Цены — repeater `excursion_dates`, конвертация через bsi_education_convert_price_to_rub()
 */

add_filter('region_taxonomy_post_types', function ($types) {
  $types[] = 'excursion';
  return array_values(array_unique($types));
}, 5);

add_filter('resort_taxonomy_post_types', function ($types) {
  $types[] = 'excursion';
  return array_values(array_unique($types));
}, 5);

add_action('init', function () {

  register_taxonomy('excursion_type', ['excursion'], [
    'labels' => [
      'name' => 'Типы экскурсий',
      'singular_name' => 'Тип экскурсии',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип',
      'new_item_name' => 'Новый тип',
      'menu_name' => 'Типы экскурсий',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => true,
    'rewrite' => false,
    'query_var' => true,
  ]);

  register_taxonomy('excursion_language', ['excursion'], [
    'labels' => [
      'name' => 'Языки гида',
      'singular_name' => 'Язык гида',
      'search_items' => 'Найти язык',
      'all_items' => 'Все языки',
      'edit_item' => 'Редактировать язык',
      'update_item' => 'Обновить язык',
      'add_new_item' => 'Добавить язык',
      'new_item_name' => 'Новый язык',
      'menu_name' => 'Языки гида',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);

  register_taxonomy('excursion_include', ['excursion'], [
    'labels' => [
      'name' => 'Включено в экскурсию',
      'singular_name' => 'Пункт включено',
      'search_items' => 'Найти',
      'all_items' => 'Все пункты',
      'edit_item' => 'Редактировать',
      'update_item' => 'Обновить',
      'add_new_item' => 'Добавить пункт',
      'new_item_name' => 'Новый пункт',
      'menu_name' => 'Включено в экскурсию',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);

}, 9);

add_action('init', function () {

  register_post_type('excursion', [
    'labels' => [
      'name' => 'Экскурсии',
      'singular_name' => 'Экскурсия',
      'add_new' => 'Добавить экскурсию',
      'add_new_item' => 'Новая экскурсия',
      'edit_item' => 'Редактировать экскурсию',
      'new_item' => 'Новая экскурсия',
      'view_item' => 'Смотреть экскурсию',
      'search_items' => 'Искать экскурсии',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Экскурсии',
    ],

    'public' => true,
    'publicly_queryable' => true,

    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 25,
    'menu_icon' => 'dashicons-tickets-alt',

    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions'],

    'taxonomies' => ['region', 'resort', 'excursion_type', 'excursion_language', 'excursion_include'],

    'has_archive' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);

}, 10);

add_action('init', function () {
  if (taxonomy_exists('region')) {
    register_taxonomy_for_object_type('region', 'excursion');
  }
  if (taxonomy_exists('resort')) {
    register_taxonomy_for_object_type('resort', 'excursion');
  }
  if (taxonomy_exists('excursion_type')) {
    register_taxonomy_for_object_type('excursion_type', 'excursion');
  }
  if (taxonomy_exists('excursion_language')) {
    register_taxonomy_for_object_type('excursion_language', 'excursion');
  }
  if (taxonomy_exists('excursion_include')) {
    register_taxonomy_for_object_type('excursion_include', 'excursion');
  }
}, 30);

/* ───────────────────────────────────────────────────────────────────
 * Single URL: /country/{country_slug}/ekskursii/{excursion_slug}/
 * Каталог /country/{country_slug}/ekskursii/ зарегистрирован в country.php.
 * Резервный slug `ekskursii` исключён из тройного regex там же.
 * ─────────────────────────────────────────────────────────────────── */

add_filter('query_vars', function ($vars) {
  if (!in_array('country_in_path', $vars, true)) {
    $vars[] = 'country_in_path';
  }
  return $vars;
});

add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/ekskursii/([^/]+)/?$',
    'index.php?post_type=excursion&name=$matches[2]&country_in_path=$matches[1]',
    'top'
  );
}, 25);

add_filter('post_type_link', function ($post_link, $post) {
  if ($post->post_type !== 'excursion') {
    return $post_link;
  }
  if (empty($post->post_name)) {
    return $post_link;
  }
  $country_id = function_exists('bsi_get_excursion_country_id') ? bsi_get_excursion_country_id((int) $post->ID) : 0;
  if (!$country_id) {
    return $post_link;
  }
  $country_slug = get_post_field('post_name', $country_id);
  if (!$country_slug) {
    return $post_link;
  }
  return trailingslashit(home_url('/country/' . $country_slug . '/ekskursii/' . $post->post_name));
}, 10, 2);

add_action('template_redirect', function () {
  if (!is_singular('excursion')) {
    return;
  }

  $country_in_path = (string) get_query_var('country_in_path');
  if ($country_in_path === '') {
    return;
  }

  $excursion_id = get_queried_object_id();
  if (!$excursion_id) {
    return;
  }

  $country_id = function_exists('bsi_get_excursion_country_id') ? bsi_get_excursion_country_id((int) $excursion_id) : 0;
  if (!$country_id) {
    return;
  }

  $real_country_slug = get_post_field('post_name', $country_id);
  if ($real_country_slug && $real_country_slug !== $country_in_path) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    return;
  }
});

/* ───────────────────────────────────────────────────────────────────
 * Helpers: страна экскурсии + минимальная цена в RUB
 * ─────────────────────────────────────────────────────────────────── */

if (!function_exists('bsi_get_excursion_country_id')) {
  function bsi_get_excursion_country_id(int $post_id): int
  {
    if ($post_id <= 0 || !function_exists('get_field')) {
      return 0;
    }
    $value = get_field('excursion_country', $post_id);
    if ($value instanceof WP_Post) {
      return (int) $value->ID;
    }
    if (is_array($value)) {
      $first = reset($value);
      if ($first instanceof WP_Post) {
        return (int) $first->ID;
      }
      return (int) $first;
    }
    return (int) $value;
  }
}

if (!function_exists('bsi_get_excursion_tickets_rows')) {
  /**
   * Нормализованные строки `excursion_tickets` repeater (типы билетов и их цены).
   *
   * @return array<int, array{name:string, description:string, amount:?float, currency:string, price_rub:?int, price_original:?float, price_currency:?string}>
   */
  function bsi_get_excursion_tickets_rows(int $post_id): array
  {
    if ($post_id <= 0 || !function_exists('get_field')) {
      return [];
    }

    $rows = get_field('excursion_tickets', $post_id);
    if (!is_array($rows) || empty($rows)) {
      return [];
    }

    $out = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $name = isset($row['ticket_name']) ? trim((string) $row['ticket_name']) : '';
      $description = isset($row['ticket_description']) ? trim((string) $row['ticket_description']) : '';

      $amount_raw = $row['ticket_price_amount'] ?? null;
      $amount = ($amount_raw !== null && $amount_raw !== '') ? (float) $amount_raw : null;

      $currency = isset($row['ticket_price_currency']) ? strtoupper(trim((string) $row['ticket_price_currency'])) : 'RUB';
      if ($currency === '') {
        $currency = 'RUB';
      }

      $price_rub = null;
      $price_original = null;
      $price_currency = null;
      if ($amount !== null && $amount > 0 && function_exists('bsi_education_convert_price_to_rub')) {
        $converted = bsi_education_convert_price_to_rub($amount, $currency);
        if ($converted !== null && $converted > 0) {
          $price_rub = (int) $converted;
          if ($currency !== 'RUB') {
            $price_original = $amount;
            $price_currency = $currency;
          }
        }
      }

      $out[] = [
        'name' => $name,
        'description' => $description,
        'amount' => $amount,
        'currency' => $currency,
        'price_rub' => $price_rub,
        'price_original' => $price_original,
        'price_currency' => $price_currency,
      ];
    }

    return $out;
  }
}

if (!function_exists('bsi_get_excursion_price_from_rub')) {
  /**
   * Минимальная цена в RUB по строкам `excursion_tickets`. Null если валидных цен нет.
   */
  function bsi_get_excursion_price_from_rub(int $post_id): ?int
  {
    $rows = bsi_get_excursion_tickets_rows($post_id);
    $rubs = [];
    foreach ($rows as $r) {
      if (isset($r['price_rub']) && (int) $r['price_rub'] > 0) {
        $rubs[] = (int) $r['price_rub'];
      }
    }
    return !empty($rubs) ? min($rubs) : null;
  }
}

if (!function_exists('bsi_get_excursion_price_from_original')) {
  /**
   * Оригинальная валюта/сумма, соответствующая минимальной RUB-цене.
   *
   * @return array{amount: ?float, currency: ?string}
   */
  function bsi_get_excursion_price_from_original(int $post_id): array
  {
    $rows = bsi_get_excursion_tickets_rows($post_id);
    if (empty($rows)) {
      return ['amount' => null, 'currency' => null];
    }
    $min_rub = null;
    foreach ($rows as $r) {
      if (isset($r['price_rub']) && (int) $r['price_rub'] > 0) {
        if ($min_rub === null || (int) $r['price_rub'] < $min_rub) {
          $min_rub = (int) $r['price_rub'];
        }
      }
    }
    if ($min_rub === null) {
      return ['amount' => null, 'currency' => null];
    }
    foreach ($rows as $r) {
      if ((int) $r['price_rub'] === $min_rub && !empty($r['price_currency']) && !empty($r['price_original'])) {
        return ['amount' => (float) $r['price_original'], 'currency' => (string) $r['price_currency']];
      }
    }
    return ['amount' => null, 'currency' => null];
  }
}

/* ───────────────────────────────────────────────────────────────────
 * Кеш кандидатов по стране (паттерн tour.php)
 * ─────────────────────────────────────────────────────────────────── */

if (!function_exists('bsi_country_excursion_candidate_ids_cache_version')) {
  function bsi_country_excursion_candidate_ids_cache_version(): int
  {
    return max(1, (int) get_option('bsi_country_excursion_ids_cache_ver', 1));
  }
}

if (!function_exists('bsi_country_excursion_candidate_ids_cache_bump')) {
  function bsi_country_excursion_candidate_ids_cache_bump(): void
  {
    $v = bsi_country_excursion_candidate_ids_cache_version();
    update_option('bsi_country_excursion_ids_cache_ver', $v + 1, false);
  }
}

if (!function_exists('bsi_country_excursion_candidate_ids_transient_key')) {
  function bsi_country_excursion_candidate_ids_transient_key(int $country_id): string
  {
    return 'bsi_excursion_ids_country_' . (int) $country_id . '_' . bsi_country_excursion_candidate_ids_cache_version();
  }
}

if (!function_exists('bsi_get_country_excursion_candidate_ids_uncached')) {
  function bsi_get_country_excursion_candidate_ids_uncached(int $country_id): array
  {
    $country_id = (int) $country_id;
    if ($country_id <= 0) {
      return [];
    }

    $q = new WP_Query([
      'post_type'              => 'excursion',
      'post_status'            => 'publish',
      'posts_per_page'         => -1,
      'fields'                 => 'ids',
      'meta_query'             => [
        [
          'key' => 'excursion_country',
          'value' => $country_id,
          'compare' => '=',
        ],
      ],
      'orderby'                => 'title',
      'order'                  => 'ASC',
      'no_found_rows'          => true,
      'bsi_skip_schedule'      => true,
      'update_post_meta_cache' => false,
      'update_post_term_cache' => false,
    ]);

    return array_values(
      array_unique(
        array_filter(
          array_map('intval', is_array($q->posts ?? null) ? $q->posts : [])
        )
      )
    );
  }
}

if (!function_exists('bsi_get_country_excursion_candidate_ids_cached')) {
  function bsi_get_country_excursion_candidate_ids_cached(int $country_id): array
  {
    $country_id = (int) $country_id;
    if ($country_id <= 0) {
      return [];
    }

    if (is_user_logged_in() && current_user_can('edit_posts')) {
      return bsi_get_country_excursion_candidate_ids_uncached($country_id);
    }

    $key = bsi_country_excursion_candidate_ids_transient_key($country_id);
    $cached = get_transient($key);
    if (is_array($cached)) {
      return array_values(array_unique(array_filter(array_map('intval', $cached))));
    }

    $ids = bsi_get_country_excursion_candidate_ids_uncached($country_id);
    set_transient($key, $ids, 15 * MINUTE_IN_SECONDS);
    return $ids;
  }
}

add_action('transition_post_status', static function (string $new_status, string $old_status, WP_Post $post): void {
  if (($post->post_type ?? '') !== 'excursion') {
    return;
  }
  if ($old_status !== 'publish' && $new_status !== 'publish') {
    return;
  }
  bsi_country_excursion_candidate_ids_cache_bump();
}, 10, 3);

add_action('save_post_excursion', static function (int $post_id, WP_Post $post, bool $update): void {
  if (
    wp_is_post_revision($post_id)
    || (defined('DOING_AUTOSAVE') && constant('DOING_AUTOSAVE'))
    || !$update
    || $post->post_status !== 'publish'
  ) {
    return;
  }
  bsi_country_excursion_candidate_ids_cache_bump();
}, 99, 3);

/* ───────────────────────────────────────────────────────────────────
 * ACF: поля экскурсии (паттерн custom-post-event.php)
 * ─────────────────────────────────────────────────────────────────── */

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  $currency_choices = [
    'RUB' => 'RUB',
    'USD' => 'USD',
    'EUR' => 'EUR',
    'GBP' => 'GBP',
  ];

  acf_add_local_field_group([
    'key' => 'group_excursion_main',
    'title' => 'Экскурсия — основное',
    'position' => 'normal',
    'menu_order' => 10,
    'fields' => [
      [
        'key' => 'field_excursion_country',
        'label' => 'Страна',
        'name' => 'excursion_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'multiple' => 0,
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'instructions' => 'Страна, которой принадлежит экскурсия. Экскурсия будет видна в меню страны и в каталоге /country/{slug}/ekskursii/.',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_excursion_duration_hours',
        'label' => 'Длительность (часов)',
        'name' => 'excursion_duration_hours',
        'type' => 'number',
        'min' => 0,
        'step' => 0.5,
        'wrapper' => ['width' => '33'],
      ],
      [
        'key' => 'field_excursion_phone',
        'label' => 'Телефон',
        'name' => 'excursion_phone',
        'type' => 'text',
        'placeholder' => '+7 (___) ___-__-__',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_excursion_website',
        'label' => 'Сайт',
        'name' => 'excursion_website',
        'type' => 'url',
        'placeholder' => 'https://...',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_excursion_cta_lead',
        'label' => 'Текст под формой консультации (внизу страницы)',
        'name' => 'excursion_cta_lead',
        'type' => 'text',
        'default_value' => 'Оставьте заявку — менеджер свяжется в течение дня и поможет подобрать удобную дату.',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_excursion_gallery',
        'label' => 'Галерея',
        'name' => 'excursion_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 30,
      ],
    ],
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'excursion']]],
  ]);

  acf_add_local_field_group([
    'key' => 'group_excursion_program',
    'title' => 'Экскурсия — программа по дням',
    'position' => 'normal',
    'menu_order' => 20,
    'fields' => [
      [
        'key' => 'field_excursion_program',
        'label' => 'Программа',
        'name' => 'excursion_program',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить день',
        'sub_fields' => [
          [
            'key' => 'field_excursion_program_day_title',
            'label' => 'Заголовок дня',
            'name' => 'day_title',
            'type' => 'text',
            'wrapper' => ['width' => '30'],
            'placeholder' => 'День 1 / Утро / ...',
          ],
          [
            'key' => 'field_excursion_program_day_content',
            'label' => 'Описание',
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
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'excursion']]],
  ]);

  acf_add_local_field_group([
    'key' => 'group_excursion_included',
    'title' => 'Экскурсия — включено / не включено',
    'position' => 'normal',
    'menu_order' => 30,
    'fields' => [
      [
        'key' => 'field_excursion_included',
        'label' => 'В стоимость включено',
        'name' => 'excursion_included',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
      [
        'key' => 'field_excursion_not_included',
        'label' => 'Не включено',
        'name' => 'excursion_not_included',
        'type' => 'wysiwyg',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 0,
      ],
    ],
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'excursion']]],
  ]);

  acf_add_local_field_group([
    'key' => 'group_excursion_tickets',
    'title' => 'Экскурсия — билеты и цены',
    'position' => 'normal',
    'menu_order' => 40,
    'fields' => [
      [
        'key' => 'field_excursion_tickets',
        'label' => 'Типы билетов',
        'name' => 'excursion_tickets',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить билет',
        'instructions' => 'Каждая строка — отдельный тип билета (взрослый, детский, эконом, премиум и т.п.). Цена указывается в валюте оплаты; в каталоге и на странице экскурсии она конвертируется в рубли по курсу ЦБ + наценка. Переключатель «Стоимость в валюте» возвращает оригинальную цену.',
        'sub_fields' => [
          [
            'key' => 'field_excursion_ticket_name',
            'label' => 'Название билета',
            'name' => 'ticket_name',
            'type' => 'text',
            'placeholder' => 'Например: Взрослый, Детский, Эконом',
            'wrapper' => ['width' => '30'],
          ],
          [
            'key' => 'field_excursion_ticket_description',
            'label' => 'Описание (опц.)',
            'name' => 'ticket_description',
            'type' => 'text',
            'wrapper' => ['width' => '35'],
          ],
          [
            'key' => 'field_excursion_ticket_price_amount',
            'label' => 'Сумма',
            'name' => 'ticket_price_amount',
            'type' => 'number',
            'min' => 0,
            'step' => 0.01,
            'wrapper' => ['width' => '20'],
          ],
          [
            'key' => 'field_excursion_ticket_price_currency',
            'label' => 'Валюта',
            'name' => 'ticket_price_currency',
            'type' => 'select',
            'choices' => $currency_choices,
            'default_value' => 'RUB',
            'allow_null' => 0,
            'wrapper' => ['width' => '15'],
          ],
        ],
      ],
    ],
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'excursion']]],
  ]);

  acf_add_local_field_group([
    'key' => 'group_excursion_faq',
    'title' => 'Экскурсия — FAQ',
    'position' => 'normal',
    'menu_order' => 50,
    'fields' => [
      [
        'key' => 'field_excursion_faq',
        'label' => 'Вопросы и ответы',
        'name' => 'excursion_faq',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить вопрос',
        'sub_fields' => [
          [
            'key' => 'field_excursion_faq_question',
            'label' => 'Вопрос',
            'name' => 'question',
            'type' => 'text',
            'wrapper' => ['width' => '30'],
          ],
          [
            'key' => 'field_excursion_faq_answer',
            'label' => 'Ответ',
            'name' => 'answer',
            'type' => 'wysiwyg',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 0,
            'wrapper' => ['width' => '70'],
          ],
        ],
      ],
    ],
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'excursion']]],
  ]);

});

/* Ограничиваем post_object выбор страны корневыми (как у tour) */
add_filter('acf/fields/post_object/query/key=field_excursion_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);

/* ───────────────────────────────────────────────────────────────────
 * Колонки админ-списка
 * ─────────────────────────────────────────────────────────────────── */

add_filter('manage_excursion_posts_columns', function ($columns) {
  $new = [];
  foreach ($columns as $key => $value) {
    $new[$key] = $value;
    if ($key === 'title') {
      $new['excursion_country_col'] = 'Страна';
    }
  }
  return $new;
});

add_action('manage_excursion_posts_custom_column', function ($column, $post_id) {
  if ($column !== 'excursion_country_col') {
    return;
  }
  $country_id = bsi_get_excursion_country_id((int) $post_id);
  echo $country_id ? esc_html(get_the_title($country_id)) : '—';
}, 10, 2);

/* ───────────────────────────────────────────────────────────────────
 * Yoast breadcrumbs
 * ─────────────────────────────────────────────────────────────────── */

add_filter('wpseo_breadcrumb_links', function ($links) {

  $country_slug = (string) get_query_var('country_excursions');
  if ($country_slug !== '') {
    $country = get_page_by_path($country_slug, OBJECT, 'country');
    if (!$country) {
      return $links;
    }

    $countries_page = get_page_by_path('strany');
    $new = [];
    $new[] = ['url' => home_url('/'), 'text' => 'Главная'];
    if ($countries_page) {
      $new[] = ['url' => get_permalink($countries_page->ID), 'text' => $countries_page->post_title ?: 'Страны'];
    } else {
      $new[] = ['url' => get_post_type_archive_link('country'), 'text' => 'Страны'];
    }
    $new[] = ['url' => get_permalink($country->ID), 'text' => $country->post_title];
    $new[] = ['text' => 'Экскурсии'];

    return $new;
  }

  if (is_singular('excursion')) {
    $excursion_id = get_queried_object_id();
    if (!$excursion_id) {
      return $links;
    }

    $country_id = bsi_get_excursion_country_id((int) $excursion_id);
    if (!$country_id) {
      return $links;
    }

    $country_slug_local = get_post_field('post_name', $country_id);
    $country_title = get_the_title($country_id);
    if (!$country_slug_local || !$country_title) {
      return $links;
    }

    $countries_page = get_page_by_path('strany');
    $countries_url = $countries_page ? get_permalink($countries_page->ID) : get_post_type_archive_link('country');

    $excursions_list_url = home_url('/country/' . $country_slug_local . '/ekskursii/');

    $new = [];
    $new[] = ['url' => home_url('/'), 'text' => 'Главная'];
    if ($countries_url) {
      $new[] = ['url' => $countries_url, 'text' => $countries_page ? ($countries_page->post_title ?: 'Страны') : 'Страны'];
    }
    $new[] = ['url' => get_permalink($country_id), 'text' => $country_title];
    $new[] = ['url' => $excursions_list_url, 'text' => 'Экскурсии'];
    $new[] = ['text' => get_the_title($excursion_id)];

    return $new;
  }

  return $links;
});

add_filter('wpseo_canonical', function ($canonical) {
  if (!is_singular('excursion')) {
    return $canonical;
  }
  $excursion_id = get_queried_object_id();
  if (!$excursion_id) {
    return $canonical;
  }
  $slug = get_post_field('post_name', $excursion_id);
  if (!$slug) {
    return $canonical;
  }
  $country_id = function_exists('bsi_get_excursion_country_id') ? bsi_get_excursion_country_id((int) $excursion_id) : 0;
  if (!$country_id) {
    return $canonical;
  }
  $country_slug = get_post_field('post_name', $country_id);
  if (!$country_slug) {
    return $canonical;
  }
  return trailingslashit(home_url('/country/' . $country_slug . '/ekskursii/' . $slug));
});
