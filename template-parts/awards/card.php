<?php
$award_id = get_the_ID();
$year = get_field('award_year', $award_id);
$issuer = get_field('award_issuer', $award_id);
$link = get_field('award_link', $award_id);
$file = get_field('award_file', $award_id);
$excerpt = get_the_excerpt();
?>

<article id="post-<?php the_ID(); ?>"
         <?php post_class('award-card'); ?>>
  <div class="award-card__inner">
    <?php if (has_post_thumbnail()): ?>
      <div class="award-card__image">
        <?php the_post_thumbnail('medium_large'); ?>
      </div>
    <?php endif; ?>

    <div class="award-card__bottom">
      <!-- <?php if ($year): ?>
        <div class="award-card__year"><?= esc_html($year); ?></div>
      <?php endif; ?> -->

      <h3 class="award-card__title h2"><?php the_title(); ?></h3>

      <!-- <?php if ($issuer): ?>
        <div class="award-card__issuer">
          <?= esc_html($issuer); ?>
        </div>
      <?php endif; ?> -->

      <?php if ($excerpt): ?>
        <div class="award-card__excerpt">
          <?= esc_html($excerpt); ?>
        </div>
      <?php endif; ?>


    </div>
  </div>
</article>