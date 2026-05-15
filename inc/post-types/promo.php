<?php

if (!defined('BSI_PROMO_BOOKING_CTA_LEAD_DEFAULT')) {
  define(
    'BSI_PROMO_BOOKING_CTA_LEAD_DEFAULT',
    'Оставьте заявку — менеджер свяжется с вами и подробно расскажет об условиях акции.'
  );
}

/**
 * Дата окончания акции (promo_date_to) в календарном прошлом — для публичной single вместо 404.
 */
function bsi_promo_is_past_promo_end_date(int $post_id): bool
{
  if (get_post_type($post_id) !== 'promo') {
    return false;
  }

  if (!function_exists('bsi_schedule_normalize_date')) {
    return false;
  }

  $raw_to = function_exists('get_field') ? get_field('promo_date_to', $post_id) : null;
  $to_ymd = bsi_schedule_normalize_date($raw_to);
  if ($to_ymd === null) {
    return false;
  }

  if (!class_exists('DateTimeImmutable')) {
    return false;
  }

  try {
    $tz = wp_timezone();
    $today = new DateTimeImmutable('today', $tz);
    $end = DateTimeImmutable::createFromFormat('!Ymd', $to_ymd, $tz);
    if (!$end instanceof DateTimeImmutable) {
      return false;
    }
    $end_day = $end->setTime(0, 0, 0);

    return $today > $end_day;
  } catch (Throwable $e) {
    return false;
  }
}

/**
 * Календарных полных дней от сегодня до promo_date_to (включительно по дню окончания); null если даты конца нет или срок уже вышел.
 */
function bsi_promo_calendar_days_until_end_from_raw($raw_to): ?int
{
  if (!function_exists('bsi_schedule_normalize_date') || !class_exists('DateTimeImmutable')) {
    return null;
  }

  $to_ymd = bsi_schedule_normalize_date($raw_to);
  if ($to_ymd === null) {
    return null;
  }

  try {
    $tz = wp_timezone();
    $today = new DateTimeImmutable('today', $tz);
    $end = DateTimeImmutable::createFromFormat('!Ymd', $to_ymd, $tz);
    if (!$end instanceof DateTimeImmutable) {
      return null;
    }
    $end_day = $end->setTime(0, 0, 0);
    if ($today > $end_day) {
      return null;
    }

    return (int) $today->diff($end_day)->days;
  } catch (Throwable $e) {
    return null;
  }
}

/**
 * Текст отсчёта до конца акции (< 10 дней), как на single-promo.
 */
function bsi_promo_countdown_public_message(?int $days_left): string
{
  if ($days_left === null || $days_left < 0 || $days_left >= 10) {
    return '';
  }

  if ($days_left <= 0) {
    return 'Сегодня — последний день акции';
  }

  if ($days_left === 1) {
    return 'До конца акции остался 1 день';
  }

  $mod100 = $days_left % 100;
  $mod10 = $days_left % 10;

  if ($mod100 >= 11 && $mod100 <= 14) {
    $day_word = 'дней';
  } elseif ($mod10 === 1) {
    $day_word = 'день';
  } elseif ($mod10 >= 2 && $mod10 <= 4) {
    $day_word = 'дня';
  } else {
    $day_word = 'дней';
  }

  return sprintf('До конца акции осталось %d %s', $days_left, $day_word);
}

/**
 * Относительный ЧПУ пути акции: /country/{страна}/promo/{slug}/.
 */
function bsi_promo_public_path_from_post(WP_Post $post): ?string
{
  if ($post->post_type !== 'promo' || $post->post_status !== 'publish' || $post->post_name === '') {
    return null;
  }

  $countries = function_exists('get_field') ? get_field('promo_countries', $post->ID) : null;

  if (is_array($countries) && !empty($countries)) {
    $country_id = (int) reset($countries);
  } else {
    $country_id = (int) $countries;
  }

  if ($country_id <= 0) {
    return null;
  }

  $country = get_post($country_id);
  if (
    !$country instanceof WP_Post
    || $country->post_type !== 'country'
    || $country->post_status !== 'publish'
    || $country->post_name === ''
  ) {
    return null;
  }

  return sprintf(
    '/country/%s/promo/%s/',
    $country->post_name,
    $post->post_name
  );
}

/**
 * Публичный URL акции (без дефолтного ?p= при привязке к стране).
 */
function bsi_get_promo_public_url(int $post_id): string
{
  $post = get_post($post_id);
  if (!$post instanceof WP_Post) {
    return '';
  }

  $path = bsi_promo_public_path_from_post($post);

  return $path !== null ? home_url($path) : get_permalink($post);
}

