<?php
/*
Template Name: Архив акций
*/

get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="page-head archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 page-award__title archive-page__title">
          <?php the_title(); ?>
        </h1>

        <p class="page-award__excerpt archive-page__excerpt"><?= get_the_excerpt() ?></p>

        <?php if (trim((string) get_the_content()) !== ''): ?>
          <div class="editor-content archive-page__content">
            <?php the_content(); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="archive-page__content-section promo-page__content-section">
    <div class="container">
      <div class="promo-page__list">

        <?php
        $archived_query = new WP_Query([
          'post_type' => 'promo',
          'post_status' => 'publish',
          'posts_per_page' => -1,
          'orderby' => 'date',
          'order' => 'DESC',
          'meta_query' => [
            ['key' => 'promo_date_to', 'value' => date('Ymd'), 'compare' => '<'],
          ],
        ]);
        ?>

        <?php if ($archived_query->have_posts()): ?>
          <div class="promo-grid">
            <?php while ($archived_query->have_posts()):
              $archived_query->the_post(); ?>

              <?php get_template_part('template-parts/promo/card'); ?>

            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="promo-archive__empty">
            <p>Архив акций пуст.</p>
          </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

      </div>
    </div>
  </section>

</main>

<?php
get_footer();
