<?php

/**
 * Карточка промо-баннера.
 *
 * Десктопная и мобильная картинки разной формы — 1200×320 против 738×886,
 * то есть высота отличается в разы. Пока файл не загружен, браузеру неоткуда
 * узнать, сколько места занять, и секция подскакивает при появлении картинки.
 *
 * Поэтому пропорции обеих версий отдаются в CSS-переменных, посчитанных
 * из реальных размеров вложения, а `<img>` получает width и height.
 *
 * @package bsi
 */

declare(strict_types=1);

$img_desktop = get_field('banner_image_desktop');
$img_mobile = get_field('banner_image_mobile');

$link = get_field('banner_link');
$link_target = get_field('banner_target');

if (!$img_mobile && $img_desktop) {
  $img_mobile = $img_desktop;
}

if (empty($img_desktop['url'])) {
  return;
}

/**
 * Пропорция вложения в виде «ширина / высота» для CSS.
 */
$ratio = static function ($image): string {
  $w = (int) ($image['width'] ?? 0);
  $h = (int) ($image['height'] ?? 0);

  return ($w > 0 && $h > 0) ? $w . ' / ' . $h : '';
};

$ratio_desktop = $ratio($img_desktop);
$ratio_mobile = $ratio($img_mobile);

$style = [];
if ($ratio_desktop !== '') {
  $style[] = '--promo-banner-ratio: ' . $ratio_desktop;
}
if ($ratio_mobile !== '') {
  $style[] = '--promo-banner-ratio-mobile: ' . $ratio_mobile;
}

$alt = trim((string) ($img_desktop['alt'] ?? '')) ?: get_the_title();
?>

<div class="promo-banner-card">
  <a href="<?= esc_url($link); ?>"
     target="<?= esc_attr($link_target); ?>"
     class="promo-banner-card__link">

    <picture class="promo-banner-card__picture"<?= $style ? ' style="' . esc_attr(implode('; ', $style)) . '"' : ''; ?>>
      <?php if (!empty($img_mobile['url'])): ?>
        <source srcset="<?= esc_url($img_mobile['url']); ?>"
                media="(max-width: 767px)"
                <?php if (!empty($img_mobile['width'])): ?>width="<?= (int) $img_mobile['width']; ?>" height="<?= (int) $img_mobile['height']; ?>"<?php endif; ?>>
      <?php endif; ?>

      <img src="<?= esc_url($img_desktop['url']); ?>"
           alt="<?= esc_attr($alt); ?>"
           class="promo-banner-card__img"
           <?php if (!empty($img_desktop['width'])): ?>width="<?= (int) $img_desktop['width']; ?>" height="<?= (int) $img_desktop['height']; ?>"<?php endif; ?>
           loading="lazy"
           decoding="async">
    </picture>


  </a>
</div>
