<?php
/**
 * Слайдер похожих экскурсий той же страны (single-excursion.php).
 *
 * @var WP_Post[] $related_excursions
 */

$posts = get_query_var('related_excursions') ?: [];
if (!is_array($posts) || empty($posts)) {
  return;
}
?>

<section class="single-excursion__related">
  <div class="container">
    <div class="title-wrap">
      <h2 class="h2">Похожие экскурсии</h2>
    </div>

    <div class="swiper related-excursions-slider">
      <div class="swiper-wrapper">
        <?php foreach ($posts as $rel): ?>
          <div class="swiper-slide">
            <?php get_template_part('template-parts/excursion/card-row', null, ['post_id' => (int) $rel->ID]); ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-pagination related-excursions-slider-pag"></div>
    </div>
  </div>
</section>
