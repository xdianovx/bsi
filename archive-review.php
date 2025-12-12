<?php
get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>



  <section class="archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 archive-page__title">
          Отзывы
        </h1>

        <div class="archive-page__excerpt --row">
          <p>За 35 лет работы мы получили сотни отзывов от партнёров, корпоративных клиентов и туристов. Для нас это не
            просто «слова благодарности» — это реальный показатель того, что мы делаем свою работу честно и
            профессионально.

          </p>

          <p>Здесь собраны живые истории сотрудничества с BSI Group: проекты, в которых мы вместе решали сложные задачи,
            запускали новые направления и поддерживали высокий уровень сервиса в любое время.</p>
        </div>
      </div>
    </div>
  </section>


  <section class="archive-page__content-section reviews-page__content-section">
    <div class="container">
      <div class="reviews-page__list">

        <?php
        $paged = get_query_var('paged') ? (int) get_query_var('paged') : 1;

        $reviews_query = new WP_Query([
          'post_type' => 'review',
          'post_status' => 'publish',
          'posts_per_page' => -1,
          'paged' => $paged,
          'orderby' => 'date',
          'order' => 'DESC',
        ]);
        ?>

        <?php if ($reviews_query->have_posts()): ?>
          <?php while ($reviews_query->have_posts()):
            $reviews_query->the_post(); ?>
            <?php get_template_part('template-parts/reviews/card'); ?>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="reviews-archive__empty">
            <p>Отзывов пока нет.</p>
          </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>


      </div>
    </div>
  </section>

</main>

<?php
get_footer();