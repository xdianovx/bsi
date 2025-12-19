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
  'meta_query' => [
    [
      'key' => 'tour_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ],
  'orderby' => 'title',
  'order' => 'ASC',
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
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">

          <h1 class="h1 country-tours__title">
            <?= esc_html($country ? $country->post_title : ''); ?> — туры
          </h1>

          <div class="country-tours__counter">
            Найдено туров: <?= (int) $tours_query->found_posts; ?>
          </div>

          <?php if ($tours_query->have_posts()): ?>
            <div class="country-tours__list">
              <?php while ($tours_query->have_posts()):
                $tours_query->the_post(); ?>
                <?php
                get_template_part('template-parts/tour/card-row', null, [
                  'post_id' => get_the_ID(),
                ]);
                ?>
              <?php endwhile; ?>
            </div>

            <?php
            $pagination = paginate_links([
              'total' => (int) $tours_query->max_num_pages,
              'current' => $paged,
              'prev_text' => '&larr; Назад',
              'next_text' => 'Вперёд &rarr;',
              'mid_size' => 2,
              'type' => 'list',
            ]);
            ?>

            <?php if ($pagination): ?>
              <div class="country-tours__pagination">
                <?= $pagination; ?>
              </div>
            <?php endif; ?>

          <?php else: ?>
            <p>Пока нет туров для этой страны.</p>
          <?php endif; ?>

          <?php wp_reset_postdata(); ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>