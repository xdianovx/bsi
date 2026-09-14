<?php

/**
 * Нативная ленивая загрузка картинок.
 *
 * В теме десятки шаблонов выводят <img> руками, без `loading`. Ленивую
 * загрузку обеспечивает только JS-библиотека lazysizes: она переносит адрес
 * в `data-src` и ждёт выполнения скрипта. Из-за этого картинка, готовая
 * к показу, ждёт JS — на странице страны так набегала задержка 4,2 секунды
 * до начала загрузки (wiki/docs/seo-audit-2026-09-14.md, M3).
 *
 * Здесь `loading="lazy"` и `decoding="async"` проставляются всем картинкам
 * разом, на выходе. Нативный атрибут работает и без JS, а lazysizes
 * остаётся вторым слоем — они не конфликтуют.
 *
 * Не трогаем:
 *  - картинки, где `loading` уже задан явно (первый баннер — `eager`);
 *  - всё с классом `no-lazyload` — им помечены LCP-кандидаты;
 *  - админку, фиды, REST и письма.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Стоит ли обрабатывать текущий запрос.
 */
function bsi_lazy_images_enabled(): bool
{
  if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) {
    return false;
  }

  if (defined('REST_REQUEST') && REST_REQUEST) {
    return false;
  }

  if (defined('DOING_CRON') && DOING_CRON) {
    return false;
  }

  return true;
}

/**
 * Проставить `loading` и `decoding` тем <img>, у которых их нет.
 */
function bsi_lazy_images_filter(string $html): string
{
  if (stripos($html, '<img') === false) {
    return $html;
  }

  return (string) preg_replace_callback(
    '#<img\b[^>]*>#i',
    static function (array $match): string {
      $tag = $match[0];

      /* Явно помеченные — не наша забота: либо уже настроены, либо LCP. */
      if (stripos($tag, 'no-lazyload') !== false) {
        return $tag;
      }

      if (!preg_match('/\sloading\s*=/i', $tag)) {
        $tag = preg_replace('/<img\b/i', '<img loading="lazy"', $tag, 1);
      }

      if (!preg_match('/\sdecoding\s*=/i', $tag)) {
        $tag = preg_replace('/<img\b/i', '<img decoding="async"', $tag, 1);
      }

      return $tag;
    },
    $html
  );
}

/**
 * Буферизуем страницу целиком: <img> разбросаны по десяткам шаблонов,
 * и один проход на выходе дешевле правки каждого места.
 */
add_action('template_redirect', static function (): void {
  if (!bsi_lazy_images_enabled()) {
    return;
  }

  ob_start('bsi_lazy_images_filter');
}, 1);
