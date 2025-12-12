<?php get_header(); ?>

<main class="site-main">

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section>
    <div class="container">
      <div class="page-country__about">
        <div class="page-country__title">

          <h1 class="h1 h1-country">
            <?php the_title(); ?>
          </h1>

        </div>
      </div>
  </section>


</main>

<?php
get_footer();

?>