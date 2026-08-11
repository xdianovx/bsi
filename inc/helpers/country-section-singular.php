<?php
/**
 * Записи-разделы страны без собственной single-страницы.
 *
 * CPT `tourist_memo`, `entry_rules`, `hotel_info`, `hotel_deposit` — это
 * контент разделов страны (`/country/{slug}/pamyatka/` и т.п.), а не
 * самостоятельные страницы. При этом они `public`, поэтому WordPress по
 * умолчанию отдаёт их по `/?{post_type}={slug}` и рендерит дефолтным
 * `single.php` темы — кривая страница-дубль, доступная поисковикам.
 *
 * Регистратор ниже закрывает это для всех таких CPT разом:
 *  - `post_type_link` — «Просмотреть» в админке и любые get_permalink()
 *    ведут на раздел страны;
 *  - `template_redirect` — прямой заход и `?p=ID` отдают 301 туда же
 *    (страна не выбрана — 404, показывать запись отдельно нечем).
 */

if (!function_exists('bsi_country_section_country_id')) {
  /**
   * Страна, к которой привязана запись раздела.
   *
   * @param string $meta_key ACF-поле связи (например `memo_country`).
   */
  function bsi_country_section_country_id(int $post_id, string $meta_key): int
  {
    if ($post_id <= 0) {
      return 0;
    }

    $value = function_exists('get_field')
      ? get_field($meta_key, $post_id)
      : get_post_meta($post_id, $meta_key, true);

    if ($value instanceof WP_Post) {
      return (int) $value->ID;
    }

    if (is_array($value)) {
      $value = reset($value);

      if ($value instanceof WP_Post) {
        return (int) $value->ID;
      }
    }

    return (int) $value;
  }
}

if (!function_exists('bsi_country_section_url')) {
  /**
   * URL раздела страны для записи. '' — страна не выбрана или удалена.
   *
   * @param string $section_slug Слаг раздела: `pamyatka`, `depozity`, ...
   */
  function bsi_country_section_url(int $post_id, string $meta_key, string $section_slug): string
  {
    $country_id = bsi_country_section_country_id($post_id, $meta_key);
    if (!$country_id) {
      return '';
    }

    $country_slug = (string) get_post_field('post_name', $country_id);
    if ($country_slug === '') {
      return '';
    }

    return trailingslashit(home_url('/country/' . $country_slug . '/' . $section_slug));
  }
}

if (!function_exists('bsi_register_country_section_singular')) {
  /**
   * Вешает на CPT правильную ссылку и редирект с одиночной записи.
   *
   * @param string $post_type    CPT записи-раздела.
   * @param string $meta_key     ACF-поле связи со страной.
   * @param string $section_slug Слаг раздела в URL страны.
   */
  function bsi_register_country_section_singular(string $post_type, string $meta_key, string $section_slug): void
  {
    add_filter('post_type_link', function ($url, $post) use ($post_type, $meta_key, $section_slug) {
      if (!($post instanceof WP_Post) || $post->post_type !== $post_type) {
        return $url;
      }

      $section_url = bsi_country_section_url((int) $post->ID, $meta_key, $section_slug);

      return $section_url !== '' ? $section_url : $url;
    }, 10, 2);

    add_action('template_redirect', function () use ($post_type, $meta_key, $section_slug) {
      if (!is_singular($post_type)) {
        return;
      }

      /* Предпросмотр черновика из админки не трогаем: на разделе страны
         неопубликованной записи нет, редирект увёл бы редактора «в никуда». */
      if (is_preview()) {
        return;
      }

      $section_url = bsi_country_section_url((int) get_queried_object_id(), $meta_key, $section_slug);

      if ($section_url === '') {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        return;
      }

      wp_safe_redirect($section_url, 301);
      exit;
    });
  }
}
