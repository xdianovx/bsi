<?php
/*
Template Name: Акции
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
        <h1 class="h1 page-award__title archive-page__title ">
          <?php the_title(); ?>
        </h1>

        <p class="page-award__excerpt archive-page__excerpt"><?= get_the_excerpt() ?></p>
      </div>
    </div>
  </section>

  <section class="promo-filter__section">
    <div class="container">
      <?php
      // Страны, у которых есть акции
      $promo_countries = bsi_get_promo_countries();
      $total_promos = !empty($promo_countries)
        ? array_sum(array_column($promo_countries, 'count'))
        : 0;
      ?>

      <div class="promo-filter">
        <!-- Кнопка "Все направления" -->
        <button class="promo-filter__btn --all active js-promo-filter-btn"
                data-country="">
          Все (<?= $total_promos ?>)
        </button>

        <?php if (!empty($promo_countries)): ?>
          <?php foreach ($promo_countries as $country): ?>

            <button class="promo-filter__btn  js-promo-filter-btn"
                    data-country="<?php echo esc_attr($country['id']); ?>">
              <?php if (!empty($country['flag'])): ?>
                <span class="promo-filter__flag-wrap">
                  <img src="<?php echo $country['flag']; ?>"
                       alt="<?php echo esc_attr($country['title']); ?>"
                       class="promo-filter__flag">
                </span>
              <?php endif; ?>

              <span class="promo-filter__title">
                <?php echo esc_html($country['title']); ?>
              </span>

              <?php if (!empty($country['count'])): ?>
                <span class="promo-filter__count">
                  <?php echo (int) $country['count']; ?>
                </span>
              <?php endif; ?>
            </button>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="archive-page__content-section promo-page__content-section">
    <div class="container">
      <div class="promo-page__list">

        <?php
        $promo_query = new WP_Query([
          'post_type' => 'promo',
          'post_status' => 'publish',
          'posts_per_page' => -1,
          'orderby' => 'date',
          'order' => 'DESC',
        ]);
        ?>

        <?php if ($promo_query->have_posts()): ?>
          <div class="promo-grid js-promo-list">
            <?php while ($promo_query->have_posts()):
              $promo_query->the_post(); ?>

              <?php get_template_part('template-parts/promo/card'); ?>

            <?php endwhile; ?>
          </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

      </div>
    </div>
  </section>




</main>

<?php
get_footer();