<?php
$gallery = $args['gallery'] ?? [];
if (empty($gallery) || !is_array($gallery)) {
  return;
}

$id = $args['id'] ?? uniqid('gallery_'); // уникальная группа для fancybox и js
$title = $args['title'] ?? ''; // если нужно
?>

<div class="single-hotel__gallery-section country-page__gallery js-gallery"
     data-gallery-id="<?= esc_attr($id); ?>">

  <?php if (!empty($title)): ?>
    <h2 class="h2"><?= esc_html($title); ?></h2>
  <?php endif; ?>

  <div class="hotel-gallery__wrap">
    <div class="swiper hotel-gallery-main-slider js-gallery-main">
      <div class="swiper-wrapper">
        <?php foreach ($gallery as $item): ?>
          <?php
          $img_url = $item['url'] ?? '';
          $img_alt = $item['alt'] ?? '';
          if (!$img_url)
            continue;

          // если ACF отдает sizes, можно открыть крупнее:
          $full_url = $item['sizes']['large'] ?? $img_url; // поменяй на 'full' если есть
          ?>
          <div class="swiper-slide">
            <a class="hotel-gallery-main-slide country-page__gallery-slide"
               href="<?= esc_url($full_url); ?>"
               data-fancybox="<?= esc_attr($id); ?>">
              <img src="<?= esc_url($img_url); ?>"
                   alt="<?= esc_attr($img_alt); ?>">
            </a>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="slider-arrow xl slider-arrow-prev hotel-gallery-main-arrow-prev js-gallery-prev"></div>
      <div class="slider-arrow xl slider-arrow-next hotel-gallery-main-arrow-next js-gallery-next"></div>
    </div>
  </div>

  <div class="swiper hotel-gallery-main-slider-thumb js-gallery-thumbs">
    <div class="swiper-wrapper">
      <?php foreach ($gallery as $item): ?>
        <?php
        $img_url = $item['url'] ?? '';
        $img_alt = $item['alt'] ?? '';
        if (!$img_url)
          continue;
        ?>
        <div class="swiper-slide">
          <div class="hotel-gallery-thumb-slide">
            <img src="<?= esc_url($img_url); ?>"
                 alt="<?= esc_attr($img_alt); ?>">
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>