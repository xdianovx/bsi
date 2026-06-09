<?php
/**
 * Информация об отелях страны — /country/{slug}/informaciya-ob-otelyah/
 *
 * Раздел-«события по отелям» (например: ремонт бассейна, реновация и т.п.).
 * Шаблон грузится через роутер в single-country.php после установки
 * глобальной $country_hotels_info_data ({country: WP_Post, country_slug: string}).
 *
 * TODO (контент-модель ещё не утверждена):
 *  - Источник данных пока не определён. Сейчас выводится WYSIWYG-поле
 *    `hotels_info_content` на записи country (если заведено в ACF),
 *    иначе — заглушка. Когда решим структуру (отдельный CPT «событие по
 *    отелю» / repeater на country / привязка к hotel) — заменить блок
 *    .country-hotels-info__body на реальный вывод списка.
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

/* Временный источник контента — WYSIWYG-поле на country (если заведено). */
$hotels_info_content = $country_id && function_exists('get_field')
  ? get_field('hotels_info_content', $country_id)
  : '';

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
            <?php if (!empty($hotels_info_content)): ?>
              <div class="country-hotels-info__body editor-content">
                <?= wp_kses_post($hotels_info_content); ?>
              </div>
            <?php else: ?>
              <div class="country-hotels-info__empty">
                Раздел в разработке.
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
