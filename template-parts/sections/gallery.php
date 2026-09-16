<?php

/**
 * Галерея с превью — общая для страны, отеля, достопримечательности, образования.
 *
 * Картинки выводятся через bsi_picture(): WordPress проставляет ширину и высоту,
 * поэтому место резервируется до загрузки и макет не дёргается. Раньше атрибутов
 * не было, и счётчик «Ещё N фото» давал почти весь сдвиг макета на десктопе
 * (CLS 0,283 при пороге 0,1 — см. wiki/docs/seo-audit-2026-09-14.md, C3).
 *
 * В превью уходит `medium`, а не оригинал: полоска шириной в сотню пикселей
 * не нуждается в файле на 2500.
 *
 * @package bsi
 */

declare(strict_types=1);

$gallery = $args['gallery'] ?? [];
if (empty($gallery) || !is_array($gallery)) {
  return;
}

$id = $args['id'] ?? uniqid('gallery_');
$title = $args['title'] ?? '';

/** Крупный слайд занимает колонку контента, превью — узкую полоску. */
$main_sizes = '(max-width: 1200px) 100vw, 800px';
$thumb_sizes = '160px';

/**
 * ID вложения из элемента ACF-галереи; 0 — если пришёл голый URL.
 */
$attachment_id = static function (array $item): int {
  return (int) ($item['ID'] ?? $item['id'] ?? 0);
};
?>

<div class="single-hotel__gallery-section country-page__gallery js-gallery" data-gallery-id="<?= esc_attr($id); ?>">

  <?php if (!empty($title)): ?>
    <h2 class="h2"><?= esc_html($title); ?></h2>
  <?php endif; ?>
  <div class="swiper  js-gallery-main">
    <div class="swiper-wrapper">
      <?php foreach ($gallery as $index => $item): ?>
        <?php
        $img_url = $item['url'] ?? '';
        $img_alt = $item['alt'] ?? '';
        if (!$img_url) {
          continue;
        }

        $item_id = $attachment_id($item);
        /* Первый слайд виден сразу, остальные ждут пролистывания. */
        $attrs = [
          'alt' => $img_alt,
          'decoding' => 'async',
          'loading' => $index === 0 ? 'eager' : 'lazy',
          'sizes' => $main_sizes,
        ];
        ?>
        <div class="swiper-slide">
          <a class="hotel-gallery-main-slide country-page__gallery-slide" href="<?= esc_url($img_url); ?>"
            data-fancybox="<?= esc_attr($id); ?>">
            <?php if ($item_id > 0 && function_exists('bsi_picture')): ?>
              <?= bsi_picture($item_id, 'large', $attrs, $main_sizes); ?>
            <?php else: ?>
              <img src="<?= esc_url($img_url); ?>" alt="<?= esc_attr($img_alt); ?>" loading="<?= $index === 0 ? 'eager' : 'lazy'; ?>" decoding="async">
            <?php endif; ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="slider-arrow  slider-arrow-prev hotel-gallery-main-arrow-prev js-gallery-prev"></div>
    <div class="slider-arrow  slider-arrow-next hotel-gallery-main-arrow-next js-gallery-next"></div>
  </div>


  <div class="swiper  js-gallery-thumbs">
    <div class="swiper-wrapper">
      <?php
      $total_count = count($gallery);
      $remaining_count = $total_count > 4 ? $total_count - 4 : 0;
      foreach ($gallery as $item):
        $img_url = $item['url'] ?? '';
        $img_alt = $item['alt'] ?? '';
        if (!$img_url) {
          continue;
        }

        $item_id = $attachment_id($item);
        $thumb_attrs = [
          'alt' => $img_alt,
          'decoding' => 'async',
          'loading' => 'lazy',
          'sizes' => $thumb_sizes,
        ];
        ?>
        <div class="swiper-slide">
          <div class="hotel-gallery-thumb-slide">
            <?php if ($item_id > 0 && function_exists('bsi_picture')): ?>
              <?= bsi_picture($item_id, 'medium', $thumb_attrs, $thumb_sizes); ?>
            <?php else: ?>
              <img src="<?= esc_url($img_url); ?>" alt="<?= esc_attr($img_alt); ?>" loading="lazy" decoding="async">
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($remaining_count > 0): ?>
      <div class="gallery-thumb-overlay js-gallery-overlay" data-gallery-id="<?= esc_attr($id); ?>"
        data-remaining-count="<?= esc_attr($remaining_count); ?>">
        <span class="gallery-thumb-overlay__text">Ещё <?= esc_html($remaining_count); ?> фото</span>
      </div>
    <?php endif; ?>
  </div>

</div>
