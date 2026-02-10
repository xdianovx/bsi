<?php
/**
 * template-parts/reviews/slider.php
 */

// Проверяем, нужно ли фильтровать по MICE (передается через get_template_part)
$filter_mice = isset($args['filter_mice']) ? $args['filter_mice'] : false;

// Если на странице MICE, фильтруем по ACF полю
$meta_query = [];
if ($filter_mice) {
  $meta_query[] = [
    'key' => 'show_on_mice_page',
    'value' => '1',
    'compare' => '='
  ];
}

$reviews_args = [
  'post_type' => 'review',
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'orderby' => 'date',
  'order' => 'DESC',
];

if (!empty($meta_query)) {
  $reviews_args['meta_query'] = $meta_query;
}

$reviews = get_posts($reviews_args);

if (empty($reviews)) {
  return;
}

$archive_url = get_post_type_archive_link('review');
?>

<section class="reviews-slider-section">
  <div class="container">

    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title">Отзывы</h2>

        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev reviews-arrow-prev" tabindex="0" role="button"
            aria-label="Previous slide"></div>
          <div class="slider-arrow slider-arrow-next reviews-arrow-next" tabindex="0" role="button"
            aria-label="Next slide"></div>
        </div>
      </div>

      <?php if (!empty($archive_url)): ?>
        <div class="title-wrap__buttons">
          <a href="<?= esc_url($archive_url); ?>" class="title-wrap__link link-arrow">
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
      <?php endif; ?>
    </div>

    <div class="reviews-slider-section__content">
      <div class="swiper reviews-slider">
        <div class="swiper-wrapper">
          <?php foreach ($reviews as $post):
            setup_postdata($post); ?>
            <div class="swiper-slide">
              <?php
              // карточка review-card (у тебя уже есть разметка)
              get_template_part('template-parts/reviews/card');
              ?>
            </div>
          <?php endforeach; ?>
          <?php wp_reset_postdata(); ?>
        </div>
      </div>
    </div>

  </div>
</section>