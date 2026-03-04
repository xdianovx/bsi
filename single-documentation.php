<?php
/**
 * Single template for the "Агентствам" section CPT.
 */

get_header();
?>

<main class="site-main agency-page">
  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section class="agency-page__section">
    <div class="container">
      <div class="agency-page__layout">
        <aside class="agency-page__aside">
          <?php get_template_part('template-parts/pages/agency/sidebar'); ?>
        </aside>

        <div class="agency-page__content">
          <?php while (have_posts()): ?>
            <?php the_post(); ?>
            <h1 class="h1 agency-page__title"><?php the_title(); ?></h1>

            <div class="editor-content agency-page__editor">
              <?php the_content(); ?>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </section>

  <?php get_template_part('template-parts/sections/subscribe'); ?>
</main>

<?php get_footer();
