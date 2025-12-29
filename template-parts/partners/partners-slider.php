<?php
$partners_page_link = get_post_type_archive_link('partner');
$partners_query = new WP_Query([
  'post_type' => 'partner',
  'posts_per_page' => -1,
  'orderby' => 'date',
  'order' => 'DESC'
]);
?>

<section class="partners-slider__section">
  <div class="container">

    <div class="title-wrap">
      <div class="title-wrap__left">
        <h2 class="h2 partners-slider__title">Партнеры</h2>
      </div>


    </div>


    <?php if ($partners_query->have_posts()): ?>

      <div class="swiper partners-slider-slider">
        <div class="swiper-wrapper">
          <?php
          while ($partners_query->have_posts()):
            $partners_query->the_post();
            ?>
            <div class="swiper-slide">
              <div class="partner-card">

                <img src="<?= get_the_post_thumbnail_url() ?>" alt="<?php the_title() ?>">
              </div>
            </div>

          <?php endwhile; ?>

        </div>
      </div>


      <?php wp_reset_postdata(); ?>
    <?php endif; ?>


  </div>
</section>