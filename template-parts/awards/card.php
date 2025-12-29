<?php
$award_id = get_the_ID();

$year = get_field('award_year', $award_id);
$issuer = get_field('award_issuer', $award_id);
$link = get_field('award_link', $award_id);
$file = get_field('award_file', $award_id);
$excerpt = get_the_excerpt();

$file_url = '';
if (!empty($file)) {
  if (is_array($file) && !empty($file['url'])) {
    $file_url = (string) $file['url'];
  } elseif (is_string($file)) {
    $file_url = (string) $file;
  }
}

$fb_href = '';
$fb_type = '';

if ($file_url) {
  $fb_href = $file_url;
} elseif (!empty($link)) {
  $fb_href = (string) $link;
  $fb_type = 'iframe';
}
?>

<?php if ($fb_href): ?>
  <a href="<?php echo esc_url($fb_href); ?>" class="award-card__link" data-fancybox="awards" <?php if ($fb_type): ?>
      data-type="<?php echo esc_attr($fb_type); ?>" <?php endif; ?> aria-label="<?php echo esc_attr(get_the_title()); ?>">
  <?php endif; ?>

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
            <?php echo esc_html($excerpt); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </article>

  <?php if ($fb_href): ?>
  </a>
<?php endif; ?>