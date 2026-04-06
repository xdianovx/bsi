<?php
global $country_tours_data;

$country = $country_tours_data['country'] ?? null;
$country_slug = $country_tours_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;

$paged = max(1, (int) get_query_var('paged'));
$per_page = 12;

$tours_query = new WP_Query([
  'post_type' => 'tour',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'meta_query' => bsi_build_tour_country_meta_query((int) $country_id),
  'orderby' => 'title',
  'order' => 'ASC',
]);

/**
 * ID всех туров страны (используется для фильтрации регионов и типов)
 */
$country_tour_ids = get_posts([
  'post_type'      => 'tour',
  'post_status'    => 'publish',
  'posts_per_page' => -1,
  'fields'         => 'ids',
  'meta_query'     => bsi_build_tour_country_meta_query((int) $country_id),
]);

/**
 * Регионы: только те, в которых есть туры этой страны
 */
$region_terms = [];
if (!empty($country_tour_ids)) {
  $region_terms = wp_get_object_terms($country_tour_ids, 'region', [
    'orderby' => 'name',
    'order'   => 'ASC',
  ]);
}

/**
 * Типы туров: только те, что есть у туров этой страны (только родительские)
 */
$tour_type_terms = [];
if (!empty($country_tour_ids)) {
  $tour_type_terms = wp_get_object_terms($country_tour_ids, 'tour_type', [
    'orderby' => 'name',
    'order'   => 'ASC',
    'parent'  => 0,
  ]);
}

/**
 * Курорты страны: все регионы страны -> все курорты этих регионов
 */
$region_ids = [];
if (!is_wp_error($region_terms) && !empty($region_terms)) {
  foreach ($region_terms as $rt) {
    $region_ids[] = (int) $rt->term_id;
  }
}

$resort_terms = [];
if (!empty($region_ids)) {
  $resort_terms = get_terms([
    'taxonomy' => 'resort',
    'hide_empty' => false,
    'meta_query' => [
      [
        'key' => 'resort_region',
        'value' => $region_ids,
        'compare' => 'IN',
      ],
    ],
    'orderby' => 'name',
    'order' => 'ASC',
  ]);
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
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">

          <div class="country-tours"
               data-tours-filter
               data-country-id="<?= (int) $country_id; ?>">

            <div class="country-tours__head">
              <h1 class="h1 country-tours__title">
                <?= esc_html($country ? $country->post_title : ''); ?> — туры
              </h1>

              <div class="country-tours__counter"
                   data-tours-count>
                Найдено туров: <?= (int) $tours_query->found_posts; ?>
              </div>
            </div>

            <form class="country-tours__filters"
                  data-tours-form>
              <div class="country-tours__filters-row">

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Регион</div>
                  <select class="tours-filter__select"
                          name="region"
                          data-choice="single">
                    <option value="">Все регионы</option>
                    <?php if (!is_wp_error($region_terms) && !empty($region_terms)): ?>
                      <?php foreach ($region_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Курорты</div>
                  <select class="tours-filter__select"
                          name="resort"
                          data-choice="single">
                    <option value="">Все курорты</option>
                    <?php if (!is_wp_error($resort_terms) && !empty($resort_terms)): ?>
                      <?php foreach ($resort_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Типы туров</div>
                  <select class="tours-filter__select"
                          name="tour_type"
                          data-choice="single">
                    <option value="">Все типы</option>
                    <?php if (!is_wp_error($tour_type_terms) && !empty($tour_type_terms)): ?>
                      <?php foreach ($tour_type_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

              </div>
            </form>

            <div class="country-tours__list"
                 data-tours-list>
              <?php if ($tours_query->have_posts()): ?>
                <?php while ($tours_query->have_posts()):
                  $tours_query->the_post(); ?>
                  <?php get_template_part('template-parts/tour/card-row', null, ['post_id' => get_the_ID()]); ?>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="country-tours__empty">
                  Пока нет туров для этой страны.
                </div>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </div>

            <div class="country-tours__pagination news-pagination" data-tours-pagination>
              <?php if ($tours_query->max_num_pages > 1): ?>
                <?php
                echo paginate_links([
                  'total'   => $tours_query->max_num_pages,
                  'current' => $paged,
                  'prev_text' => '&larr; Назад',
                  'next_text' => 'Вперед &rarr;',
                  'mid_size' => 2,
                ]);
                ?>
              <?php endif; ?>
            </div>

          </div>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>