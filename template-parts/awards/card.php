<?php
$award_id = get_the_ID();

$year = get_field('award_year', $award_id);
$issuer = get_field('award_issuer', $award_id);
$description = get_field('award_description', $award_id);
$excerpt = $description ?: get_the_excerpt();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('award-card'); ?>>
  <div class="award-card__inner">
    <?php if (has_post_thumbnail()): ?>
      <div class="award-card__image">
        <?php the_post_thumbnail('medium_large'); ?>
      </div>
    <?php endif; ?>

    <div class="award-card__bottom">
      <h3 class="award-card__title h2"><?php the_title(); ?></h3>

      <?php if ($excerpt): ?>
        <div class="award-card__excerpt">
          <?php echo wp_kses($excerpt, ['br' => []]); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</article>
