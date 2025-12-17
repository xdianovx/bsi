<?php
global $country_promos_data;

$country = $country_promos_data['country'] ?? null;
$country_slug = $country_promos_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? $country->ID : 0;

$promos = get_posts([
  'post_type' => 'promo',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'meta_query' => [
    [
      'key' => 'promo_countries',
      'value' => '"' . $country_id . '"',
      'compare' => 'LIKE',
    ],
  ],
  'orderby' => 'date',
  'order' => 'DESC',
]);

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
          <?php if ($promos): ?>
            <h1 class="h1 country-promos__title">
              <?= esc_html($country->post_title); ?> — акции
            </h1>

            <div class="country-promos__counter">

              <span>Нашли акций: <span class=""><?= count($promos); ?></span>
              </span>
            </div>

            <div class="country-promos__list promo-grid">
              <?php
              global $post;

              foreach ($promos as $promo) {
                $post = $promo;               // важно
                setup_postdata($post);        // теперь get_the_ID() и т.п. точно про promo
                get_template_part('template-parts/promo/card');
              }

              wp_reset_postdata();
              ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>

</main>

<?php
get_footer();