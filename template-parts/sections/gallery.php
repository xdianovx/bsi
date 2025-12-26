<?php
$gallery = $args['gallery'] ?? [];
if (empty($gallery) || !is_array($gallery)) {
  return;
}

$id = $args['id'] ?? uniqid('gallery_'); // уникальная группа для fancybox и js
$title = $args['title'] ?? ''; // если нужно
?>

<div class="single-hotel__gallery-section country-page__gallery js-gallery" data-gallery-id="<?= esc_attr($id); ?>">

  <?php if (!empty($title)): ?>
    <h2 class="h2"><?= esc_html($title); ?></h2>
  <?php endif; ?>
  <div class="swiper  js-gallery-main">
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
          <a class="hotel-gallery-main-slide country-page__gallery-slide" href="<?= esc_url($img_url); ?>"
            data-fancybox="<?= esc_attr($id); ?>">
            <img src="<?= esc_url($img_url); ?>" alt="<?= esc_attr($img_alt); ?>">
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
      foreach ($gallery as $index => $item):
        $img_url = $item['url'] ?? '';
        $img_alt = $item['alt'] ?? '';
        if (!$img_url)
          continue;
        ?>
        <div class="swiper-slide">
          <div class="hotel-gallery-thumb-slide">
            <img src="<?= esc_url($img_url); ?>" alt="<?= esc_attr($img_alt); ?>">
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