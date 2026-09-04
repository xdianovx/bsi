<?php
/**
 * Страница отеля из хаба BSIHOTELS.
 * Подключается из inc/hotels-api/hotel-page.php по адресу
 * /country/{country}/hotel/{slug}/, когда отеля нет в WordPress.
 *
 * Вёрстка общая с отелями WordPress: шаблон только собирает view-модель
 * (inc/hotel-page/view.php) и отдаёт её партиалам template-parts/hotel-page/.
 */

global $bsi_hotels_api_hotel;

$hotel = $bsi_hotels_api_hotel['hotel'] ?? [];
$country = $bsi_hotels_api_hotel['country'] ?? null;

if (!$hotel || !$country instanceof WP_Post) {
  return;
}

$view = bsi_hotel_view_from_api($hotel, $country);

get_header();
?>

<main class="site-main hotel-page">

  <?php
  /* Крошки — общим механизмом темы; цепочку подставляет
     фильтр wpseo_breadcrumb_links в inc/hotels-api/seo.php. */
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>', '</p></div></div>');
  }

  get_template_part('template-parts/hotel-page/head', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/gallery', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/nav', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/rooms', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/sections', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/amenities', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/facts', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/map', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/request', null, ['view' => $view]);
  ?>

  <section>
    <div class="container">
      <div class="callout callout-neutral">
        <h3 class="callout__title">
          Информация об отеле носит ознакомительный характер и подвержена периодическим изменениям.
        </h3>

        <p>
          Перед бронированием уточните актуальные условия размещения и цены у менеджеров BSI Group.
        </p>
      </div>
    </div>
  </section>

  <?php if ($view['back']): ?>
    <section class="hp-back">
      <div class="container">
        <a href="<?= esc_url($view['back']['url']); ?>"><?= esc_html($view['back']['label']); ?></a>
      </div>
    </section>
  <?php endif; ?>

</main>

<?php get_footer(); ?>
