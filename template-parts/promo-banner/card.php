<?php

$img_desktop = get_field('banner_image_desktop');
$img_mobile = get_field('banner_image_mobile');
$badge = get_field('banner_badge');
$subtitle = get_field('banner_subtitle');

$link = get_field('banner_link');
$link_target = get_field('banner_target');

if (!$img_mobile && $img_desktop) {
  $img_mobile = $img_desktop;
}

?>


<div class="promo-banner-card">
  <a href="<?= esc_url($link); ?>"
     target="<?= esc_attr($link_target); ?>"
     class="promo-banner-card__link">

    <picture class="promo-banner-card__picture">
      <?php if (!empty($img_mobile['url'])): ?>
        <source srcset="<?= esc_url($img_mobile['url']); ?>"
                media="(max-width: 767px)">
      <?php endif; ?>

      <img src="<?= esc_url($img_desktop['url'] ?? $placeholder); ?>"
           alt="<?= esc_attr($img_desktop['alt'] ?? get_the_title()); ?>"
           class="promo-banner-card__img"
           loading="lazy">
    </picture>


  </a>
</div>