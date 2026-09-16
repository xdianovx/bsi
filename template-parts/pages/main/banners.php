<?php

/**
 * Баннеры главной.
 *
 * Первый слайд — LCP-элемент страницы, поэтому он грузится сразу
 * (`eager` + `fetchpriority="high"`), остальные ленивые.
 *
 * Раньше десктопная и мобильная версии выводились двумя <img> и разводились
 * через `display: none` — но скрытая картинка всё равно скачивается, и телефон
 * тянул оба файла. Теперь это <picture>: браузер загружает ровно один источник,
 * подходящий по ширине экрана, и предпочитает WebP, если он сделан
 * (inc/helpers/webp.php). Плюс `srcset` — до этого отдавался оригинал 2560px
 * даже на телефон.
 *
 * @package bsi
 */

declare(strict_types=1);

$front_page_id = (int) get_option('page_on_front');
if ($front_page_id <= 0) {
  return;
}

$banners = function_exists('get_field') ? (get_field('banners', $front_page_id) ?: []) : [];
$banners = function_exists('bsi_filter_schedule_rows') ? bsi_filter_schedule_rows($banners) : $banners;

if (empty($banners)) {
  return;
}

/** Ширина баннера в макете: до планшета — вся ширина, дальше — колонка контейнера. */
const BSI_BANNER_SIZES_DESKTOP = '(max-width: 1280px) 100vw, 1280px';
const BSI_BANNER_SIZES_MOBILE = '100vw';

/**
 * Один источник <picture>: сначала WebP, следом исходный формат.
 *
 * @param int         $attachment_id вложение
 * @param string      $sizes         значение атрибута sizes
 * @param string|null $media         медиавыражение, null — без него
 */
function bsi_banner_sources(int $attachment_id, string $sizes, ?string $media = null): string
{
  if ($attachment_id <= 0) {
    return '';
  }

  $media_attr = $media !== null ? ' media="' . esc_attr($media) . '"' : '';
  $out = '';

  $webp = function_exists('bsi_webp_srcset') ? bsi_webp_srcset($attachment_id, 'full') : '';
  if ($webp !== '') {
    $out .= '<source type="image/webp" srcset="' . esc_attr($webp) . '" sizes="' . esc_attr($sizes) . '"' . $media_attr . ' />';
  }

  $srcset = wp_get_attachment_image_srcset($attachment_id, 'full');
  if ($srcset) {
    $type = (string) get_post_mime_type($attachment_id);
    $out .= '<source type="' . esc_attr($type) . '" srcset="' . esc_attr($srcset) . '" sizes="' . esc_attr($sizes) . '"' . $media_attr . ' />';
  }

  return $out;
}

/**
 * Разметка баннера: <picture> с мобильным и десктопным источником.
 */
function bsi_banner_picture(array $banner, string $alt, bool $is_first): string
{
  $desktop_id = (int) ($banner['img'] ?? 0);
  $mobile_id = (int) ($banner['mobilnyj_banner'] ?? 0);

  if ($desktop_id <= 0) {
    return '';
  }

  $html = '<picture class="main-banner__slide_picture">';

  /* Мобильный источник идёт первым: браузер берёт первый подошедший. */
  if ($mobile_id > 0) {
    $html .= bsi_banner_sources($mobile_id, BSI_BANNER_SIZES_MOBILE, '(max-width: 768px)');
  }

  $html .= bsi_banner_sources($desktop_id, BSI_BANNER_SIZES_DESKTOP);

  /* Первый слайд — LCP: грузим немедленно и мимо ленивой загрузки. */
  $attrs = [
    'class' => 'main-banner__slide_image' . ($is_first ? ' no-lazyload' : ''),
    'alt' => $alt,
    'sizes' => BSI_BANNER_SIZES_DESKTOP,
    'decoding' => 'async',
    'loading' => $is_first ? 'eager' : 'lazy',
  ];

  if ($is_first) {
    $attrs['fetchpriority'] = 'high';
  }

  $html .= wp_get_attachment_image($desktop_id, 'full', false, $attrs);
  $html .= '</picture>';

  return $html;
}
?>

<section class="main-banner__section">
  <div class="container">
    <div class="swiper main-banners-slider">
      <div class="swiper-wrapper">

        <?php foreach ($banners as $i => $banner): ?>
          <?php
          // ?: вместо ??: у баннера без заголовка ключ существует, но пуст,
          // и оператор ?? отдавал пустой alt.
          $banner_alt = esc_attr(trim((string) ($banner['title'] ?? '')) ?: 'Баннер BSI Group');
          $picture = bsi_banner_picture($banner, $banner_alt, $i === 0);

          if ($picture === '') {
            continue;
          }
          ?>
          <div class="swiper-slide">
            <?php if (!empty($banner['url'])): ?>
              <a href="<?= esc_url($banner['url']); ?>" target="_blank" rel="noopener noreferrer" class="main-banner__slide">
                <?= $picture; ?>
              </a>
            <?php else: ?>
              <div class="main-banner__slide">
                <?= $picture; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-pagination main-banner__pagination"></div>
      <div class="slider-arrow-wrap main-banner__arrows">
        <div class="slider-arrow slider-arrow-prev main-banner-arrow-prev">
        </div>
        <div class="slider-arrow slider-arrow-next main-banner-arrow-next">
        </div>
      </div>

    </div>

  </div>
</section>
