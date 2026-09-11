<?php

/**
 * CLI-импорт достопримечательностей старого сайта (локальная разработка).
 *
 * На проде доступа к CLI нет — там страница «Настройки сайта →
 * Импорт достопримечательностей» (inc/admin/legacy-sights-import.php).
 * Общая логика обоих способов — importer.php.
 *
 *   php import.php --file=data/gbr.json [--status=publish|draft] [--dry-run] [--limit=10]
 *
 * Аргументы читаем ДО подключения wp-load.php: он перетирает глобальные переменные.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

/**
 * @return array{path: string, status: string, dry_run: bool, limit: int, wp_load: string}
 */
function bsi_legacy_sights_cli_input(): array
{
  $options = getopt('', ['file:', 'status:', 'wp:', 'limit:', 'dry-run']);

  $path = (string) ($options['file'] ?? __DIR__ . '/data/gbr.json');
  if (!str_starts_with($path, '/')) {
    $path = __DIR__ . '/' . $path;
  }
  if (!is_readable($path)) {
    exit("Нет файла: $path\n");
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
    'path' => $path,
    'status' => $status,
    'dry_run' => isset($options['dry-run']),
    'limit' => isset($options['limit']) ? (int) $options['limit'] : 0,
    'wp_load' => $wp_load,
  ];
}

function bsi_legacy_sights_cli_run(array $input): void
{
  require_once __DIR__ . '/importer.php';

  if (!function_exists('update_field')) {
    exit("ACF не активен — импорт полей невозможен.\n");
  }

  $loaded = bsi_legacy_sights_load_payload($input['path']);
  if (is_wp_error($loaded)) {
    exit($loaded->get_error_message() . "\n");
  }

  $items = $loaded['payload']['items'];
  if ($input['limit'] > 0) {
    $items = array_slice($items, 0, $input['limit']);
  }

  printf(
    "Страна: %s (ID %d), записей: %d, статус: %s%s\n\n",
    $loaded['country_title'],
    $loaded['country_id'],
    count($items),
    $input['status'],
    $input['dry_run'] ? ' [dry-run]' : ''
  );

  $stats = bsi_legacy_import_sights($items, $loaded['country_id'], $input['status'], $input['dry_run']);

  foreach ($stats['log'] as $line) {
    echo '  ' . $line . "\n";
  }

  printf(
    "\nСоздано: %d, обновлено: %d, пропущено: %d, конфликтов: %d, с типом: %d\n",
    $stats['created'],
    $stats['updated'],
    $stats['skipped'],
    $stats['conflicts'],
    $stats['typed']
  );
}

$bsi_legacy_sights_cli_input = bsi_legacy_sights_cli_input();

define('WP_USE_THEMES', false);
require $bsi_legacy_sights_cli_input['wp_load'];

bsi_legacy_sights_cli_run($bsi_legacy_sights_cli_input);
