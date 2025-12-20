<?php
/**
 * Template: /country/{slug}/entry-rules/
 * Ищет 1 пост entry_rules по meta entry_rules_country = ID страны
 */

$country_slug = (string) get_query_var('country_entry_rules');
$country = $country_slug ? get_page_by_path($country_slug, OBJECT, 'country') : null;

if (!$country instanceof WP_Post) {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  get_header();
  get_footer();
  exit;
}

$country_id = (int) $country->ID;

// Ищем правила въезда для страны
$rules_q = new WP_Query([
  'post_type' => 'entry_rules',
  'post_status' => 'publish',
  'posts_per_page' => 1,
  'meta_query' => [
    [
      'key' => 'entry_rules_country',
      'value' => $country_id,
      'compare' => '=',
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

          <h1 class="h1">
            <?= esc_html($country->post_title); ?> — Правила въезда
          </h1>

          <?php if ($rules_q->have_posts()): ?>
            <?php $rules_q->the_post(); ?>

            <div class="editor-content">
              <?php the_content(); ?>
            </div>

            <?php wp_reset_postdata(); ?>
          <?php else: ?>
            <p>Пока нет правил въезда для этой страны.</p>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>