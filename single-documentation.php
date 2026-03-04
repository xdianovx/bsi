<?php
/**
 * Single template for the "Агентствам" section CPT.
 */

get_header();

$is_education_page = (get_post_field('post_name', get_queried_object_id()) === 'obuchenie');
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
          <?php if ($is_education_page): ?>
            <h1 class="h1 agency-page__title">Обучение</h1>
            <?php get_template_part('template-parts/agency/education-events'); ?>
          <?php else: ?>
            <?php while (have_posts()): ?>
              <?php the_post(); ?>
              <h1 class="h1 agency-page__title"><?php the_title(); ?></h1>
              <div class="editor-content agency-page__editor">
                <?php the_content(); ?>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php get_template_part('template-parts/sections/subscribe'); ?>
</main>

<?php if ($is_education_page): ?>
  <?php get_template_part('template-parts/agency/event-registration-modal'); ?>
<?php endif; ?>
<?php get_footer();
