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

    $post_obj = $row['post'] ?? null;
    if (!$post_obj instanceof WP_Post)
      continue;

    $badges = $row['badges'] ?? [];
    $tags = [];
    if (is_array($badges)) {
      foreach ($badges as $t) {
        if (!empty($t->name))
          $tags[] = $t->name;
      }
    }

    $title = ($row['title_override'] ?? '') ?: get_the_title($post_obj->ID);
    $url = ($row['link_override'] ?? '') ?: get_permalink($post_obj->ID);

    $image = '';
    if (!empty($row['image_override']['url'])) {
      $image = $row['image_override']['url'];
    } else {
      $thumb = get_the_post_thumbnail_url($post_obj->ID, 'large');
      if ($thumb)
        $image = $thumb;
    }

    $type_obj = get_post_type_object($post_obj->post_type);
    $type = $type_obj && !empty($type_obj->labels->singular_name) ? $type_obj->labels->singular_name : '';

    $location_title = ($row['location_override'] ?? '') ?: '';
    $price = $row['price'] ?? '';

    $slides[] = [
      'url' => $url,
      'image' => $image,
      'type' => $type,
      'tags' => $tags,
      'title' => $title,
      'flag' => '',
      'location_title' => $location_title,
      'price' => $price,
    ];
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

      <div class="title-wrap__buttons">
        <a href="<?= esc_url(home_url('/luchshie-predlozheniya/')); ?>" class="title-wrap__link link-arrow">
          <span>Смотреть все</span>
          <div class="link-arrow__icon">

            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
              class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
              <path d="M7 7h10v10"></path>
              <path d="M7 17 17 7"></path>
            </svg>

          </div>
        </a>
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
                  'post_id' => $post_obj->ID,
                ]); ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>