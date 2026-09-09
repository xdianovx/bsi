<?php

/**
 * CPT `sight` — достопримечательности страны.
 *
 * - Single URL: /country/{country_slug}/dostoprimechatelnosti/{sight_slug}/
 * - Каталог в стране: /country/{slug}/dostoprimechatelnosti/ (query_var `country_sights`,
 *   rewrite зарегистрирован в inc/post-types/country.php)
 * - Связь со страной — ACF `sight_country` (post_object на CPT country)
 * - География — общие таксономии `region` (Шотландия, Уэльс…) и `resort` (город)
 * - Координаты — ACF `sight_map_coordinates` в формате «широта, долгота»,
 *   рендер через js/modules/maps.js (Яндекс.Карты v3, атрибуты data-lat/data-lng)
 * - Источник данных — старый сайт на Битриксе, таблица `t_sights`; `sight_legacy_id`
 *   хранит S_ID для идемпотентного импорта.
 */

add_filter('region_taxonomy_post_types', function ($types) {
  $types[] = 'sight';
  return array_values(array_unique($types));
}, 5);

add_filter('resort_taxonomy_post_types', function ($types) {
  $types[] = 'sight';
  return array_values(array_unique($types));
}, 5);

add_action('init', function () {

  register_taxonomy('sight_type', ['sight'], [
    'labels' => [
      'name' => 'Типы достопримечательностей',
      'singular_name' => 'Тип достопримечательности',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип',
      'new_item_name' => 'Новый тип',
      'menu_name' => 'Типы достопримечательностей',
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

add_action('init', function () {

  register_post_type('sight', [
    'labels' => [
      'name' => 'Достопримечательности',
      'singular_name' => 'Достопримечательность',
      'add_new' => 'Добавить достопримечательность',
      'add_new_item' => 'Новая достопримечательность',
      'edit_item' => 'Редактировать достопримечательность',
      'new_item' => 'Новая достопримечательность',
      'view_item' => 'Смотреть достопримечательность',
      'search_items' => 'Искать достопримечательности',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Достопримечательности',
    ],

    'public' => true,
    'publicly_queryable' => true,

    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 26,
    'menu_icon' => 'dashicons-location',

    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions'],

    'taxonomies' => ['region', 'resort', 'sight_type'],

    'has_archive' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);

}, 10);

add_action('init', function () {
  if (taxonomy_exists('region')) {
    register_taxonomy_for_object_type('region', 'sight');
  }
  if (taxonomy_exists('resort')) {
    register_taxonomy_for_object_type('resort', 'sight');
  }
  if (taxonomy_exists('sight_type')) {
    register_taxonomy_for_object_type('sight_type', 'sight');
  }
}, 30);

/* ───────────────────────────────────────────────────────────────────
 * Single URL: /country/{country_slug}/dostoprimechatelnosti/{sight_slug}/
 * Каталог и резервирование slug — в country.php.
 * ─────────────────────────────────────────────────────────────────── */

add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/dostoprimechatelnosti/([^/]+)/?$',
    'index.php?post_type=sight&name=$matches[2]&country_in_path=$matches[1]',
    'top'
  );
}, 25);

add_filter('post_type_link', function ($post_link, $post) {
  if ($post->post_type !== 'sight') {
    return $post_link;
  }
  if (empty($post->post_name)) {
    return $post_link;
  }
  $country_id = bsi_get_sight_country_id((int) $post->ID);
  if (!$country_id) {
    return $post_link;
  }
  $country_slug = get_post_field('post_name', $country_id);
  if (!$country_slug) {
    return $post_link;
  }
  return trailingslashit(home_url('/country/' . $country_slug . '/dostoprimechatelnosti/' . $post->post_name));
}, 10, 2);

add_action('template_redirect', function () {
  if (!is_singular('sight')) {
    return;
  }

  $country_in_path = (string) get_query_var('country_in_path');

  $sight_id = get_queried_object_id();
  if (!$sight_id) {
    return;
  }

  $country_id = bsi_get_sight_country_id((int) $sight_id);

  /* Заход мимо ЧПУ (/?sight={slug}, /?p=ID) — 301 на канонический URL,
     иначе запись доступна по двум адресам. Страны нет — показывать негде, 404. */
  if ($country_in_path === '') {
    if (is_preview()) {
      return;
    }

    if (!$country_id) {
      global $wp_query;
      $wp_query->set_404();
      status_header(404);
      nocache_headers();
      return;
    }

    $canonical = get_permalink($sight_id);
    if ($canonical) {
      wp_safe_redirect($canonical, 301);
      exit;
    }

    return;
  }

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
 * Helpers
 * ─────────────────────────────────────────────────────────────────── */

if (!function_exists('bsi_get_sight_country_id')) {
  function bsi_get_sight_country_id(int $post_id): int
  {
    if ($post_id <= 0 || !function_exists('get_field')) {
      return 0;
    }
    $value = get_field('sight_country', $post_id);
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

if (!function_exists('bsi_get_sight_coordinates')) {
  /**
   * Координаты достопримечательности из ACF-строки «широта, долгота».
   *
   * @return array{lat:float, lng:float}|null
   */
  function bsi_get_sight_coordinates(int $post_id): ?array
  {
    if ($post_id <= 0 || !function_exists('get_field')) {
      return null;
    }

    $raw = trim((string) get_field('sight_map_coordinates', $post_id));
    if ($raw === '') {
      return null;
    }

    $parts = array_map('trim', explode(',', $raw));
    if (count($parts) !== 2) {
      return null;
    }

    $lat = (float) str_replace(',', '.', $parts[0]);
    $lng = (float) str_replace(',', '.', $parts[1]);

    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
      return null;
    }
    if ($lat === 0.0 && $lng === 0.0) {
      return null;
    }

    return ['lat' => $lat, 'lng' => $lng];
  }
}

/* ───────────────────────────────────────────────────────────────────
 * ACF: поля достопримечательности
 * ─────────────────────────────────────────────────────────────────── */

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_sight_main',
    'title' => 'Достопримечательность — основное',
    'position' => 'normal',
    'menu_order' => 10,
    'fields' => [
      [
        'key' => 'field_sight_country',
        'label' => 'Страна',
        'name' => 'sight_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'multiple' => 0,
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'instructions' => 'Страна, которой принадлежит достопримечательность. От неё зависит URL: /country/{slug}/dostoprimechatelnosti/{slug}/.',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_sight_short',
        'label' => 'Короткое описание',
        'name' => 'sight_short',
        'type' => 'textarea',
        'rows' => 3,
        'new_lines' => '',
        'maxlength' => 400,
        'instructions' => 'Две-три строки для карточки в каталоге и для подсказки на карте.',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_sight_address',
        'label' => 'Адрес',
        'name' => 'sight_address',
        'type' => 'text',
        'placeholder' => 'Например: Westminster, London SW1A 0AA',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_sight_map_coordinates',
        'label' => 'Координаты на карте',
        'name' => 'sight_map_coordinates',
        'type' => 'text',
        'instructions' => 'Вставьте одну строку: широта, долгота. Например: 51.500729, -0.124625',
        'placeholder' => '51.500729, -0.124625',
        'wrapper' => ['width' => '66'],
      ],
      [
        'key' => 'field_sight_map_zoom',
        'label' => 'Zoom (карта)',
        'name' => 'sight_map_zoom',
        'type' => 'number',
        'min' => 1,
        'max' => 20,
        'step' => 1,
        'default_value' => 15,
        'wrapper' => ['width' => '34'],
      ],
      [
        'key' => 'field_sight_gallery',
        'label' => 'Галерея',
        'name' => 'sight_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 30,
      ],
      [
        'key' => 'field_sight_legacy_id',
        'label' => 'ID на старом сайте',
        'name' => 'sight_legacy_id',
        'type' => 'number',
        'instructions' => 'Поле заполняет импорт из старой базы (t_sights.S_ID). Вручную не трогать.',
        'readonly' => 1,
        'wrapper' => ['width' => '100'],
      ],
    ],
    'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'sight']]],
  ]);
});

/* ───────────────────────────────────────────────────────────────────
 * Хлебные крошки Yoast
 * Каталог: Главная > Страны > {Страна} > Достопримечательности
 * Карточка: … > Достопримечательности > {Название}
 * ─────────────────────────────────────────────────────────────────── */

add_filter('wpseo_breadcrumb_links', function ($links) {
  $country_id = 0;
  $is_single = false;

  $catalog_slug = get_query_var('country_sights');
  if (!empty($catalog_slug)) {
    $country = get_page_by_path($catalog_slug, OBJECT, 'country');
    $country_id = $country ? (int) $country->ID : 0;
  } elseif (is_singular('sight')) {
    $country_id = bsi_get_sight_country_id((int) get_queried_object_id());
    $is_single = true;
  }

  if (!$country_id) {
    return $links;
  }

  $countries_page = get_page_by_path('strany');
  $countries_url = $countries_page ? get_permalink($countries_page->ID) : get_post_type_archive_link('country');

  $new = [];
  $new[] = ['url' => home_url('/'), 'text' => 'Главная'];

  if ($countries_url) {
    $new[] = ['url' => $countries_url, 'text' => $countries_page ? ($countries_page->post_title ?: 'Страны') : 'Страны'];
  }

  $new[] = ['url' => get_permalink($country_id), 'text' => get_the_title($country_id)];

  $catalog_url = home_url('/country/' . get_post_field('post_name', $country_id) . '/dostoprimechatelnosti/');

  if ($is_single) {
    $new[] = ['url' => $catalog_url, 'text' => 'Достопримечательности'];
    $new[] = ['text' => get_the_title(get_queried_object_id())];
  } else {
    $new[] = ['text' => 'Достопримечательности'];
  }

  return $new;
});

/* ───────────────────────────────────────────────────────────────────
 * Иконки типов достопримечательностей (Lucide)
 *
 * Иконка задаётся у термина `sight_type` полем `sight_type_icon`; если поле
 * пустое, подбирается по названию термина. Используется маркерами на карте
 * и бейджем типа на карточке.
 * ─────────────────────────────────────────────────────────────────── */

if (!function_exists('bsi_sight_type_icon_choices')) {
  /**
   * Белый список иконок: slug файла в img/icons/lucide => подпись в админке.
   *
   * @return array<string, string>
   */
  function bsi_sight_type_icon_choices(): array
  {
    return [
      'castle' => 'Замок',
      'landmark' => 'Музей / колоннада',
      'church' => 'Храм',
      'trees' => 'Природа',
      'ferris-wheel' => 'Развлечения',
      'wine' => 'Гастрономия',
      'waves' => 'Вода',
      'mountain-snow' => 'Горы',
      'camera' => 'Смотровая точка',
      'map-pin' => 'Точка на карте',
    ];
  }
}

if (!function_exists('bsi_sight_type_icon_fallback')) {
  /**
   * Иконка по названию термина — чтобы импортированные типы не требовали
   * ручной настройки сразу после переноса.
   */
  function bsi_sight_type_icon_fallback(string $term_name): string
  {
    $name = mb_strtolower($term_name);

    $rules = [
      'замок' => 'castle',
      'дворц' => 'castle',
      'музе' => 'landmark',
      'галере' => 'landmark',
      'храм' => 'church',
      'собор' => 'church',
      'природ' => 'trees',
      'парк' => 'trees',
      'развлеч' => 'ferris-wheel',
      'гастроном' => 'wine',
      'озер' => 'waves',
      'гор' => 'mountain-snow',
    ];

    foreach ($rules as $needle => $icon) {
      if (str_contains($name, $needle)) {
        return $icon;
      }
    }

    return 'map-pin';
  }
}

if (!function_exists('bsi_sight_type_icon')) {
  /**
   * Slug иконки для термина `sight_type`.
   */
  function bsi_sight_type_icon(int $term_id): string
  {
    if ($term_id <= 0) {
      return 'map-pin';
    }

    $icon = function_exists('get_field') ? (string) get_field('sight_type_icon', 'sight_type_' . $term_id) : '';
    $icon = trim($icon);

    if ($icon !== '' && isset(bsi_sight_type_icon_choices()[$icon])) {
      return $icon;
    }

    $term = get_term($term_id, 'sight_type');

    return ($term instanceof WP_Term) ? bsi_sight_type_icon_fallback($term->name) : 'map-pin';
  }
}

if (!function_exists('bsi_sight_icon_inner')) {
  /**
   * Внутренности <svg> иконки Lucide из img/icons/lucide/<slug>.svg.
   * Свой ридер, а не bsi_ui_icon_svg(): тот ограничен белым списком
   * иконок страхования, и добавление наших туда засорило бы его пикер.
   */
  function bsi_sight_icon_inner(string $slug): string
  {
    static $cache = [];

    if (isset($cache[$slug])) {
      return $cache[$slug];
    }

    if (!isset(bsi_sight_type_icon_choices()[$slug])) {
      return '';
    }

    $path = get_template_directory() . '/img/icons/lucide/' . $slug . '.svg';
    if (!is_readable($path)) {
      return '';
    }

    $svg = (string) file_get_contents($path);

    if (!preg_match('~<svg[^>]*>(.*)</svg>~is', $svg, $matches)) {
      return '';
    }

    $cache[$slug] = trim($matches[1]);

    return $cache[$slug];
  }
}

if (!function_exists('bsi_sight_icon_markup')) {
  /**
   * Готовый <svg> иконки типа.
   */
  function bsi_sight_icon_markup(string $slug, int $size = 20, string $class = ''): string
  {
    $inner = bsi_sight_icon_inner($slug);
    if ($inner === '') {
      return '';
    }

    return sprintf(
      '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="%2$s" aria-hidden="true">%3$s</svg>',
      $size,
      esc_attr($class),
      $inner
    );
  }
}

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_sight_type_icon',
    'title' => 'Иконка типа',
    'fields' => [
      [
        'key' => 'field_sight_type_icon',
        'label' => 'Иконка',
        'name' => 'sight_type_icon',
        'type' => 'select',
        'choices' => bsi_sight_type_icon_choices(),
        'allow_null' => 1,
        'ui' => 1,
        'instructions' => 'Маркер этого типа на карте и значок на карточке. Пусто — подберётся по названию типа.',
      ],
    ],
    'location' => [[['param' => 'taxonomy', 'operator' => '==', 'value' => 'sight_type']]],
  ]);
});
