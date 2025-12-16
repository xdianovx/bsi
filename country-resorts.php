<?php
global $country_resorts_data;

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

          <h1 class="h1 country-promos__title">
            <?= esc_html($country->post_title); ?> — курорты
          </h1>

          <?php if (!empty($regions)): ?>
            <div class="country-regions__list">
              <?php foreach ($regions as $region): ?>

                <?php
                $resorts = get_terms([
                  'taxonomy' => 'resort',
                  'hide_empty' => false,
                  'orderby' => 'name',
                  'order' => 'ASC',
                  'meta_query' => [
                    [
                      'key' => 'resort_region',
                      'value' => $region->term_id,
                      'compare' => '=',
                    ],
                  ],
                ]);

                if (empty($resorts) || is_wp_error($resorts)) {
                  $resorts = [];
                }
                ?>

                <div class="country-regions__item">
                  <a class="country-regions__link"
                     href="<?= esc_url(get_term_link($region)); ?>">
                    <?= esc_html($region->name); ?>
                  </a>

                  <?php if (!empty($resorts)): ?>
                    <div class="country-regions__resorts">
                      <?php foreach ($resorts as $resort): ?>
                        <a class="country-regions__resort"
                           href="<?= esc_url(get_term_link($resort)); ?>">
                          <?= esc_html($resort->name); ?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>

              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p>Пока нет регионов и курортов для этой страны.</p>
          <?php endif; ?>

        </div>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>