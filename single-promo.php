<?php
$country_hotels_slug = get_query_var('country_hotels');
$country_promos_slug = get_query_var('country_promos');

if ($country_hotels_slug) {
  $country = get_page_by_path($country_hotels_slug, OBJECT, 'country');

  global $country_hotels_data;
  $country_hotels_data = [
    'country' => $country,
    'country_slug' => $country_hotels_slug,
  ];

  get_template_part('country-hotels'); // твой шаблон списка отелей
  exit;
}

if ($country_promos_slug) {
  $country = get_page_by_path($country_promos_slug, OBJECT, 'country');

  global $country_promos_data;
  $country_promos_data = [
    'country' => $country,
    'country_slug' => $country_promos_slug,
  ];

  get_template_part('country-promo'); // шаблон, который ты уже сделал по примеру
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
      <div class="page-country__about">
        <div class="page-country__title">

          <h1 class="h1 h1-country">
            <?php the_title(); ?>
          </h1>

        </div>
      </div>
  </section>


</main>

<?php
get_footer();

?>