<?php
global $country_hotels_data;
$country = $country_hotels_data['country'];
$country_slug = $country_hotels_data['country_slug'];

// Получаем все отели этой страны
$hotels = get_posts([
  'post_type' => 'hotel',
  'posts_per_page' => -1,
  'meta_query' => [
    [
      'key' => 'hotel_country',
      'value' => $country->ID,
      'compare' => '='
    ]
  ],
  'meta_key' => 'hotel_city', // Сортируем по городу
  'orderby' => 'meta_value',
  'order' => 'ASC'
]);

// Группируем отели по городам из ACF поля
$hotels_by_city = [];
foreach ($hotels as $hotel) {
  $city = get_field('hotel_city', $hotel->ID); // Получаем город из ACF

  // Если город не указан, используем "Без города"
  if (empty($city)) {
    $city = 'Без города';
  }

  if (!isset($hotels_by_city[$city])) {
    $hotels_by_city[$city] = [];
  }
  $hotels_by_city[$city][] = $hotel;
}

// Сортируем города по алфавиту
ksort($hotels_by_city);

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
          <?= get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>


        <?php if ($hotels): ?>
          <div class="">

            <h1 class="h1 country-hotels__title"><?php echo $country->post_title; ?> список отелей </h1>

            <div class="country-hotels__counter">
              Нашли отелей: <?php echo count($hotels); ?>
            </div>

            <div class="country-hotels__wrap">

              <?php foreach ($hotels_by_city as $city => $city_hotels): ?>
                <div class="country-hotels__item">
                  <h2 class="country-hotels__item-title">
                    <?php echo esc_html($city); ?>
                  </h2>

                  <div class="country-hotels__list">
                    <?php foreach ($city_hotels as $hotel):
                      $rating = get_field('rating', $hotel->ID);
                      $address = get_field('address', $hotel->ID);
                      ?>

                      <div class="country-hotel">

                        <div class="country-hotel__wrap">
                          <a href="<?php echo get_permalink($hotel->ID); ?>"
                             class="country-hotel__title">
                            <?php echo esc_html($hotel->post_title); ?>
                          </a>

                          <?php if ($rating): ?>
                            <span class="hotel-rating country-hotel__rating">
                              <?php echo $rating; ?>*
                            </span>
                          <?php endif; ?>
                        </div>

                      </div>

                    <?php endforeach; ?>

                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div>

        <?php endif; ?>
      </div>
    </div>
  </section>

</main>

<?php
get_footer();
?>