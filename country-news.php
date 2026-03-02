<?php
global $country_news_data;

$country = $country_news_data['country'] ?? null;
$country_slug = $country_news_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;

$paged = max(1, (int) get_query_var('paged'));
$per_page = 12;

$news_query = new WP_Query([
  'post_type' => 'news',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'meta_query' => [
    [
      'key' => 'news_countries',
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
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">

          <div class="country-news">

            <div class="country-news__head">
              <h1 class="h1 country-news__title">
                <?= esc_html($country ? $country->post_title : ''); ?> — новости
              </h1>

              <div class="country-news__counter">
                Найдено новостей: <?= (int) $news_query->found_posts; ?>
              </div>
            </div>

            <div class="country-news__list">
              <?php if ($news_query->have_posts()): ?>
                <div class="news-grid">
                  <?php while ($news_query->have_posts()):
                    $news_query->the_post(); ?>
                    <?php get_template_part('template-parts/news/card'); ?>
                  <?php endwhile; ?>
                </div>
              <?php else: ?>
                <div class="no-news">
                  <p>Пока нет новостей для этой страны.</p>
                </div>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </div>

            <div class="country-news__pagination news-pagination">
              <?php if ($news_query->max_num_pages > 1): ?>
                <?php
                echo paginate_links([
                  'total'   => $news_query->max_num_pages,
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
