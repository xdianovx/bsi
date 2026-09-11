<?php

/**
 * Ядро импорта экскурсий старого сайта. Требует загруженный WordPress.
 *
 * Используется двумя точками входа:
 *  - CLI: tools/legacy-excursions/import.php (локально);
 *  - админка: Инструменты → «Импорт экскурсий» (inc/admin/legacy-excursions-import.php),
 *    единственный способ на проде, где есть только FTP.
 *
 * Импорт идемпотентный: запись ищется по мете `bsi_legacy_excursion_id`.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Транслитерация кириллицы для слага (плагин cyr2lat недоступен в CLI).
 */
function bsi_legacy_translit(string $text): string
{
  $map = [
    'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
    'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm',
    'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
    'ф' => 'f', 'х' => 'h', 'ц' => 'cz', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh',
    'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
  ];

  return strtr(mb_strtolower($text), $map);
}

/**
 * Термин region по имени: находим существующий у этой страны либо создаём.
 */
function bsi_legacy_get_region_id(string $name, int $country_id, bool $dry_run, array &$log): int
{
  $name = trim($name);
  if ($name === '') {
    return 0;
  }

  $terms = get_terms([
    'taxonomy' => 'region',
    'name' => $name,
    'hide_empty' => false,
  ]);

  if (!is_wp_error($terms)) {
    foreach ($terms as $term) {
      $term_country = (int) get_field('region_country', 'region_' . $term->term_id);
      if ($term_country === $country_id || $term_country === 0) {
        if ($term_country === 0 && !$dry_run) {
          update_field('region_country', $country_id, 'region_' . $term->term_id);
        }
        return (int) $term->term_id;
      }
    }
  }

  if ($dry_run) {
    $log[] = "регион (создать): $name";
    return 0;
  }

  $created = wp_insert_term($name, 'region');
  if (is_wp_error($created)) {
    $log[] = "! регион не создан: $name — " . $created->get_error_message();
    return 0;
  }

  $term_id = (int) $created['term_id'];
  update_field('region_country', $country_id, 'region_' . $term_id);
  $log[] = "регион создан: $name (ID $term_id)";

  return $term_id;
}

/**
 * Термин resort по имени: находим существующий в нужном регионе либо создаём.
 */
function bsi_legacy_get_resort_id(string $name, int $region_id, bool $dry_run, array &$log): int
{
  $name = trim($name);
  if ($name === '') {
    return 0;
  }

  $terms = get_terms([
    'taxonomy' => 'resort',
    'name' => $name,
    'hide_empty' => false,
  ]);

  if (!is_wp_error($terms)) {
    foreach ($terms as $term) {
      $term_region = (int) get_field('resort_region', 'resort_' . $term->term_id);
      if ($term_region === $region_id || $term_region === 0) {
        if ($term_region === 0 && $region_id > 0 && !$dry_run) {
          update_field('resort_region', $region_id, 'resort_' . $term->term_id);
        }
        return (int) $term->term_id;
      }
    }
  }

  if ($dry_run) {
    $log[] = "курорт (создать): $name";
    return 0;
  }

  $created = wp_insert_term($name, 'resort');
  if (is_wp_error($created)) {
    $log[] = "! курорт не создан: $name — " . $created->get_error_message();
    return 0;
  }

  $term_id = (int) $created['term_id'];
  if ($region_id > 0) {
    update_field('resort_region', $region_id, 'resort_' . $term_id);
  }
  $log[] = "курорт создан: $name (ID $term_id)";

  return $term_id;
}

/**
 * Существующая запись по legacy-идентификатору.
 */
function bsi_legacy_find_excursion(int $legacy_id): int
{
  $found = get_posts([
    'post_type' => 'excursion',
    'post_status' => 'any',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => [
      ['key' => 'bsi_legacy_excursion_id', 'value' => $legacy_id, 'compare' => '='],
    ],
    'no_found_rows' => true,
  ]);

  return $found ? (int) $found[0] : 0;
}

/**
 * Чужая запись (не из импорта) с тем же слагом или заголовком.
 *
 * На проде экскурсии заводили руками — их нельзя ни перезаписать, ни продублировать,
 * поэтому такие совпадения пропускаем и показываем в отчёте.
 */
function bsi_legacy_find_conflict(string $slug, string $title): int
{
  $candidates = get_posts([
    'post_type' => 'excursion',
    'post_status' => 'any',
    'posts_per_page' => 5,
    'fields' => 'ids',
    'name' => $slug,
    'no_found_rows' => true,
  ]);

  if (empty($candidates)) {
    $candidates = get_posts([
      'post_type' => 'excursion',
      'post_status' => 'any',
      'posts_per_page' => 5,
      'fields' => 'ids',
      'title' => $title,
      'no_found_rows' => true,
    ]);
  }

  foreach ($candidates as $candidate_id) {
    if (!get_post_meta((int) $candidate_id, 'bsi_legacy_excursion_id', true)) {
      return (int) $candidate_id;
    }
  }

  return 0;
}

/**
 * Импорт среза записей.
 *
 * @param array  $items    элементы JSON (уже нарезанные для батча)
 * @param int    $country_id ID записи CPT country
 * @param string $status   'publish' | 'draft'
 * @param bool   $dry_run  только посчитать, ничего не писать
 *
 * @return array{created:int, updated:int, skipped:int, with_prices:int, log:string[]}
 */
