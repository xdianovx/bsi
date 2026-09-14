<?php

/**
 * Ядро импорта достопримечательностей старого сайта. Требует загруженный WordPress.
 *
 * Ядро вызывается из единого импорта (tools/legacy-import/) вместе с экскурсиями.
 *
 * Импорт идемпотентный: запись ищется по мете `bsi_legacy_sight_id`.
 * Записи, текст которых правили после импорта, повторный прогон не перетирает —
 * см. bsi_legacy_is_edited_by_hand() в ядре экскурсий.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/* Термины region/resort создаются той же логикой, что у экскурсий. */
require_once get_template_directory() . '/tools/legacy-excursions/importer.php';

/**
 * Термин sight_type по имени: находим либо создаём.
 */
function bsi_legacy_sight_type_id(string $name, bool $dry_run, array &$log): int
{
  $name = trim($name);
  if ($name === '') {
    return 0;
  }

  $term = get_term_by('name', $name, 'sight_type');
  if ($term instanceof WP_Term) {
    return (int) $term->term_id;
  }

  if ($dry_run) {
    $log[] = "тип (создать): $name";
    return 0;
  }

  $created = wp_insert_term($name, 'sight_type');
  if (is_wp_error($created)) {
    $log[] = "! тип не создан: $name — " . $created->get_error_message();
    return 0;
  }

  $log[] = "тип создан: $name (ID {$created['term_id']})";

  return (int) $created['term_id'];
}

/**
 * Существующая запись по legacy-идентификатору.
 */
function bsi_legacy_find_sight(int $legacy_id): int
{
  $found = get_posts([
    'post_type' => 'sight',
    'post_status' => 'any',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => [
      ['key' => 'bsi_legacy_sight_id', 'value' => $legacy_id, 'compare' => '='],
    ],
    'no_found_rows' => true,
  ]);

  return $found ? (int) $found[0] : 0;
}

/**
 * Чужая запись (заведённая руками) с тем же слагом или заголовком.
 * Такие не перезаписываем и не дублируем — только показываем в отчёте.
 */
function bsi_legacy_find_sight_conflict(string $slug, string $title): int
{
  $candidates = get_posts([
    'post_type' => 'sight',
    'post_status' => 'any',
    'posts_per_page' => 5,
    'fields' => 'ids',
    'name' => $slug,
    'no_found_rows' => true,
  ]);

  if (empty($candidates)) {
    $candidates = get_posts([
      'post_type' => 'sight',
      'post_status' => 'any',
      'posts_per_page' => 5,
      'fields' => 'ids',
      'title' => $title,
      'no_found_rows' => true,
    ]);
  }

  foreach ($candidates as $candidate_id) {
    if (!get_post_meta((int) $candidate_id, 'bsi_legacy_sight_id', true)) {
      return (int) $candidate_id;
    }
  }

  return 0;
}

/**
 * Импорт среза записей.
 *
 * @param array  $items      элементы JSON (уже нарезанные для батча)
 * @param int    $country_id ID записи CPT country
 * @param string $status     'publish' | 'draft'
 * @param bool   $dry_run    только посчитать, ничего не писать
 * @param bool   $force      перезаписывать записи, правленые руками после импорта
 *
 * @return array{created:int, updated:int, skipped:int, conflicts:int, protected:int, typed:int, log:string[]}
 */
function bsi_legacy_import_sights(array $items, int $country_id, string $status, bool $dry_run, bool $force = false): array
{
  $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'protected' => 0, 'typed' => 0, 'log' => []];

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
    $type_id = !empty($item['sight_type'])
      ? bsi_legacy_sight_type_id((string) $item['sight_type'], $dry_run, $stats['log'])
      : 0;

    $post_id = bsi_legacy_find_sight($legacy_id);

    /* Слаг: код старого сайта, иначе транслит заголовка.
       Числовые коды вида «420222» не годятся — из них не читается адрес. */
    $slug = sanitize_title((string) ($item['slug'] ?? ''));
    if ($slug === '' || ctype_digit($slug) || str_starts_with($slug, '%')) {
      $slug = sanitize_title(bsi_legacy_translit($title));
    }

    if ($post_id === 0) {
      $conflict_id = bsi_legacy_find_sight_conflict($slug, $title);
      if ($conflict_id > 0) {
        $stats['conflicts']++;
        $stats['log'][] = sprintf('пропущена «%s» — уже есть запись без legacy-id (ID %d)', $title, $conflict_id);
        continue;
      }
    }

    /* Текст правили после импорта — повторный прогон его не перетирает.
       Координаты при этом не теряются: их заполняет отдельный прогон. */
    if ($post_id > 0 && !$force && bsi_legacy_is_edited_by_hand($post_id, $item)) {
      $stats['protected']++;
      $stats['log'][] = sprintf('сохранена «%s» (ID %d) — текст правили вручную', $title, $post_id);
      continue;
    }

    $postarr = [
      'post_type' => 'sight',
      'post_title' => $title,
      'post_name' => $slug,
      'post_content' => (string) ($item['content'] ?? ''),
      'post_excerpt' => (string) ($item['excerpt'] ?? ''),
      'post_status' => $status,
    ];

    if ($dry_run) {
      $stats[$post_id ? 'updated' : 'created']++;
      if (!empty($item['sight_type'])) {
        $stats['typed']++;
      }
      continue;
    }

    $is_new = $post_id === 0;

    if ($post_id > 0) {
      $postarr['ID'] = $post_id;
      /* Статус уже опубликованной записи повторный импорт не понижает. */
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

    /* Отпечаток снимаем с уже сохранённой записи: следующий прогон по нему
       отличит правку контент-команды от собственного результата импорта. */
    bsi_legacy_store_post_hash($post_id);

    /* Картинки импорт не трогает вообще: ни `_thumbnail_id`, ни галерея
       здесь не пишутся. Фото старого сайта не переносятся (72 ГБ остались там),
       а всё, что подобрала контент-команда, переживает любой повторный прогон. */

    update_post_meta($post_id, 'bsi_legacy_sight_id', $legacy_id);
    update_field('sight_country', $country_id, $post_id);
    update_field('sight_legacy_id', $legacy_id, $post_id);

    $short = trim((string) ($item['excerpt'] ?? ''));
    if ($short !== '') {
      update_field('sight_short', $short, $post_id);
    }

    /* Координаты приезжают уже посчитанными: геокодер — CLI-скрипт, на проде
       его запустить негде, поэтому точки складываются в JSON вместе с текстом.
       Заполненные вручную координаты (без меты источника) импорт не трогает. */
    $coords = trim((string) ($item['coordinates'] ?? ''));
    if ($coords !== '') {
      $existing = trim((string) get_field('sight_map_coordinates', $post_id));
      $existing_source = (string) get_post_meta($post_id, 'bsi_sight_coords_source', true);

      if ($existing === '' || $existing_source !== '') {
        update_field('sight_map_coordinates', $coords, $post_id);

        $coords_source = trim((string) ($item['coordinates_source'] ?? ''));
        if ($coords_source !== '') {
          update_post_meta($post_id, 'bsi_sight_coords_source', $coords_source);
        }
      }
    }

    bsi_legacy_assign_term($post_id, $resort_id, 'resort', $is_new);
    bsi_legacy_assign_term($post_id, $region_id, 'region', $is_new);

    if ($type_id > 0) {
      bsi_legacy_assign_term($post_id, $type_id, 'sight_type', $is_new);
      $stats['typed']++;
    }
  }

  return $stats;
}
