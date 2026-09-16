<?php

/**
 * Единый импорт со старого сайта: экскурсии и достопримечательности из одного
 * файла data/{code}.json. Требует загруженный WordPress.
 *
 * Ядро обеих сущностей не дублируется — переиспользуются
 * tools/legacy-excursions/importer.php и tools/legacy-sights/importer.php.
 * Здесь только общий загрузчик payload и прогон обеих сущностей подряд.
 *
 * РАЗОВАЯ МИГРАЦИЯ, ЗАВЕРШЕНА. Страница в админке удалена 2026-09-15:
 * импорт перезаписывал ручные правки редакторов (описания и координаты
 * Великобритании). Код оставлен только как CLI-архив миграции.
 *
 * Точка входа одна: CLI `tools/legacy-import/import.php`, запускать локально
 * и только осознанно. На проде способа запустить импорт больше нет.
 *
 * Импорт идемпотентный: записи ищутся по мете `bsi_legacy_excursion_id`
 * и `bsi_legacy_sight_id`.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__DIR__) . '/legacy-excursions/importer.php';
require_once dirname(__DIR__) . '/legacy-sights/importer.php';

/**
 * Файлы data/*.json единого экспорта.
 *
 * @return array<string, string> имя файла => полный путь
 */
function bsi_legacy_unified_available_files(): array
{
  $dir = __DIR__ . '/data';
  if (!is_dir($dir)) {
    return [];
  }

  $files = [];
  foreach (glob($dir . '/*.json') ?: [] as $path) {
    $files[basename($path)] = $path;
  }

  ksort($files);

  return $files;
}

/**
 * Чтение payload единого экспорта + поиск страны.
 *
 * @return array{payload: array, country_id: int, country_title: string}|WP_Error
 */
function bsi_legacy_unified_load_payload(string $path)
{
  if (!is_readable($path)) {
    return new WP_Error('bsi_legacy_no_file', 'Файл не найден: ' . $path);
  }

  $payload = json_decode((string) file_get_contents($path), true);
  if (!is_array($payload)) {
    return new WP_Error('bsi_legacy_bad_json', 'Битый JSON: ' . basename($path));
  }

  $excursions = (array) ($payload['excursions'] ?? []);
  $sights = (array) ($payload['sights'] ?? []);
  if (!$excursions && !$sights) {
    return new WP_Error('bsi_legacy_bad_json', 'В файле нет ни экскурсий, ни достопримечательностей: ' . basename($path));
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

/**
 * Прогон обеих сущностей.
 *
 * Записи, текст которых правили после импорта, повторный прогон не трогает —
 * это поведение по умолчанию, снимается только `$force`.
 *
 * @param array{excursions?: array, sights?: array} $payload
 * @return array{excursions: array, sights: array}
 */
function bsi_legacy_unified_import(array $payload, int $country_id, string $status, bool $dry_run, int $limit = 0, bool $force = false): array
{
  $excursions = (array) ($payload['excursions'] ?? []);
  $sights = (array) ($payload['sights'] ?? []);

  if ($limit > 0) {
    $excursions = array_slice($excursions, 0, $limit);
    $sights = array_slice($sights, 0, $limit);
  }

  return [
    'excursions' => $excursions
      ? bsi_legacy_import_items($excursions, $country_id, $status, $dry_run, $force)
      : ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'protected' => 0, 'with_prices' => 0, 'log' => []],
    'sights' => $sights
      ? bsi_legacy_import_sights($sights, $country_id, $status, $dry_run, $force)
      : ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'protected' => 0, 'typed' => 0, 'log' => []],
  ];
}
