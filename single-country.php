<?php
/* Роутинг: отели/акции внутри страны */
$country_hotels_slug = get_query_var('country_hotels');
$country_promos_slug = get_query_var('country_promos');
$gallery = get_field('galereya', get_the_ID());// или любое другое поле

if ($country_hotels_slug) {
  $country = get_page_by_path($country_hotels_slug, OBJECT, 'country');

  global $country_hotels_data;
  $country_hotels_data = [
    'country' => $country,
    'country_slug' => $country_hotels_slug,
  ];

  get_template_part('country-hotels');
  exit;
}

if ($country_promos_slug) {
  $country = get_page_by_path($country_promos_slug, OBJECT, 'country');

  global $country_promos_data;
  $country_promos_data = [
    'country' => $country,
    'country_slug' => $country_promos_slug,
  ];

  get_template_part('country-promo');
  exit;
}



/* Контекст страны */
$country_id = get_the_ID();

$country_root_id = $country_id;
$parent = wp_get_post_parent_id($country_root_id);
while ($parent) {
  $country_root_id = (int) $parent;
  $parent = wp_get_post_parent_id($country_root_id);
}

/* Запрос новостей по стране */
$news_query = new WP_Query([
  'post_type' => 'news',
  'post_status' => 'publish',
  'posts_per_page' => 4,
  'meta_query' => [
    [
      'key' => 'news_countries',
      'value' => '"' . $country_id . '"',
      'compare' => 'LIKE',
    ],
  ],
]);

/* Событийные туры: в ACF страна — родительская, совпадает с корнем ветки стран */
$country_events_query = new WP_Query([
  'post_type' => 'event',
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'orderby' => 'title',
  'order' => 'ASC',
  'meta_query' => [
    [
      'key' => 'tour_country',
      'value' => $country_root_id,
      'compare' => '=',
    ],
  ],
]);

/* Получение регионов страны (meta_query + fallback через ACF, если meta_query не отрабатывает) */
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

if (empty($regions)) {
  $all_regions = get_terms([
    'taxonomy' => 'region',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
  ]);

  if (!empty($all_regions) && !is_wp_error($all_regions)) {
    foreach ($all_regions as $term) {
      $term_country = get_field('region_country', 'term_' . $term->term_id);
      if ($term_country == $country_id) {
        $regions[] = $term;
      }
    }
  }
}



/* Флаг страны */
$flag = get_field('flag', $country_id);
$flag_url = '';
if ($flag) {
  $flag_url = is_array($flag) && !empty($flag['url']) ? $flag['url'] : $flag;
}

get_header();
?>

<main class="site-main">

  <?php
  /* Хлебные крошки */
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>', '</p></div></div>');
  }
  ?>

  <section>
    <div class="container">
      <div class="coutry-page__wrap">

        <?php /* Aside меню страны */ ?>
        <aside class="coutry-page__aside">
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <?php /* Контент страны */ ?>
        <div class="page-country__content">

          <?php /* Заголовок + краткое описание */ ?>
          <div class="page-country__about">
            <div class="page-country__title">
              <?php if (!wp_get_post_parent_id($country_id) && $flag_url): ?>
                <img src="<?= esc_url($flag_url); ?>"
                     alt="флаг <?= esc_attr(get_the_title($country_id)); ?>">
              <?php endif; ?>

              <h1 class="h1 h1-country">Туры в <?php the_title(); ?></h1>
            </div>

            <p class="page-country__descr"><?= get_the_excerpt(); ?></p>

            <?php get_template_part('template-parts/pages/country/country-info'); ?>
            <div class="country-page__gallery">
              <?php
              get_template_part('template-parts/sections/gallery', null, [
                'gallery' => $gallery,
                'id' => 'hotel_' . get_the_ID(),
              ]);
              ?>
            </div>


          </div>



          <?php /* Контент из редактора */ ?>
          <div class="editor-content page-country__editor-content">
            <?php the_content(); ?>
          </div>

          <?php
          $event_tours_catalog = get_page_by_path('sobytiynye-tury');
          $event_tours_catalog_url = $event_tours_catalog ? get_permalink($event_tours_catalog->ID) : home_url('/sobytiynye-tury/');
          if ($country_events_query->have_posts()):
            ?>
            <section id="country-event-tours" class="page-country__event-tours">
              <div class="page-country__event-tours-top">
                <h2 class="h2">Событийные туры</h2>
                <a href="<?= esc_url($event_tours_catalog_url); ?>" class="title-wrap__link link-arrow">
                  <span>Все событийные туры</span>
                  <div class="link-arrow__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M7 7h10v10" />
                      <path d="M7 17 17 7" />
                    </svg>
                  </div>
                </a>
              </div>
              <div class="country-tours__list">
                <?php
                while ($country_events_query->have_posts()):
                  $country_events_query->the_post();
                  get_template_part('template-parts/event/card-row', null, ['post_id' => (int) get_the_ID()]);
                endwhile;
                wp_reset_postdata();
                ?>
              </div>
            </section>
          <?php endif; ?>

          <?php if ($news_query && $news_query->have_posts()): ?>
            <section class="page-country__news">
              <h2 class="h2">Новости</h2>

              <div class="swiper news-slider-slider">
                <div class="swiper-wrapper">
                  <?php while ($news_query->have_posts()):
                    $news_query->the_post(); ?>
                    <div class="swiper-slide">
                      <?php get_template_part('template-parts/news/card'); ?>
                    </div>
                  <?php endwhile; ?>
                </div>
              </div>

              <?php wp_reset_postdata(); ?>
            </section>
          <?php endif; ?>

        </div>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>