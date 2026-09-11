<?php

/**
 * Перенос координат из локального WordPress обратно в JSON экспорта.
 *
 * Геокодер (geocode.php) — CLI-скрипт: на проде запустить его негде, там только
 * FTP и админка. Поэтому точки считаются локально, складываются в тот же JSON,
 * а прод получает их обычным импортом (importer.php читает поле `coordinates`).
 *
 *   php sync-coords.php --file=data/gbr.json [--dry-run]
 *
 * Локально запускать php из MAMP — системный не видит сокет MySQL:
 *   /Applications/MAMP/bin/php/php8.3.14/bin/php sync-coords.php …
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

/**
 * Аргументы уезжают в константы: wp-load.php перетирает глобальные переменные
 * (у $file уже был случай — путь подменился на файл ядра, и JSON записался туда).
 *
 * @return array{file:string, dry_run:bool, wp_load:string}
 */
function bsi_sync_coords_input(): array
{
  $options = getopt('', ['file:', 'wp:', 'dry-run']);

  $file = (string) ($options['file'] ?? 'data/gbr.json');
  if (!str_starts_with($file, '/')) {
    $file = __DIR__ . '/' . $file;
  }

  $wp_load = (string) ($options['wp'] ?? '');
  if ($wp_load === '') {
    $wp_load = dirname(__DIR__, 5) . '/wp-load.php';
  }

  return [
    'file' => $file,
    'dry_run' => isset($options['dry-run']),
    'wp_load' => $wp_load,
  ];
}

$bsi_sync_input = bsi_sync_coords_input();

define('BSI_SYNC_FILE', $bsi_sync_input['file']);
define('BSI_SYNC_DRY_RUN', $bsi_sync_input['dry_run']);

if (!is_readable($bsi_sync_input['wp_load'])) {
  exit("Не найден wp-load.php: {$bsi_sync_input['wp_load']} (передайте --wp=/путь/wp-load.php)\n");
}

if (!is_readable(BSI_SYNC_FILE)) {
  exit('Не найден JSON: ' . BSI_SYNC_FILE . "\n");
}

$payload = json_decode((string) file_get_contents(BSI_SYNC_FILE), true);
if (!is_array($payload) || empty($payload['items'])) {
  exit('Пустой или битый JSON: ' . BSI_SYNC_FILE . "\n");
}

define('WP_USE_THEMES', false);
require $bsi_sync_input['wp_load'];

if (!function_exists('get_field')) {
  exit("ACF не активен — координаты читать нечем.\n");
}

$filled = 0;
$empty = 0;

foreach ($payload['items'] as &$item) {
  $legacy_id = (int) ($item['legacy_id'] ?? 0);
  if ($legacy_id <= 0) {
    continue;
  }

  $found = get_posts([
    'post_type' => 'sight',
    'post_status' => 'any',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'meta_query' => [
      ['key' => 'bsi_legacy_sight_id', 'value' => $legacy_id, 'compare' => '='],
    ],
  ]);

  if (empty($found)) {
    $empty++;
    continue;
  }

  $post_id = (int) $found[0];
  $coords = trim((string) get_field('sight_map_coordinates', $post_id));

  if ($coords === '') {
    $empty++;
    continue;
  }

  $item['coordinates'] = $coords;
  $source = (string) get_post_meta($post_id, 'bsi_sight_coords_source', true);
  if ($source !== '') {
    $item['coordinates_source'] = $source;
  }

  $filled++;
}
unset($item);

printf("Записей с координатами: %d, без координат: %d%s\n", $filled, $empty, BSI_SYNC_DRY_RUN ? ' [dry-run]' : '');

if (BSI_SYNC_DRY_RUN) {
  exit(0);
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
  exit("Не удалось закодировать JSON\n");
}

file_put_contents(BSI_SYNC_FILE, $json . "\n");

printf("Записано: %s\n", BSI_SYNC_FILE);
