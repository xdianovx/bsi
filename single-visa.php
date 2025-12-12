<?php
get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <?php if (have_posts()): ?>
    <?php while (have_posts()):
      the_post();

      $country_id = get_field('visa_country');
      $country = $country_id ? get_post($country_id) : null;
      $country_title = $country ? $country->post_title : get_the_title();
      ?>

      <section class="visa-page-head">
        <div class="container">
          <h1 class="h1 visa-page__title">
            <?php the_title() ?>
          </h1>

          <div class="visa-page__poster">
            <img src="<?= get_the_post_thumbnail_url() ?>"
                 alt="">
          </div>
        </div>
      </section>

      <section class="visa-page__content">
        <div class="container">
          <div class="visa-page__layout">
            <div class="visa-page__main editor-content">
              <?php the_content(); ?>
            </div>

          </div>
        </div>
      </section>

    <?php endwhile; ?>
  <?php endif; ?>

</main>

<?php
get_footer();