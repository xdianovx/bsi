<?php
/*
Template Name: Награды
*/
function bsi_get_awards_count_by_year($year)
{
  if (!$year) {
    return 0;
  }

  $q = new WP_Query([
    'post_type' => 'award',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_key' => 'award_year',
    'meta_value' => (int) $year,
    'meta_compare' => '=',
  ]);

  return (int) $q->found_posts;
}
get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="page-head archive-page-head">
    <div class="container">
      <div class="award-page__top archive-page__top">
        <h1 class="h1 page-award__title archive-page__title ">
          <?php the_title(); ?>
        </h1>

        <p class="page-award__excerpt archive-page__excerpt"><?= get_the_excerpt() ?></p>
      </div>
    </div>
  </section>


  <section class="page-award__content">
    <div class="container">
      <div class="reviews-archive">
        <?php
        $paged = max(1, get_query_var('paged'));

        $awards_query = new WP_Query([
          'post_type' => 'award',
          'posts_per_page' => 50,
          'paged' => $paged,
          'meta_key' => 'award_year',
          'orderby' => 'meta_value_num',
          'order' => 'DESC',
        ]);

        ?>

        <?php if ($awards_query->have_posts()): ?>

          <?php $current_year = null; ?>

          <?php while ($awards_query->have_posts()):
            $awards_query->the_post(); ?>
            <?php
            $year = get_field('award_year') ?: get_the_date('Y');


            if ($year !== $current_year):
              if ($current_year !== null): ?>
              </div>
          </section>
        <?php endif; ?>

        <section class="awards-year">
          <h2 class="awards-year__title">
            <?= esc_html($year); ?>
          </h2>
          <div class="awards-year__list">
            <?php
            $current_year = $year;
            endif;

            get_template_part('template-parts/awards/card');
            ?>
        <?php endwhile; ?>

        <?php if ($current_year !== null): ?>
        </div>
      </section>
    <?php endif; ?>

    <div class="awards-archive__pagination">
      <?php
      echo paginate_links([
        'total' => $awards_query->max_num_pages,
        'current' => $paged,
        'prev_text' => '&larr; Назад',
        'next_text' => 'Вперёд &rarr;',
        'mid_size' => 2,
      ]);
      ?>
    </div>

  <?php else: ?>
    <div class="awards-archive__empty">
      <p>Наград пока нет.</p>
    </div>
  <?php endif; ?>

  <?php wp_reset_postdata(); ?>
  </div>
  </section>

</main>

<?php
get_footer();