<?php

$collection = get_posts([
  'post_type' => 'offer_collection',
  'post_status' => 'publish',
  'posts_per_page' => 1,
  'orderby' => 'date',
  'order' => 'DESC',
]);

$collection_id = !empty($collection) ? $collection[0]->ID : 0;

$sections = $collection_id ? (get_field('offer_sections', $collection_id) ?: []) : [];

$slides_limit = 12;
$slides = [];

foreach ($sections as $section) {
  $items = $section['items'] ?? [];
  foreach ($items as $row) {
    if (count($slides) >= $slides_limit)
      break 2;

    $card = bsi_prepare_offer_item($row);
    if ($card) {
      $slides[] = $card;
    }
  }
}

?>

<section class="best-offers__section">
  <div class="container">
    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title">Лучшие предложения</h2>
        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev best-offers-arrow-prev" tabindex="-1" role="button"
            aria-label="Previous slide" aria-controls="swiper-wrapper-6afd786aee0e5cee" aria-disabled="true">
          </div>
          <div class="slider-arrow slider-arrow-next best-offers-arrow-next" tabindex="0" role="button"
            aria-label="Next slide" aria-controls="swiper-wrapper-6afd786aee0e5cee" aria-disabled="false">
          </div>
        </div>
      </div>

    </div>

    <?php if (!empty($slides)): ?>
      <div class="best-offers__content">
        <div class="swiper best-offers-slider">
          <div class="swiper-wrapper">
            <?php foreach ($slides as $card): ?>
              <div class="swiper-slide">
                <?php get_template_part('template-parts/best-offers/card', null, [
                  'best_offer' => $card,
                  'post_id' => $card['post_id'] ?? 0,
                ]); ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>