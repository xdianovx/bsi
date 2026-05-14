<?php
/**
 * Related events slider (Swiper).
 *
 * @var array $args {
 *   @type WP_Post[] $posts
 *   @type string    $title
 * }
 */
$posts = isset($args['posts']) ? $args['posts'] : [];
if (empty($posts)) {
  return;
}
$title = isset($args['title']) ? (string) $args['title'] : 'Похожие событийные туры';

$event_catalog = get_page_by_path('sobytiynye-tury');
$catalog_url = $event_catalog ? get_permalink($event_catalog->ID) : home_url('/sobytiynye-tury/');
?>

<section class="single-event__related news-slider__section">
  <div class="container">
    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title"><?= esc_html($title); ?></h2>
        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev single-event-related-prev" tabindex="0" role="button"
            aria-label="Предыдущие события"></div>
          <div class="slider-arrow slider-arrow-next single-event-related-next" tabindex="0" role="button"
            aria-label="Следующие события"></div>
        </div>
      </div>
      <div class="title-wrap__buttons">
        <a href="<?= esc_url($catalog_url); ?>" class="title-wrap__link link-arrow">
          <span>Все событийные туры</span>
          <div class="link-arrow__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M7 7h10v10" />
              <path d="M7 17 17 7" />
            </svg>
          </div>
        </a>
      </div>
    </div>

    <div class="swiper single-event-related-slider">
      <div class="swiper-wrapper">
        <?php foreach ($posts as $rel_post): ?>
          <?php
          if (!($rel_post instanceof WP_Post)) {
            continue;
          }
          ?>
          <div class="swiper-slide single-event-related-slide">
            <?php get_template_part('template-parts/event/card-row', null, ['post_id' => (int) $rel_post->ID]); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
