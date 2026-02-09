<?php
/**
 * Awards slider section
 * Template: template-parts/awards/slider.php
 */

$awards = new WP_Query([
  'post_type' => 'award',
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'orderby' => 'date',
  'order' => 'DESC',
]);

if (!$awards->have_posts()) {
  wp_reset_postdata();
  return;
}
?>

<section class="awards__section best-offers__section">
  <div class="container">
    <div class="awards__title-wrap title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="awards__title h2 news-slider__title">Награды</h2>

        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev awards-arrow-prev" tabindex="0" role="button"
            aria-label="Previous slide">
          </div>

          <div class="slider-arrow slider-arrow-next awards-arrow-next" tabindex="0" role="button"
            aria-label="Next slide">
          </div>
        </div>
      </div>

      <div class="title-wrap__buttons awards__buttons">
        <a href="<?php echo esc_url(home_url('/nagrady/')); ?>" class="title-wrap__link link-arrow">
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

    <div class="awards__content best-offers__content">
      <div class="swiper awards-slider">
        <div class="swiper-wrapper">
          <?php while ($awards->have_posts()):
            $awards->the_post(); ?>
            <div class="swiper-slide awards__slide">
              <?php get_template_part('template-parts/awards/card'); ?>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

  </div>
</section>

<?php wp_reset_postdata(); ?>