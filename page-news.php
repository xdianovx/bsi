<?php
/*
Template Name: Новости
*/

get_header();

$news_terms = get_terms([
  'taxonomy' => 'news_type',
  'hide_empty' => true,
]);

$paged = max(1, get_query_var('paged'));
$news_query = new WP_Query([
  'post_type' => 'news',
  'posts_per_page' => 9,
  'paged' => $paged,
  'orderby' => 'date',
  'order' => 'DESC',
]);
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>



  <section class="archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 archive-page__title">
          Новости BSI Group
        </h1>

        <div class="archive-page__excerpt --row">
          <p>Здесь мы делимся важными событиями компании, обновлениями по направлениям и свежими предложениями для
            партнёров и туристов. Следите за новостями, чтобы не пропускать выгодные акции и изменения на рынке туризма.
          </p>
        </div>
      </div>
    </div>
  </section>



  <section class="archive-page__content-section news-page__content-section">
    <div class="container">

      <div class="news-filter">
        <button class="news-filter__btn js-news-filter-btn is-active"
                data-term="">
          Все
        </button>

        <?php if (!empty($news_terms) && !is_wp_error($news_terms)): ?>
          <?php foreach ($news_terms as $term): ?>
            <button class="news-filter__btn js-news-filter-btn"
                    data-term="<?php echo esc_attr($term->slug); ?>">
              <?php echo esc_html($term->name); ?>
            </button>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if ($news_query->have_posts()): ?>
        <div class="news-grid js-news-list">
          <?php while ($news_query->have_posts()):
            $news_query->the_post(); ?>
            <?php get_template_part('template-parts/news/card'); ?>
          <?php endwhile; ?>
        </div>

        <div class="news-pagination">
          <?php
          echo paginate_links([
            'total' => $news_query->max_num_pages,
            'current' => $paged,
            'prev_text' => '&larr; Назад',
            'next_text' => 'Вперед &rarr;',
            'mid_size' => 2,
          ]);
          ?>
        </div>
      <?php else: ?>
        <div class="no-news">
          <p>Новостей пока нет.</p>
        </div>
      <?php endif; ?>

      <?php wp_reset_postdata(); ?>
    </div>

  </section>

</main>

<?php get_footer(); ?>