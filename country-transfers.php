<?php
/**
 * Трансферы страны — /country/{slug}/transfery/
 *
 * По образцу country-deposits.php. Контент — ACF страны `transfers_content`,
 * под ним кнопка на поиск в Само (`transfers_samo_url`).
 * Страница есть только в режиме `page` (bsi_country_transfers_mode), иначе 404.
 */

global $country_transfers_data;

$country = $country_transfers_data['country'] ?? null;

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
}

$country_id = $country instanceof WP_Post ? (int) $country->ID : 0;
$country_title = $country instanceof WP_Post ? (string) $country->post_title : '';

if (bsi_country_transfers_mode($country_id) !== 'page') {
  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  nocache_headers();
  get_template_part('404');
  exit;
}

/* H1 в предложном падеже («Трансферы в Южной Корее»), иначе «Трансферы: {Страна}». */
$country_locative = function_exists('bsi_country_locative_title')
  ? bsi_country_locative_title($country_id)
  : '';

$transfers_h1 = $country_locative !== '' && $country_locative !== $country_title
  ? 'Трансферы в ' . $country_locative
  : 'Трансферы: ' . $country_title;

$transfers_content = (string) get_field('transfers_content', $country_id);
$transfers_samo_url = bsi_country_transfers_samo_url($country_id);

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
            <h1 class="h1"><?= esc_html($transfers_h1); ?></h1>
          </div>

          <?php if (trim(wp_strip_all_tags($transfers_content)) !== ''): ?>
            <div class="editor-content">
              <?= apply_filters('the_content', $transfers_content); ?>
            </div>
          <?php endif; ?>

          <?php if ($transfers_samo_url !== ''): ?>
            <div class="country-transfers__cta">
              <a href="<?= esc_url($transfers_samo_url); ?>" class="btn btn-accent" target="_blank" rel="noopener">
                Подобрать трансфер
              </a>
              <p class="country-transfers__cta-note">Поиск откроется в новой вкладке в системе бронирования BSI</p>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
