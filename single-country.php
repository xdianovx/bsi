<?php
$country_id = get_the_ID();
$news_query = new WP_Query([
  'post_type' => 'news',
  'post_status' => 'publish',
  'posts_per_page' => 4,
  'meta_query' => [
    [
      'key' => 'news_countries',
      'value' => '"' . $country_id . '"',
      'compare' => 'LIKE',
    ],
  ],
]);


$country_hotels_slug = get_query_var('country_hotels');
$country_promos_slug = get_query_var('country_promos');

if ($country_hotels_slug) {
  $country = get_page_by_path($country_hotels_slug, OBJECT, 'country');

  global $country_hotels_data;
  $country_hotels_data = [
    'country' => $country,
    'country_slug' => $country_hotels_slug,
  ];

  get_template_part('country-hotels'); // твой файл списка отелей
  exit;
}

if ($country_promos_slug) {
  $country = get_page_by_path($country_promos_slug, OBJECT, 'country');

  global $country_promos_data;
  $country_promos_data = [
    'country' => $country,
    'country_slug' => $country_promos_slug,
  ];

  get_template_part('country-promo');  // тот файл, который ты показал
  exit;
}



get_header(); ?>

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

      <div class="coutry-page__wrap">
        <aside class="coutry-page__aside">
          <?= get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">
          <div class="page-country__about">
            <div class="page-country__title">
              <?php

              if (!wp_get_post_parent_id(get_the_ID()) && get_field('flag', get_the_ID())): ?>

                <img src="<?= get_field('flag', get_the_ID()) ?>"
                     alt="флаг <?php the_title() ?>" />


              <?php endif; ?>

              <h1 class="h1 h1-country">
                Туры <?php the_title(); ?>
              </h1>
            </div>

            <?= get_template_part('template-parts/pages/country/country-info') ?>

            <p class="page-country__descr">
              <?= get_the_excerpt() ?>
            </p>

            <?=
              get_template_part('template-parts/pages/country/photo-gallery-slider');
            ?>
          </div>

          <div class="editor-content">
            <?php the_content() ?>
          </div>

          <section>
            <h2 class="h2">Новости</h2>
            <?php
            if ($news_query->have_posts()): ?>
              <div class="swiper news-slider-slider">
                <div class="swiper-wrapper">
                  <?php
                  while ($news_query->have_posts()):
                    $news_query->the_post();
                    ?>
                    <div class="swiper-slide">
                      <?php get_template_part('template-parts/news/card'); ?>
                    </div>
                  <?php endwhile; ?>
                </div>
              <?php endif; ?>
          </section>
        </div>



      </div>
    </div>
  </section>


</main>

<?php
get_footer();

?>