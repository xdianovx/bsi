<?php
get_header();

$term = get_queried_object();

if (!$term || empty($term->term_id)) {
  get_footer();
  return;
}

$ancestors = get_ancestors($term->term_id, 'region');
$country_term_id = !empty($ancestors) ? end($ancestors) : $term->term_id;
$country_term = get_term($country_term_id, 'region');

$children = get_terms([
  'taxonomy' => 'region',
  'hide_empty' => false,
  'parent' => $term->term_id,
]);

$resorts = get_terms([
  'taxonomy' => 'resort',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
  'meta_query' => [
    [
      'key' => 'resort_region',
      'value' => $term->term_id,
      'compare' => '=',
    ],
  ],
]);

if (empty($resorts) || is_wp_error($resorts)) {
  $resorts = [];
}

$paged = max(1, get_query_var('paged'));

$posts_query = new WP_Query([
  'post_type' => ['hotel'],
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'paged' => $paged,
  'tax_query' => [
    [
      'taxonomy' => 'region',
      'field' => 'term_id',
      'terms' => $term->term_id,
      'include_children' => true,
    ],
  ],
]);
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <!-- <section class="archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 archive-page__title"><?= esc_html($term->name); ?></h1>

        <?php if (!empty($term->description)): ?>
          <div class="archive-page__excerpt --row">
            <?= wp_kses_post(wpautop($term->description)); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section> -->

  <section class="">
    <div class="container">
      <div class="coutry-page__wrap">
        <!-- Aside -->
        <aside class="coutry-page__aside">
          <?= get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>


        <!--  -->
        <div class="page-country__content">
          <h1 class="h1 country-promos__title">
            <?= esc_html($term->name); ?>
          </h1>

          <?php
          $term_excerpt = '';
          if (function_exists('get_field')) {
            $term_excerpt = get_field('excerpt', 'term_' . $term->term_id);
            if (empty($term_excerpt)) {
              $term_excerpt = get_field('term_excerpt', 'term_' . $term->term_id);
            }
          }
          if (empty($term_excerpt) && !empty($term->description)) {
            $term_excerpt = $term->description;
          }
          ?>

          <?php if (!empty($term_excerpt)): ?>
            <div class="archive-page__excerpt">
              <?= wp_kses_post(wpautop($term_excerpt)); ?>
            </div>
          <?php endif; ?>

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
          <?php if (!empty($children) && !is_wp_error($children)): ?>
            <div class="region-children">
              <?php foreach ($children as $child): ?>
                <a class="region-children__item"
                   href="<?= esc_url(get_term_link($child)); ?>">
                  <?= esc_html($child->name); ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($posts_query->have_posts()): ?>
            <div class="region-posts-grid">
              <?php while ($posts_query->have_posts()):
                $posts_query->the_post(); ?>
                <?php get_template_part('template-parts/hotels/card'); ?>
              <?php endwhile; ?>
            </div>

            <div class="region-posts-pagination">
              <?= paginate_links([
                'total' => $posts_query->max_num_pages,
                'current' => $paged,
                'prev_text' => '&larr; Назад',
                'next_text' => 'Вперёд &rarr;',
                'mid_size' => 2,
              ]); ?>
            </div>

          <?php endif; ?>

          <?php wp_reset_postdata(); ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>