<?php

/**
 * CLI единого импорта со старого сайта (локальная разработка).
 *
 * На проде доступа к CLI нет — там «Настройки сайта → Импорт со старого сайта»
 * (inc/admin/legacy-import.php). Общая логика обоих способов — importer.php.
 *
 *   php import.php --file=data/che.json [--status=publish|draft] [--dry-run] [--limit=10]
 *   php import.php --all [--status=publish]
 *
 * Записи, текст которых правили после импорта, повторный прогон не перетирает
 * и считает отдельно («сохранено правленых»). Снимается флагом --force.
 *
 * Аргументы читаем ДО подключения wp-load.php: он перетирает глобальные
 * переменные (в частности $file).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

/**
 * Разбор аргументов + путь к wp-load.php.
 *
 * @return array{paths: string[], status: string, dry_run: bool, limit: int, force: bool, wp_load: string}
 */
function bsi_legacy_unified_cli_input(): array
{
  $options = getopt('', ['file:', 'all', 'status:', 'wp:', 'limit:', 'dry-run', 'force']);

  $paths = [];
  if (isset($options['all'])) {
    $paths = glob(__DIR__ . '/data/*.json') ?: [];
    if (!$paths) {
      exit("В data/ нет ни одного JSON — сначала запусти export.php --all\n");
    }
    sort($paths);
  } else {
    $path = (string) ($options['file'] ?? '');
    if ($path === '') {
      exit("Укажи --file=data/{код}.json или --all\n");
    }
    if (!str_starts_with($path, '/')) {
      $path = __DIR__ . '/' . $path;
    }
    if (!is_readable($path)) {
      exit("Нет файла: $path\n");
    }
    $paths = [$path];
  }

  $status = (string) ($options['status'] ?? 'draft');
  if (!in_array($status, ['publish', 'draft'], true)) {
    exit("--status: publish или draft\n");
  }

  $wp_load = (string) ($options['wp'] ?? '');
  if ($wp_load === '') {
    $dir = __DIR__;
    for ($i = 0; $i < 6; $i++) {
      $dir = dirname($dir);
      if (is_readable($dir . '/wp-load.php')) {
        $wp_load = $dir . '/wp-load.php';
        break;
      }
    }
  }
  if ($wp_load === '' || !is_readable($wp_load)) {
    exit("Не найден wp-load.php — укажи --wp=/path/to/wp-load.php\n");
  }

  return [
    'paths' => $paths,
    'status' => $status,
    'dry_run' => isset($options['dry-run']),
    'limit' => isset($options['limit']) ? (int) $options['limit'] : 0,
    'force' => isset($options['force']),
    'wp_load' => $wp_load,
  ];
}

function bsi_legacy_unified_cli_run(array $input): void
{
  require_once __DIR__ . '/importer.php';

  if (!function_exists('update_field')) {
    exit("ACF не активен — импорт полей невозможен.\n");
  }

  $total = ['exc_created' => 0, 'exc_updated' => 0, 'sight_created' => 0, 'sight_updated' => 0, 'conflicts' => 0, 'protected' => 0];

  foreach ($input['paths'] as $path) {
    $loaded = bsi_legacy_unified_load_payload($path);
    if (is_wp_error($loaded)) {
      printf("%s: %s\n", basename($path), $loaded->get_error_message());
      continue;
    }

    $payload = $loaded['payload'];

    printf(
      "\n=== %s (ID %d) — экскурсий %d, достопримечательностей %d, статус %s%s\n",
      $loaded['country_title'],
      $loaded['country_id'],
      count((array) ($payload['excursions'] ?? [])),
      count((array) ($payload['sights'] ?? [])),
      $input['status'],
      ($input['dry_run'] ? ' [dry-run]' : '') . ($input['force'] ? ' [force]' : '')
    );

    $stats = bsi_legacy_unified_import($payload, $loaded['country_id'], $input['status'], $input['dry_run'], $input['limit'], $input['force']);

    foreach (['excursions' => 'Экскурсии', 'sights' => 'Достопримечательности'] as $key => $label) {
      printf(
        "  %s: создано %d, обновлено %d, сохранено правленых %d, пропущено %d, конфликтов %d\n",
        $label,
        $stats[$key]['created'],
        $stats[$key]['updated'],
        $stats[$key]['protected'],
        $stats[$key]['skipped'],
        $stats[$key]['conflicts']
      );
    }

    foreach (['excursions', 'sights'] as $key) {
      foreach ($stats[$key]['log'] as $line) {
        if (str_starts_with($line, 'регион') || str_starts_with($line, 'курорт') || str_starts_with($line, '!') || str_starts_with($line, 'сохранена') || str_contains($line, 'конфликт')) {
          echo '    ' . $line . "\n";
        }
      }
    }

    $total['exc_created'] += $stats['excursions']['created'];
    $total['exc_updated'] += $stats['excursions']['updated'];
    $total['sight_created'] += $stats['sights']['created'];
    $total['sight_updated'] += $stats['sights']['updated'];
    $total['conflicts'] += $stats['excursions']['conflicts'] + $stats['sights']['conflicts'];
    $total['protected'] += $stats['excursions']['protected'] + $stats['sights']['protected'];
  }

  printf(
    "\nИТОГО: экскурсий создано %d (обновлено %d), достопримечательностей создано %d (обновлено %d), сохранено правленых вручную %d, конфликтов %d\n",
    $total['exc_created'],
    $total['exc_updated'],
    $total['sight_created'],
    $total['sight_updated'],
    $total['protected'],
    $total['conflicts']
  );
}

$bsi_legacy_unified_cli_input = bsi_legacy_unified_cli_input();

define('WP_USE_THEMES', false);
require $bsi_legacy_unified_cli_input['wp_load'];

bsi_legacy_unified_cli_run($bsi_legacy_unified_cli_input);
