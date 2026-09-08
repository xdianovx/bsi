<?php
/**
 * Похожие экскурсии (single-excursion.php) — грид карточек той же страны.
 *
 * @var WP_Post[] $related_excursions
 */

$related_posts = get_query_var('related_excursions') ?: [];
if (!is_array($related_posts) || empty($related_posts)) {
  return;
}
?>

<section class="single-excursion-related">
  <div class="container">
    <div class="single-excursion-related-head">
      <h2 class="h2 single-excursion-related-title">Похожие экскурсии</h2>
    </div>

    <div class="single-excursion-related-list">
      <?php foreach ($related_posts as $related_post): ?>
        <?php get_template_part('template-parts/excursion/card-row', null, ['post_id' => (int) $related_post->ID]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
