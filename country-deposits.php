<?php
/**
 * Депозиты в отелях страны — /country/{slug}/depozity/
 *
 * По образцу country-hotels-info.php: выводит одну запись CPT hotel_deposit,
 * привязанную к стране через ACF `hotel_deposit_country`.
 * Шаблон грузится через роутер в single-country.php после установки
 * глобальной $country_deposits_data ({country: WP_Post, country_slug: string}).
 */

global $country_deposits_data;

$country = $country_deposits_data['country'] ?? null;
$country_slug = $country_deposits_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;
$country_title = $country ? (string) $country->post_title : '';

/* H1 в родительном падеже («Депозиты в отелях Китая») — хелпер inc/helpers/country-cases.php.
   Если падеж не определён, безопасная формулировка «Депозиты в отелях: {Страна}». */
$country_genitive = $country_id && function_exists('bsi_country_genitive_title')
  ? bsi_country_genitive_title($country_id)
  : '';

if ($country_genitive !== '') {
  $deposits_h1 = 'Депозиты в отелях ' . $country_genitive;
} elseif ($country_title !== '') {
  $deposits_h1 = 'Депозиты в отелях: ' . $country_title;
} else {
  $deposits_h1 = 'Депозиты в отелях';
}

/* Одна запись hotel_deposit на страну — берём последнюю опубликованную. */
$deposit_q = $country_id ? new WP_Query([
  'post_type' => 'hotel_deposit',
  'post_status' => 'publish',
  'posts_per_page' => 1,
  'orderby' => 'date',
  'order' => 'DESC',
  'meta_query' => [
    [
      'key' => 'hotel_deposit_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ],
]) : null;

/* Записи нет — раздела для этой страны не существует. Кнопки в меню тоже
   нет, попасть можно только по прямому URL: отдаём 404, чтобы не плодить
   пустые страницы в индексе. */
if (!$deposit_q || !$deposit_q->have_posts()) {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  nocache_headers();
  get_template_part('404');
  exit;
}

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
            <h1 class="h1"><?= esc_html($deposits_h1); ?></h1>
          </div>

          <?php $deposit_q->the_post(); ?>
          <div class="editor-content">
            <?php the_content(); ?>
          </div>
          <?php wp_reset_postdata(); ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
