<?php
/**
 * Страница отеля, заведённого в WordPress.
 *
 * Вёрстка общая с отелями из хаба BSIHOTELS: шаблон собирает view-модель
 * (inc/hotel-page/view.php) и отдаёт её партиалам template-parts/hotel-page/.
 */

$view = bsi_hotel_view_from_post(get_the_ID());

get_header();
?>

<main class="hotel-page">

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }

  get_template_part('template-parts/hotel-page/head', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/gallery', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/nav', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/rooms', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/sections', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/amenities', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/facts', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/contacts', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/location', null, ['view' => $view]);
  get_template_part('template-parts/hotel-page/request', null, ['view' => $view]);
  ?>

  <section>
    <div class="container">
      <div class="callout callout-neutral single-hotel__warn">
        <h3 class="callout__title">
          Информация об отеле носит ознакомительный характер и подвержена периодическим изменениям.
        </h3>

        <p>
          Перед бронированием необходимо обязательно уточнить актуальную информацию об оказываемых отелем услугах и его
          номерном фонде у менеджеров туроператора или на официальном сайте отеля.
        </p>
      </div>
    </div>
  </section>

</main>

<?php
get_footer();
