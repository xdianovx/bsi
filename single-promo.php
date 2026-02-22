<?php
/**
 * Single promo template.
 * List views (country/promo/) are handled by template_include → country-promo.php.
 */

get_header();

$promo_id = get_the_ID();
$raw_from = get_field('promo_date_from', $promo_id);
$raw_to = get_field('promo_date_to', $promo_id);
$formatted_from = function_exists('format_date_value') ? format_date_value($raw_from) : $raw_from;
$formatted_to = function_exists('format_date_value') ? format_date_value($raw_to) : $raw_to;
?>

<main class="site-main">

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div class="breadcrumbs container"><p>',
      '</p></div>'
    );
  }
  ?>

  <section class="single-promo__head">
    <div class="container">
      <h1 class="h1 single-promo__title"><?php the_title(); ?></h1>

      <?php if ($formatted_from || $formatted_to): ?>
        <div class="single-promo__dates">
          <?php if ($formatted_from && $formatted_to): ?>
            <span class="single-promo__date-range"><?= esc_html($formatted_from); ?> – <?= esc_html($formatted_to); ?></span>
          <?php elseif ($formatted_from): ?>
            <span class="single-promo__date-from"><?= esc_html($formatted_from); ?></span>
          <?php else: ?>
            <span class="single-promo__date-to"><?= esc_html($formatted_to); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (has_post_thumbnail()): ?>
        <div class="single-promo__poster">
          <?php the_post_thumbnail('large', ['class' => 'single-promo__poster-img']); ?>
        </div>
      <?php endif; ?>

      <?php if (get_the_excerpt()): ?>
        <p class="single-promo__excerpt"><?= get_the_excerpt(); ?></p>
      <?php endif; ?>
    </div>
  </section>

  <section class="post-content-section">
    <div class="container">
      <div class="editor-content">
        <?php the_content(); ?>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
