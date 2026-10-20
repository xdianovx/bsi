<?php
/**
 * Карточка награды.
 * $args['variant'] = 'grid' — белая карточка страницы наград (page-awards.php): квадратная подложка,
 * просмотр по клику с подписью. Без варианта — прежний вид для слайдера (template-parts/awards/slider.php).
 */
$award_id = get_the_ID();
$is_grid = ($args['variant'] ?? '') === 'grid';

$year = get_field('award_year', $award_id);
$issuer = get_field('award_issuer', $award_id);
$file = get_field('award_file', $award_id);
$description = get_field('award_description', $award_id);
$excerpt = $description ?: get_the_excerpt();

$file_url = '';
if (!empty($file)) {
  if (is_array($file) && !empty($file['url'])) {
    $file_url = (string) $file['url'];
  } elseif (is_string($file)) {
    $file_url = (string) $file;
  }
}

// На странице наград без файла открываем обложку в полном размере — листать можно все награды.
if ($is_grid && !$file_url && has_post_thumbnail()) {
  $file_url = (string) get_the_post_thumbnail_url($award_id, 'full');
}

// Без миниатюры — заглушка img/awards/placeholder.(webp|jpg|png|svg), если файл лежит в теме.
$placeholder_url = '';
if (!has_post_thumbnail()) {
  foreach (['webp', 'jpg', 'png', 'svg'] as $ext) {
    if (file_exists(get_template_directory() . '/img/awards/placeholder.' . $ext)) {
      $placeholder_url = get_template_directory_uri() . '/img/awards/placeholder.' . $ext;
      break;
    }
  }
}

// Подпись в просмотре: «название / кем вручена · год».
$caption = '';
if ($is_grid) {
  $caption_meta = implode(' · ', array_filter([trim((string) $issuer), trim((string) $year)]));
  $caption = '<div class="award-caption__title">' . esc_html(get_the_title()) . '</div>'
    . ($caption_meta !== '' ? '<div class="award-caption__meta">' . esc_html($caption_meta) . '</div>' : '');
}
?>

<?php if ($is_grid): ?>
  <?php if ($file_url): ?>
    <a href="<?php echo esc_url($file_url); ?>" class="award-card award-card--grid" data-fancybox="awards"
      data-caption="<?php echo esc_attr($caption); ?>">
  <?php else: ?>
    <div class="award-card award-card--grid">
  <?php endif; ?>

    <div class="award-card__media">
      <?php if (has_post_thumbnail()): ?>
        <?php the_post_thumbnail('medium_large', ['class' => 'award-card__img', 'loading' => 'lazy']); ?>
      <?php elseif ($placeholder_url): ?>
        <img class="award-card__img" src="<?php echo esc_url($placeholder_url); ?>" alt="" loading="lazy">
      <?php endif; ?>
    </div>

    <div class="award-card__text">
      <h3 class="award-card__name"><?php the_title(); ?></h3>

      <?php if ($issuer): ?>
        <div class="award-card__from"><?php echo esc_html($issuer); ?></div>
      <?php endif; ?>
    </div>

  <?php echo $file_url ? '</a>' : '</div>'; ?>
  <?php return; ?>
<?php endif; ?>

<?php if ($file_url): ?>
  <a href="<?php echo esc_url($file_url); ?>" class="award-card__link" data-fancybox="awards"
    aria-label="<?php echo esc_attr(get_the_title()); ?>">
  <?php endif; ?>

  <article id="post-<?php the_ID(); ?>" <?php post_class('award-card'); ?>>
    <div class="award-card__inner">
      <?php if (has_post_thumbnail()): ?>
        <div class="award-card__image">
          <?php the_post_thumbnail('medium_large'); ?>
        </div>
      <?php elseif ($placeholder_url): ?>
        <div class="award-card__image">
          <img src="<?php echo esc_url($placeholder_url); ?>" alt="" loading="lazy">
        </div>
      <?php endif; ?>

      <div class="award-card__bottom">
        <h3 class="award-card__title h2"><?php the_title(); ?></h3>

        <?php if ($issuer): ?>
          <div class="award-card__issuer"><?php echo esc_html($issuer); ?></div>
        <?php endif; ?>

        <?php if ($excerpt): ?>
          <div class="award-card__excerpt">
            <?php echo wp_kses($excerpt, ['br' => []]); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </article>

  <?php if ($file_url): ?>
  </a>
<?php endif; ?>
