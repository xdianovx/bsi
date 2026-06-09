<?php
/**
 * Информация об отелях страны — /country/{slug}/informaciya-ob-otelyah/
 *
 * По образцу памятки туристам (country-memo.php): выводит записи CPT
 * hotel_info, привязанные к стране через ACF `hotel_info_country`.
 * Шаблон грузится через роутер в single-country.php после установки
 * глобальной $country_hotels_info_data ({country: WP_Post, country_slug: string}).
 */

global $country_hotels_info_data;

$country = $country_hotels_info_data['country'] ?? null;
$country_slug = $country_hotels_info_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;
$country_title = $country ? (string) $country->post_title : '';

/* H1 в предложном падеже («Информация об отелях в Японии»). */
$country_locative = $country_id && function_exists('bsi_country_locative_title')
  ? bsi_country_locative_title($country_id)
  : $country_title;

$hotels_info_h1 = $country_locative !== ''
  ? 'Информация об отелях в ' . $country_locative
  : 'Информация об отелях';

/* Записи CPT hotel_info по стране. */
$hotel_info_q = $country_id ? new WP_Query([
  'post_type' => 'hotel_info',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'orderby' => 'date',
  'order' => 'DESC',
  'meta_query' => [
    [
      'key' => 'hotel_info_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ],
]) : null;

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

          <div class="title-wrap">
            <h1 class="h1"><?= esc_html($hotels_info_h1); ?></h1>
          </div>

          <div class="country-hotels-info">
            <?php if ($hotel_info_q && $hotel_info_q->have_posts()): ?>
              <?php while ($hotel_info_q->have_posts()):
                $hotel_info_q->the_post(); ?>
                <article class="country-hotels-info__item">
                  <div class="country-hotels-info__body editor-content">
                    <?php the_content(); ?>
                  </div>
                </article>
              <?php endwhile; ?>
              <?php wp_reset_postdata(); ?>
            <?php else: ?>
              <div class="country-hotels-info__empty">
                Пока нет информации об отелях для этой страны.
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