function bsi_legacy_import_items(array $items, int $country_id, string $status, bool $dry_run): array
{
  $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'with_prices' => 0, 'log' => []];

  foreach ($items as $item) {
    $legacy_id = (int) ($item['legacy_id'] ?? 0);
    $title = trim((string) ($item['title'] ?? ''));

    if ($legacy_id <= 0 || $title === '') {
      $stats['skipped']++;
      continue;
    }

    $region_id = !empty($item['region'])
      ? bsi_legacy_get_region_id((string) $item['region'], $country_id, $dry_run, $stats['log'])
      : 0;
    $resort_id = !empty($item['city'])
      ? bsi_legacy_get_resort_id((string) $item['city'], $region_id, $dry_run, $stats['log'])
      : 0;

    $post_id = bsi_legacy_find_excursion($legacy_id);

    /* Слаг: код старого сайта, иначе транслит заголовка. */
    $slug = sanitize_title((string) ($item['slug'] ?? ''));
    if ($slug === '' || ctype_digit($slug) || str_starts_with($slug, '%')) {
      $slug = sanitize_title(bsi_legacy_translit($title));
    }

    /* Запись, заведённая руками, — не трогаем и не дублируем. */
    if ($post_id === 0) {
      $conflict_id = bsi_legacy_find_conflict($slug, $title);
      if ($conflict_id > 0) {
        $stats['conflicts']++;
        $stats['log'][] = sprintf('пропущена «%s» — уже есть запись без legacy-id (ID %d)', $title, $conflict_id);
        continue;
      }
    }

    $postarr = [
      'post_type' => 'excursion',
      'post_title' => $title,
      'post_name' => $slug,
      'post_content' => (string) ($item['content'] ?? ''),
      'post_excerpt' => (string) ($item['excerpt'] ?? ''),
      'post_status' => $status,
    ];

    if ($dry_run) {
      $stats[$post_id ? 'updated' : 'created']++;
      if (!empty($item['tickets'])) {
        $stats['with_prices']++;
      }
      continue;
    }

    if ($post_id > 0) {
      $postarr['ID'] = $post_id;
      /* Статус уже опубликованной записи не понижаем повторным импортом. */
      if (get_post_status($post_id) === 'publish' && $status === 'draft') {
        unset($postarr['post_status']);
      }
      $result = wp_update_post($postarr, true);
      $stats['updated']++;
    } else {
      $result = wp_insert_post($postarr, true);
      $stats['created']++;
    }

    if (is_wp_error($result)) {
      $stats['log'][] = "! ошибка записи «$title»: " . $result->get_error_message();
      continue;
    }

    $post_id = (int) $result;

    update_post_meta($post_id, 'bsi_legacy_excursion_id', $legacy_id);
    update_field('excursion_country', $country_id, $post_id);

    if (!empty($item['duration_hours'])) {
      update_field('excursion_duration_hours', (int) $item['duration_hours'], $post_id);
    }

    if ($resort_id > 0) {
      wp_set_object_terms($post_id, [$resort_id], 'resort', false);
    }
    if ($region_id > 0) {
      wp_set_object_terms($post_id, [$region_id], 'region', false);
    }

    if (!empty($item['tickets']) && is_array($item['tickets'])) {
      $rows = [];
      foreach ($item['tickets'] as $ticket) {
        $rows[] = [
          'ticket_name' => (string) ($ticket['name'] ?? 'Стоимость'),
          'ticket_description' => (string) ($ticket['description'] ?? ''),
          'ticket_price_amount' => (float) ($ticket['amount'] ?? 0),
          'ticket_price_currency' => (string) ($ticket['currency'] ?? 'RUB'),
        ];
      }
      update_field('excursion_tickets', $rows, $post_id);
      $stats['with_prices']++;
    }
  }

  return $stats;
}

/**
 * Список доступных JSON-файлов в data/.
 *
 * @return array<string, string> имя файла => полный путь
 */
function bsi_legacy_available_files(): array
{
  $dir = __DIR__ . '/data';
  $files = [];

  foreach ((array) glob($dir . '/*.json') as $path) {
    $files[basename($path)] = $path;
  }

  return $files;
}

/**
 * Чтение и проверка JSON. Возвращает WP_Error при проблеме.
 *
 * @return array{payload: array, country_id: int, country_title: string}|WP_Error
 */
function bsi_legacy_load_payload(string $path)
{
  if (!is_readable($path)) {
    return new WP_Error('bsi_legacy_no_file', 'Файл не найден: ' . $path);
  }

  $payload = json_decode((string) file_get_contents($path), true);
  if (!is_array($payload) || empty($payload['items'])) {
    return new WP_Error('bsi_legacy_bad_json', 'Пустой или битый JSON: ' . basename($path));
  }

  $country_slug = (string) ($payload['country_slug'] ?? '');
  $country = $country_slug !== '' ? get_page_by_path($country_slug, OBJECT, 'country') : null;
  if (!$country) {
    return new WP_Error('bsi_legacy_no_country', "Не найдена страна CPT country со слагом «$country_slug»");
  }

  return [
    'payload' => $payload,
    'country_id' => (int) $country->ID,
    'country_title' => (string) $country->post_title,
  ];
}
