<?php

/**
 * Защита от записей без страны у CPT `excursion` и `sight`.
 *
 * URL этих записей строится фильтром `post_type_link` из слага страны:
 *   /country/{country_slug}/ekskursii/{slug}/
 *   /country/{country_slug}/dostoprimechatelnosti/{slug}/
 *
 * Если ACF-поле страны пустое, фильтр откатывается на служебный адрес
 * (`/?excursion=slug`), Yoast берёт его в canonical, а WordPress при
 * неразрешённом адресе отдаёт canonical главной страницы. Для поисковика
 * это значит «мой контент — это главная»: записи склеиваются с ней
 * и выпадают из индекса молча.
 *
 * Так на проде 14.09.2026 потерялись 30 достопримечательностей Японии —
 * см. wiki/docs/seo-audit-2026-09-14.md, находка C1.
 *
 * Здесь тихая поломка превращается в явную: запись без страны получает
 * `noindex` и не попадает в sitemap, а в админке видно предупреждение.
 * Выпасть из индекса заметно лучше, чем склеиться с главной.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Типы записей, у которых URL зависит от привязанной страны.
 *
 * @return array<string, string> тип записи => имя функции, отдающей ID страны
 */
function bsi_country_bound_post_types(): array
{
  return [
    'excursion' => 'bsi_get_excursion_country_id',
    'sight' => 'bsi_get_sight_country_id',
  ];
}

/**
 * У записи не проставлена страна, из-за чего её URL нельзя построить.
 */
function bsi_post_is_orphan_of_country(int $post_id): bool
{
  $post_type = get_post_type($post_id);
  $types = bsi_country_bound_post_types();

  if ($post_type === false || !isset($types[$post_type])) {
    return false;
  }

  $resolver = $types[$post_type];

  /* Резолвер живёт в файле своего CPT — на ранних хуках его может не быть. */
  if (!function_exists($resolver)) {
    return false;
  }

  return $resolver($post_id) <= 0;
}

/**
 * Записи без страны — `noindex`.
 */
add_filter('wp_robots', static function (array $robots): array {
  if (!is_singular(array_keys(bsi_country_bound_post_types()))) {
    return $robots;
  }

  if (!bsi_post_is_orphan_of_country(get_queried_object_id())) {
    return $robots;
  }

  unset($robots['index'], $robots['max-snippet'], $robots['max-image-preview'], $robots['max-video-preview']);
  $robots['noindex'] = true;
  $robots['follow'] = true;

  return $robots;
}, 20);

/**
 * Их же — вон из sitemap, чтобы не отправлять в индекс то, что закрыто.
 */
add_filter('wpseo_sitemap_entry', static function ($url, $type, $post) {
  if ($type !== 'post' || !$post instanceof WP_Post) {
    return $url;
  }

  return bsi_post_is_orphan_of_country((int) $post->ID) ? false : $url;
}, 10, 3);

/**
 * Предупреждение в списке записей: без страны запись не попадёт в поиск.
 */
add_action('admin_notices', static function (): void {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->base !== 'edit') {
    return;
  }

  $types = bsi_country_bound_post_types();
  if (!isset($types[$screen->post_type])) {
    return;
  }

  if (!current_user_can('edit_posts')) {
    return;
  }

  $orphans = get_posts([
    'post_type' => $screen->post_type,
    'post_status' => ['publish', 'draft', 'pending', 'future', 'private'],
    'posts_per_page' => 200,
    'fields' => 'ids',
    'no_found_rows' => true,
  ]);

  $broken = array_values(array_filter($orphans, 'bsi_post_is_orphan_of_country'));
  if (!$broken) {
    return;
  }

  $titles = array_map(static fn(int $id): string => get_the_title($id), array_slice($broken, 0, 5));

  printf(
    '<div class="notice notice-warning"><p><strong>Без страны: %d записей.</strong> '
      . 'Их адрес не собирается, поэтому они закрыты от индексации и убраны из карты сайта. '
      . 'Заполните поле страны: %s%s</p></div>',
    count($broken),
    esc_html(implode(', ', $titles)),
    count($broken) > 5 ? ' и другие' : ''
  );
});
