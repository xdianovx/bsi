<?php

/**
 * Разовая конвертация уже загруженных картинок в WebP.
 *
 * Новые загрузки обрабатывает хук в inc/helpers/webp.php, а этот скрипт
 * догоняет всё, что лежало в медиатеке до его появления.
 *
 * Локально:
 *   php tools/convert-webp.php --wp=/path/to/wp-load.php [--limit=50] [--only-banners] [--dry-run]
 *
 * На проде CLI нет — там конвертацию запускает страница
 * «Инструменты → Конвертация в WebP» (inc/admin/webp-convert.php).
 *
 * Аргументы читаем ДО подключения wp-load.php: он перетирает глобальные переменные.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

/**
 * @return array{wp_load: string, limit: int, only_banners: bool, dry_run: bool}
 */
function bsi_webp_cli_input(): array
{
  $options = getopt('', ['wp:', 'limit:', 'only-banners', 'dry-run']);

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
    'wp_load' => $wp_load,
    'limit' => isset($options['limit']) ? (int) $options['limit'] : 0,
    'only_banners' => isset($options['only-banners']),
    'dry_run' => isset($options['dry-run']),
  ];
}

/**
 * ID картинок баннеров главной — самое ценное для LCP.
 *
 * @return int[]
 */
function bsi_webp_banner_ids(): array
{
  $front_id = (int) get_option('page_on_front');
  if ($front_id <= 0 || !function_exists('get_field')) {
    return [];
  }

  $ids = [];

  foreach ((array) (get_field('banners', $front_id) ?: []) as $banner) {
    foreach (['img', 'mobilnyj_banner'] as $key) {
      $id = (int) ($banner[$key] ?? 0);
      if ($id > 0) {
        $ids[] = $id;
      }
    }
  }

  return array_values(array_unique($ids));
}

function bsi_webp_cli_run(array $input): void
{
  if (!function_exists('bsi_webp_convert_attachment')) {
    exit("inc/helpers/webp.php не подключён — проверь functions.php\n");
  }

  if (!function_exists('imagewebp')) {
    exit("В PHP нет imagewebp(): GD собран без поддержки WebP\n");
  }

  if ($input['only_banners']) {
    $ids = bsi_webp_banner_ids();
    echo 'Баннеры главной: ' . count($ids) . " вложений\n";
  } else {
    $ids = get_posts([
      'post_type' => 'attachment',
      'post_mime_type' => bsi_webp_convertible_mimes(),
      'post_status' => 'inherit',
      'posts_per_page' => $input['limit'] > 0 ? $input['limit'] : -1,
      'fields' => 'ids',
    ]);
    echo 'Картинок в медиатеке: ' . count($ids) . "\n";
  }

  if ($input['dry_run']) {
    echo "[dry-run] ничего не записано\n";

    return;
  }

  $files = 0;
  $attachments = 0;
  $before = 0;
  $after = 0;

  foreach ($ids as $id) {
    foreach (bsi_webp_attachment_files((int) $id) as $file) {
      $webp = bsi_webp_path($file);
      $existed = file_exists($webp);

      if (!bsi_webp_convert_file($file)) {
        continue;
      }

      if (!$existed) {
        $files++;
        $before += (int) @filesize($file);
        $after += (int) @filesize($webp);
      }
    }

    $attachments++;

    if ($attachments % 25 === 0) {
      echo "  обработано вложений: $attachments\n";
    }
  }

  printf(
    "Готово: %d вложений, новых WebP %d. Было %s → стало %s (%d%%)\n",
    $attachments,
    $files,
    size_format($before),
    size_format($after),
    $before > 0 ? (int) round(100 - $after / $before * 100) : 0
  );
}

$input = bsi_webp_cli_input();
require_once $input['wp_load'];
bsi_webp_cli_run($input);
