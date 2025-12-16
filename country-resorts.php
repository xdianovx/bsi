<?php
global $country_resorts_data;

/* Данные страны из router/template_redirect */
$country = $country_resorts_data['country'] ?? null;
$country_slug = $country_resorts_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  get_header();
  get_template_part('404');
  get_footer();
  exit;
}

$country_id = $country->ID;

/* Регионы страны */
$regions = get_terms([
  'taxonomy' => 'region',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
  'meta_query' => [
    [
      'key' => 'region_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ],
]);

if (empty($regions) || is_wp_error($regions)) {
  $regions = [];
}

get_header(); ?>

<main class="site-main">
  <?php
  /* Хлебные крошки */
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

        <!-- Aside -->
        <aside class="coutry-page__aside">
          <?= get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <!-- Content -->
        <div class="page-country__content">

          <!-- Заголовок -->
          <h1 class="h1 country-promos__title">
            <?= esc_html($country->post_title); ?> — курорты
          </h1>

          <!-- Список регионов/курортов -->
          <?php get_template_part('template-parts/pages/country/region-resort', null, ['regions' => $regions]); ?>

        </div>

      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>