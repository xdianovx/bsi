<?php

/**
 * Перенос координат достопримечательностей из локального WordPress обратно в JSON.
 *
 * Геокодер (tools/legacy-sights/geocode.php) — CLI-скрипт: на проде запустить его
 * негде, там только FTP и админка. Поэтому точки считаются локально, складываются
 * в тот же JSON, а прод получает их обычным импортом — `bsi_legacy_import_sights()`
 * читает поля `coordinates` и `coordinates_source`.
 *
 *   php sync-coords.php --file=data/che.json [--dry-run]
 *   php sync-coords.php --all
 *
 * Локально запускать php из MAMP — системный не видит сокет MySQL:
 *   /Applications/MAMP/bin/php/php8.3.14/bin/php sync-coords.php --all
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

/**
 * Аргументы уезжают в константы: wp-load.php перетирает глобальные переменные
 * (у $file уже был случай — путь подменился на файл ядра, и JSON записался туда).
 *
 * @return array{files: string[], dry_run: bool, wp_load: string}
 */
function bsi_sync_coords_input(): array
{
  $options = getopt('', ['file:', 'all', 'wp:', 'dry-run']);

  if (isset($options['all'])) {
    $files = glob(__DIR__ . '/data/*.json') ?: [];
    if (!$files) {
      exit("В data/ нет ни одного JSON\n");
    }
    sort($files);
  } else {
    $file = (string) ($options['file'] ?? '');
    if ($file === '') {
      exit("Укажи --file=data/{код}.json или --all\n");
    }
    if (!str_starts_with($file, '/')) {
      $file = __DIR__ . '/' . $file;
    }
    if (!is_readable($file)) {
      exit("Не найден JSON: $file\n");
    }
    $files = [$file];
  }

  $wp_load = (string) ($options['wp'] ?? '');
  if ($wp_load === '') {
    $wp_load = dirname(__DIR__, 5) . '/wp-load.php';
  }
  if (!is_readable($wp_load)) {
    exit("Не найден wp-load.php: $wp_load (передайте --wp=/путь/wp-load.php)\n");
  }

  return [
    'files' => $files,
    'dry_run' => isset($options['dry-run']),
    'wp_load' => $wp_load,
  ];
}

$bsi_sync_input = bsi_sync_coords_input();

define('BSI_SYNC_FILES', $bsi_sync_input['files']);
define('BSI_SYNC_DRY_RUN', $bsi_sync_input['dry_run']);

define('WP_USE_THEMES', false);
require $bsi_sync_input['wp_load'];

if (!function_exists('get_field')) {
  exit("ACF не активен — координаты читать нечем.\n");
}

foreach (BSI_SYNC_FILES as $path) {
  $payload = json_decode((string) file_get_contents($path), true);
  if (!is_array($payload) || empty($payload['sights'])) {
    printf("%s: достопримечательностей нет, пропуск\n", basename($path));
    continue;
  }

  $filled = 0;
  $empty = 0;

  foreach ($payload['sights'] as &$item) {
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

  printf("%s: с координатами %d, без координат %d%s\n", basename($path), $filled, $empty, BSI_SYNC_DRY_RUN ? ' [dry-run]' : '');

  if (BSI_SYNC_DRY_RUN) {
    continue;
  }

  $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  if ($json === false) {
    printf("! %s: не удалось закодировать JSON\n", basename($path));
    continue;
  }

  file_put_contents($path, $json . "\n");
}
