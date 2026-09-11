<?php

/**
 * Временно скрытые разделы сайта.
 *
 * Раздел остаётся доступен по прямой ссылке (контент и URL не трогаем),
 * но исчезает из меню и сеток на сайте и закрывается от индексации:
 * `noindex, nofollow` + исключение из sitemap Yoast.
 *
 * Чтобы вернуть раздел — убрать его из bsi_hidden_sections().
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Скрытые разделы: ключ → шаблон страницы (или '' если шаблона нет).
 *
 * @return array<string, string>
 */
function bsi_hidden_sections(): array
{
  return [
    'cruise' => 'page-cruise.php',
  ];
}

/**
 * ID страниц скрытых разделов (кеш на запрос).
 *
 * @return int[]
 */
function bsi_hidden_section_page_ids(): array
{
  static $ids = null;
  if ($ids !== null) {
    return $ids;
  }

  $ids = [];
  foreach (bsi_hidden_sections() as $template) {
    if ($template === '') {
      continue;
    }

    $pages = get_posts([
      'post_type' => 'page',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
      'meta_key' => '_wp_page_template',
      'meta_value' => $template,
      'no_found_rows' => true,
    ]);

    foreach ($pages as $page_id) {
      $ids[] = (int) $page_id;
    }
  }

  return $ids;
}

function bsi_is_hidden_section_page(int $post_id): bool
{
  return in_array($post_id, bsi_hidden_section_page_ids(), true);
}

/* Пункты WP-меню, ведущие на скрытые страницы. */
add_filter('wp_get_nav_menu_items', function ($items) {
  if (!is_array($items) || is_admin()) {
    return $items;
  }

  $hidden = bsi_hidden_section_page_ids();
  if (empty($hidden)) {
    return $items;
  }

  return array_values(array_filter($items, static function ($item) use ($hidden): bool {
    return !($item->object === 'page' && in_array((int) $item->object_id, $hidden, true));
  }));
}, 10);

/* Списки страниц (get_pages) — сетки и подборки на фронте. */
add_filter('get_pages', function ($pages) {
  if (!is_array($pages) || is_admin()) {
    return $pages;
  }

  $hidden = bsi_hidden_section_page_ids();
  if (empty($hidden)) {
    return $pages;
  }

  return array_values(array_filter($pages, static function ($page) use ($hidden): bool {
    return !in_array((int) $page->ID, $hidden, true);
  }));
}, 10);

/* Индексация: noindex на самой странице. */
add_filter('wpseo_robots', function ($robots) {
  if (is_page() && bsi_is_hidden_section_page((int) get_queried_object_id())) {
    return 'noindex, nofollow';
  }
  return $robots;
}, 20);

add_filter('wpseo_robots_array', function ($robots) {
  if (is_page() && bsi_is_hidden_section_page((int) get_queried_object_id())) {
    $robots['index'] = 'noindex';
    $robots['follow'] = 'nofollow';
  }
  return $robots;
}, 20);

/* Если Yoast отключат — свой мета-тег. */
add_action('wp_head', function () {
  if (defined('WPSEO_VERSION')) {
    return;
  }
  if (is_page() && bsi_is_hidden_section_page((int) get_queried_object_id())) {
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
  }
}, 1);

/* Sitemap Yoast. */
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function ($excluded) {
  $excluded = is_array($excluded) ? $excluded : [];
  return array_values(array_unique(array_merge($excluded, bsi_hidden_section_page_ids())));
});
