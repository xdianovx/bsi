<?php
/**
 * Блок «Похожие события» для single event (та же страна, до 10 CPT event). Карточка — card.php, как в каталоге.
 *
 * @var array $args {
 *   @type WP_Post[] $posts Список постов CPT event.
 *   @type string    $title Заголовок (по умолчанию «Похожие события»).
 * }
 */
$posts = isset($args['posts']) ? $args['posts'] : [];
$related_title = !empty($args['title']) ? (string) $args['title'] : 'Похожие события';
if (empty($posts)) {
  return;
}

$event_catalog_page = get_page_by_path('sobytiynye-tury');
$more_href = $event_catalog_page ? get_permalink($event_catalog_page->ID) : home_url('/sobytiynye-tury/');
$more_label = 'Все туры';
?>

<section class="single-event__related single-event__related-tours news-slider__section" aria-labelledby="single-event-related-tours-heading">
  <div class="container">
    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 id="single-event-related-tours-heading" class="h2 news-slider__title"><?= esc_html($related_title); ?></h2>
        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev single-event-related-prev" tabindex="0" role="button"
            aria-label="Предыдущие"></div>
          <div class="slider-arrow slider-arrow-next single-event-related-next" tabindex="0" role="button"
            aria-label="Следующие"></div>
        </div>
      </div>
      <div class="title-wrap__buttons">
        <a href="<?= esc_url($more_href); ?>" class="title-wrap__link link-arrow">
          <span><?= esc_html($more_label); ?></span>
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
          if (!($rel_post instanceof WP_Post) || $rel_post->post_type !== 'event') {
            continue;
          }
          ?>
          <div class="swiper-slide single-event-related-slide">
            <?php get_template_part('template-parts/event/card', null, ['post_id' => (int) $rel_post->ID]); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
