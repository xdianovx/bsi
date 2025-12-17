<?php
global $country_hotels_data;

$country = $country_hotels_data['country'] ?? null;
$country_slug = $country_hotels_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

if (!$country instanceof WP_Post) {
  get_header();
  echo '<main class="site-main"><div class="container"><p>Страна не найдена.</p></div></main>';
  get_footer();
  return;
}

// 1) Получаем все отели страны
$hotels = get_posts([
  'post_type' => 'hotel',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'fields' => 'ids',
  'meta_query' => [
    [
      'key' => 'hotel_country',
      'value' => $country->ID,
      'compare' => '=',
    ],
  ],
]);

// 2) Группируем по курорту (такса resort)
$hotels_by_resort = []; // [resort_name => ['term' => WP_Term|null, 'items' => [hotel_id,...]]]

foreach ($hotels as $hotel_id) {
  // ACF taxonomy field (return_format => id)
  $resort_id = function_exists('get_field') ? get_field('hotel_resort', $hotel_id) : 0;

  if (is_array($resort_id)) {
    $resort_id = (int) reset($resort_id);
  } elseif ($resort_id instanceof WP_Term) {
    $resort_id = (int) $resort_id->term_id;
  } else {
    $resort_id = (int) $resort_id;
  }

  $resort_term = null;

  // fallback: если ACF не вернул, но терм реально проставлен в таксономии
  if (!$resort_id) {
    $terms = get_the_terms($hotel_id, 'resort');
    if (!empty($terms) && !is_wp_error($terms)) {
      $resort_term = $terms[0];
      $resort_id = (int) $resort_term->term_id;
    }
  }

  if ($resort_id && !$resort_term) {
    $resort_term = get_term($resort_id, 'resort');
    if (is_wp_error($resort_term)) {
      $resort_term = null;
    }
  }

  $group_title = $resort_term ? $resort_term->name : 'Без курорта';

  if (!isset($hotels_by_resort[$group_title])) {
    $hotels_by_resort[$group_title] = [
      'term' => $resort_term,
      'items' => [],
    ];
  }

  $hotels_by_resort[$group_title]['items'][] = $hotel_id;
}

// 3) Сортируем группы по алфавиту
uksort($hotels_by_resort, function ($a, $b) {
  return strcasecmp($a, $b);
});

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

        <?php if (!empty($hotels)): ?>
          <div class="">
            <h1 class="h1 country-hotels__title">
              <?= esc_html($country->post_title); ?> список отелей
            </h1>

            <div class="country-hotels__counter">
              Нашли отелей: <?= (int) count($hotels); ?>
            </div>

            <div class="country-hotels__wrap">

              <?php foreach ($hotels_by_resort as $resort_title => $data): ?>
                <div class="country-hotels__item">
                  <h2 class="country-hotels__item-title">
                    <?= esc_html($resort_title); ?>
                  </h2>

                  <div class="country-hotels__list">
                    <?php foreach ($data['items'] as $hotel_id): ?>
                      <?php
                      $rating = function_exists('get_field') ? get_field('rating', $hotel_id) : '';
                      ?>
                      <div class="country-hotel">
                        <div class="country-hotel__wrap">
                          <a href="<?= esc_url(get_permalink($hotel_id)); ?>"
                             class="country-hotel__title">
                            <?= esc_html(get_the_title($hotel_id)); ?>
                          </a>

                          <?php if ($rating): ?>
                            <span class="hotel-rating country-hotel__rating">
                              <?= esc_html($rating); ?>*
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
        <?php else: ?>
          <div class="page-country__content">
            <p>Отелей пока нет.</p>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>