<?php

/**
 * WebP рядом с исходной картинкой.
 *
 * На сайте нет плагина, который отдавал бы WebP, а баннеры главной лежат
 * в PNG по 200-470 КБ — это основная часть LCP мобильной главной
 * (8,1 с при пороге 2,5 — см. wiki/docs/seo-audit-2026-09-14.md, C3).
 *
 * Здесь для каждого размера, который нарезает WordPress, рядом кладётся
 * файл `.webp`. Шаблон отдаёт его через <source type="image/webp">,
 * браузер без поддержки формата берёт исходный PNG или JPEG.
 *
 * Конвертация идёт через GD: Imagick на сервере нет, а GD собран с WebP.
 * Файлы, уже загруженные раньше, обрабатывает tools/convert-webp.php.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Форматы, которые есть смысл конвертировать.
 *
 * SVG — векторный, GIF часто анимированный (GD сохранит только первый кадр).
 */
function bsi_webp_convertible_mimes(): array
{
  return ['image/jpeg', 'image/png'];
}

/**
 * Путь к WebP-двойнику файла: `banner.png` → `banner.png.webp`.
 *
 * Расширение добавляется, а не заменяется: иначе `photo.png` и `photo.jpg`
 * в одной папке дали бы один и тот же `photo.webp`.
 */
function bsi_webp_path(string $file): string
{
  return $file . '.webp';
}

/**
 * Сконвертировать один файл. Существующий WebP не перезаписывается.
 *
 * @return bool удалось ли получить WebP (в том числе если он уже был)
 */
function bsi_webp_convert_file(string $file, int $quality = 82): bool
{
  if (!is_readable($file)) {
    return false;
  }

  $target = bsi_webp_path($file);
  if (file_exists($target)) {
    return true;
  }

  if (!function_exists('imagewebp')) {
    return false;
  }

  $type = wp_check_filetype($file)['type'] ?? '';
  if (!in_array($type, bsi_webp_convertible_mimes(), true)) {
    return false;
  }

  $image = $type === 'image/png' ? @imagecreatefrompng($file) : @imagecreatefromjpeg($file);
  if (!$image) {
    return false;
  }

  /* PNG с прозрачностью: без этого фон уедет в чёрный. */
  if ($type === 'image/png') {
    imagepalettetotruecolor($image);
    imagealphablending($image, false);
    imagesavealpha($image, true);
  }

  $done = imagewebp($image, $target, $quality);
  imagedestroy($image);

  /* GD иногда пишет пустой файл вместо ошибки. */
  if ($done && filesize($target) < 100) {
    @unlink($target);

    return false;
  }

  return (bool) $done;
}

/**
 * Все файлы вложения: оригинал и каждый нарезанный размер.
 *
 * @return string[] абсолютные пути
 */
function bsi_webp_attachment_files(int $attachment_id): array
{
  $original = get_attached_file($attachment_id);
  if (!$original || !is_readable($original)) {
    return [];
  }

  $files = [$original];
  $meta = wp_get_attachment_metadata($attachment_id);
  $dir = dirname($original);

  foreach (($meta['sizes'] ?? []) as $size) {
    if (!empty($size['file'])) {
      $files[] = $dir . '/' . $size['file'];
    }
  }

  return $files;
}

/**
 * Сконвертировать вложение целиком.
 *
 * @return int сколько файлов получили WebP
 */
function bsi_webp_convert_attachment(int $attachment_id): int
{
  $done = 0;

  foreach (bsi_webp_attachment_files($attachment_id) as $file) {
    if (bsi_webp_convert_file($file)) {
      $done++;
    }
  }

  return $done;
}

/**
 * Новая загрузка — сразу с WebP-двойниками.
 */
add_filter('wp_generate_attachment_metadata', static function ($metadata, $attachment_id) {
  if (is_array($metadata)) {
    bsi_webp_convert_attachment((int) $attachment_id);
  }

  return $metadata;
}, 20, 2);

/**
 * Удаление вложения — убрать и двойников, иначе они копятся мусором.
 */
add_action('delete_attachment', static function ($attachment_id): void {
  foreach (bsi_webp_attachment_files((int) $attachment_id) as $file) {
    $webp = bsi_webp_path($file);
    if (file_exists($webp)) {
      @unlink($webp);
    }
  }
});

/**
 * URL WebP-версии — или пустая строка, если файла нет.
 */
function bsi_webp_url(int $attachment_id, string $size = 'full'): string
{
  $src = wp_get_attachment_image_src($attachment_id, $size);
  if (!$src || empty($src[0])) {
    return '';
  }

  $url = (string) $src[0];
  $uploads = wp_get_upload_dir();

  if (strpos($url, $uploads['baseurl']) !== 0) {
    return '';
  }

  $path = $uploads['basedir'] . substr($url, strlen($uploads['baseurl']));

  return file_exists(bsi_webp_path($path)) ? bsi_webp_path($url) : '';
}

/**
 * `srcset` из WebP-двойников — тот же набор ширин, что у исходной картинки.
 *
 * Пропускаем размеры, для которых WebP не сделан: иначе браузер выберет
 * ширину, по которой лежит 404.
 */
function bsi_webp_srcset(int $attachment_id, string $size = 'full'): string
{
  $srcset = wp_get_attachment_image_srcset($attachment_id, $size);
  if (!$srcset) {
    return '';
  }

  $uploads = wp_get_upload_dir();
  $out = [];

  foreach (explode(', ', $srcset) as $candidate) {
    $parts = explode(' ', trim($candidate));
    $url = $parts[0] ?? '';
    if ($url === '' || strpos($url, $uploads['baseurl']) !== 0) {
      continue;
    }

    $path = $uploads['basedir'] . substr($url, strlen($uploads['baseurl']));
    if (!file_exists(bsi_webp_path($path))) {
      continue;
    }

    $parts[0] = bsi_webp_path($url);
    $out[] = implode(' ', $parts);
  }

  return implode(', ', $out);
}
