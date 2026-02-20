<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bsi
 */

get_header();
?>

<main>





  <?= get_template_part('template-parts/pages/main/banners') ?>
  <?= get_template_part('template-parts/main-pages-grid') ?>
  <!-- <?= get_template_part('template-parts/best-offers/best-offers') ?> -->
  <?= get_template_part('template-parts/education/popular') ?>
  <?= get_template_part('template-parts/promo-banner/slider') ?>
  <?= get_template_part('template-parts/hotels/popular') ?>
  <?php 
  // Промо баннеры 2 после секции отелей
  // Управление: Секции -> Промо баннеры 2
  set_query_var('promo_banner_post_type', 'banner_second');
  get_template_part('template-parts/promo-banner/slider'); 
  ?>
  <?= get_template_part('template-parts/tour/popular') ?>
  <!-- <?= get_template_part('template-parts/sections/features') ?> -->
  <?= get_template_part('template-parts/news/news-slider') ?>
  <?= get_template_part('template-parts/partners/partners-slider') ?>
  <?= get_template_part('template-parts/sections/subscribe') ?>
</main>

<?php
get_footer();