function bsi_register_promo_cpt()
{
  $labels = [
    'name' => 'Акции',
    'singular_name' => 'Акция',
    'menu_name' => 'Акции',
    'add_new' => 'Добавить акцию',
    'add_new_item' => 'Добавить акцию',
    'edit_item' => 'Редактировать акцию',
    'new_item' => 'Новая акция',
    'view_item' => 'Просмотр акции',
    'search_items' => 'Искать акции',
    'not_found' => 'Акции не найдены',
    'not_found_in_trash' => 'В корзине акций нет',
    'all_items' => 'Все акции',
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_position' => 20,
    'menu_icon' => 'dashicons-megaphone',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'rewrite' => [
      'slug' => 'akcii',
      'with_front' => false,
    ],
    'has_archive' => false,
    'publicly_queryable' => true,
    'show_in_rest' => true,
  ];

  register_post_type('promo', $args);
}

add_action('init', 'bsi_register_promo_cpt');

add_action('init', 'bsi_register_promo_type_taxonomy');

function bsi_register_promo_type_taxonomy()
{
  register_taxonomy('promo_type', ['promo'], [
    'labels' => [
      'name' => 'Типы акций',
      'singular_name' => 'Тип акции',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип акции',
      'new_item_name' => 'Новый тип акции',
      'menu_name' => 'Типы акций',
    ],
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => [
      'slug' => 'tip-akcii',
      'with_front' => false,
    ],
  ]);
}

add_filter('acf/fields/post_object/query/name=promo_countries', function ($args, $field, $post_id) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 3);

add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/promo/?$',
    'index.php?country_promos=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/promo/([^/]+)/?$',
    'index.php?post_type=promo&name=$matches[2]',
    'top'
  );
});

add_filter('query_vars', function ($vars) {
  $vars[] = 'country_promos';
  return $vars;
});

add_action('template_include', function ($template) {
  $country_slug = get_query_var('country_promos');

  if ($country_slug) {
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      global $country_promos_data;
      $country_promos_data = [
        'country' => $country,
        'country_slug' => $country_slug,
      ];

      $new_template = locate_template('country-promo.php');
      if ($new_template) {
        return $new_template;
      }
    } else {
      global $wp_query;
      $wp_query->set_404();
      status_header(404);
      return get_404_template();
    }
  }

  return $template;
});

add_filter('post_type_link', function ($post_link, $post) {
  if ($post->post_type === 'promo' && $post->post_status === 'publish') {
    $path = bsi_promo_public_path_from_post($post);

    if ($path !== null) {
      return home_url($path);
    }
  }

  return $post_link;
}, 10, 2);

add_filter('get_canonical_url', function ($canonical_url, $post) {
  if (!$post instanceof WP_Post || $post->post_type !== 'promo') {
    return $canonical_url;
  }

  $pretty = bsi_get_promo_public_url((int) $post->ID);

  return $pretty !== '' ? $pretty : $canonical_url;
}, 10, 2);

add_filter('wpseo_canonical', static function ($canonical) {
  if (!is_singular('promo')) {
    return $canonical;
  }

  $post_id = (int) get_queried_object_id();
  if ($post_id <= 0) {
    return $canonical;
  }

  $pretty = bsi_get_promo_public_url($post_id);

  return $pretty !== '' ? $pretty : $canonical;
}, 20);

add_filter('wpseo_breadcrumb_links', function ($links) {
  $country_slug = get_query_var('country_promos');

  if ($country_slug && !is_singular('promo')) {
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      $new_links = [];

      $new_links[] = [
        'url' => home_url('/'),
        'text' => 'Главная',
      ];

      $countries_archive = get_post_type_archive_link('country');
      if ($countries_archive) {
        $new_links[] = [
          'url' => $countries_archive,
          'text' => 'Страны',
        ];
      }

      $new_links[] = [
        'url' => get_permalink($country->ID),
        'text' => $country->post_title,
      ];

      $new_links[] = [
        'text' => 'Акции',
      ];

      return $new_links;
    }
  }

  return $links;
});

add_filter('wpseo_breadcrumb_links', function ($links) {
  if (is_singular('promo')) {
    $countries = get_field('promo_countries');

    if (is_array($countries) && !empty($countries)) {
      $country_id = reset($countries);
    } else {
      $country_id = $countries;
    }

    if ($country_id) {
      $country = get_post($country_id);

      if ($country) {
        $new_links = [];

        $new_links[] = [
          'url' => home_url('/'),
          'text' => 'Главная',
        ];

        $countries_archive = get_post_type_archive_link('country');
        if ($countries_archive) {
          $new_links[] = [
            'url' => $countries_archive,
            'text' => 'Страны',
          ];
        }

        $new_links[] = [
          'url' => get_permalink($country->ID),
          'text' => $country->post_title,
        ];

        $new_links[] = [
          'url' => home_url("/country/{$country->post_name}/promo/"),
          'text' => 'Акции',
        ];

        $new_links[] = [
          'text' => get_the_title(),
        ];

        return $new_links;
      }
    }
  }

  return $links;
});