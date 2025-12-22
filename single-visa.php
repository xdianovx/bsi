<?php
get_header();
?>
<?php if (function_exists('yoast_breadcrumb')) {
  yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
} ?>
<section>
  <div class="container">
    <div class="coutry-page__wrap">

      <?php /* Aside меню страны */ ?>
      <aside class="coutry-page__aside">
        <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
      </aside>

      <?php ?>
      <div class="page-country__content">
        <?php /* Заголовок + краткое описание */ ?>

        <div class="page-country__title">
          <h1 class="h1"><?php the_title(); ?></h1>

        </div>

        <div class="visa-page__poster">
          <img src="<?= get_the_post_thumbnail_url() ?>"
               alt="">
        </div>

        <?php /* Контент из редактора */ ?>
        <div class="editor-content page-country__editor-content">
          <?php the_content(); ?>
        </div>


      </div>
    </div>
  </div>
</section>


<?php
get_footer();