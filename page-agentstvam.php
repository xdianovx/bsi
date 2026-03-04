<?php
/**
 * Template Name: Агентствам
 *
 * Базовый шаблон раздела "Агентствам".
 */

get_header();

$documents_query_args = [
  'post_type' => 'documentation',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'orderby' => 'menu_order title',
  'order' => 'ASC',
];

if (taxonomy_exists('agency_item_type')) {
  $documents_query_args['tax_query'] = [
    [
      'taxonomy' => 'agency_item_type',
      'field' => 'slug',
      'terms' => ['document'],
    ],
  ];
}

$documents_query = new WP_Query($documents_query_args);
?>

<main class="site-main agency-page">
  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section class="agency-page__section">
    <div class="container">
      <div class="agency-page__layout">
        <aside class="agency-page__aside">
          <?php get_template_part('template-parts/pages/agency/sidebar'); ?>
        </aside>

        <div class="agency-page__content">
          <h1 class="h1 agency-page__title">Документы</h1>

          <?php if ($documents_query->have_posts()): ?>
            <div class="agency-docs">
              <?php while ($documents_query->have_posts()): ?>
                <?php $documents_query->the_post(); ?>

                <a class="agency-docs__item" href="<?php the_permalink(); ?>">
                  <svg width="20" height="20" stroke="#1F1F1F" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M11.6668 1.66797H5.00016C4.55814 1.66797 4.13421 1.84357 3.82165 2.15613C3.50909 2.46869 3.3335 2.89261 3.3335 3.33464V16.668C3.3335 17.11 3.50909 17.5339 3.82165 17.8465C4.13421 18.159 4.55814 18.3346 5.00016 18.3346H15.0002C15.4422 18.3346 15.8661 18.159 16.1787 17.8465C16.4912 17.5339 16.6668 17.11 16.6668 16.668V6.66797M11.6668 1.66797C11.9306 1.66754 12.1919 1.71931 12.4356 1.82028C12.6793 1.92125 12.9006 2.06944 13.0868 2.2563L16.0768 5.2463C16.2642 5.43256 16.4128 5.65409 16.5141 5.89811C16.6153 6.14212 16.6673 6.40378 16.6668 6.66797M11.6668 1.66797V5.83464C11.6668 6.05565 11.7546 6.26761 11.9109 6.42389C12.0672 6.58017 12.2791 6.66797 12.5002 6.66797L16.6668 6.66797M8.3335 7.5013H6.66683M13.3335 10.8346H6.66683M13.3335 14.168H6.66683"
                      stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>

                  <span><?php the_title(); ?></span>
                </a>
              <?php endwhile; ?>
              <?php wp_reset_postdata(); ?>
            </div>
          <?php else: ?>
            <div class="agency-page__empty">Документы пока не добавлены.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php get_template_part('template-parts/sections/subscribe'); ?>
</main>

<?php get_footer(); ?>