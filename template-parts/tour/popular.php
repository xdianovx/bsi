<?php

// Получаем страницу туров (page template с именем "Туры")
$tours_page_url = '';
$tours_pages = get_pages([
  'meta_key' => '_wp_page_template',
  'meta_value' => 'page-tours.php',
  'post_status' => 'publish',
  'number' => 1,
]);
if (!empty($tours_pages)) {
  $tours_page_url = get_permalink($tours_pages[0]->ID);
}

$homepage_tour_ids = function_exists('bsi_get_homepage_featured_tour_ids')
  ? bsi_get_homepage_featured_tour_ids()
  : [];

$common_tour_q = [
  'post_type' => 'tour',
  'post_status' => 'publish',
  'ignore_sticky_posts' => true,
  'no_found_rows' => true,
  'update_post_meta_cache' => false,
  'update_post_term_cache' => false,
];

if (!empty($homepage_tour_ids)) {
  $tour_query = new WP_Query(array_merge($common_tour_q, [
    'posts_per_page' => -1,
    'post__in' => $homepage_tour_ids,
    'orderby' => 'post__in',
  ]));
} else {
  $tour_query = new WP_Query(array_merge($common_tour_q, [
    'posts_per_page' => 12,
    'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'meta_query' => [
      [
        'key' => 'is_popular',
        'value' => '1',
        'compare' => '=',
      ],
    ],
  ]));
}

$tour_posts = $tour_query->posts;
wp_reset_postdata();

$country_ids = [];

if (!empty($tour_posts)) {
  foreach ($tour_posts as $tour_post) {
    $tour_id = (int) $tour_post->ID;
    $ids = function_exists('bsi_get_tour_country_ids') ? bsi_get_tour_country_ids($tour_id) : [];
    foreach ($ids as $c) {
      $c = (int) $c;
      if ($c > 0) {
        $country_ids[] = $c;
      }
    }
  }
}

$country_ids = array_values(array_unique(array_filter($country_ids)));

$promo_countries = [];

if (!empty($country_ids)) {
  $promo_countries = get_posts([
    'post_type' => 'country',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
    'post_parent' => 0,
    'post__in' => $country_ids,
    'no_found_rows' => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);

  if (class_exists('Collator')) {
    try {
      $collator = new Collator('ru_RU');
      usort($promo_countries, function ($a, $b) use ($collator) {
        return $collator->compare($a->post_title, $b->post_title);
      });
    } catch (Throwable $e) {
      usort($promo_countries, function ($a, $b) {
        return mb_strcasecmp($a->post_title, $b->post_title);
      });
    }
  } else {
    usort($promo_countries, function ($a, $b) {
      return mb_strcasecmp($a->post_title, $b->post_title);
    });
  }
}

$items = [];

foreach ($tour_posts as $tour_post) {
  $tour_id = (int) $tour_post->ID;
  $tour_data = function_exists('bsi_get_tour_card_query_var') ? bsi_get_tour_card_query_var($tour_id) : [];
  if (empty($tour_data)) {
    continue;
  }
  $items[] = $tour_data;
}
?>

<?php if (!empty($items)): ?>
  <section class="popular-tours__section">
    <div class="container">
      <div class="title-wrap news-slider__title-wrap">
        <div class="news-slider__title-wrap-left">
          <h2 class="h2 news-slider__title">Лучшие экскурсионные туры</h2>
          <div class="slider-arrow-wrap news-slider__arrows-wrap">
            <div class="slider-arrow slider-arrow-prev popular-tours-arrow-prev" tabindex="-1" role="button"
              aria-label="Previous slide" aria-controls="swiper-wrapper-popular-tours" aria-disabled="true">
            </div>
            <div class="slider-arrow slider-arrow-next popular-tours-arrow-next" tabindex="0" role="button"
              aria-label="Next slide" aria-controls="swiper-wrapper-popular-tours" aria-disabled="false">
            </div>
          </div>

          <?php if ($tours_page_url): ?>
            <a href="<?php echo esc_url($tours_page_url); ?>"
               class="title-wrap__link link-arrow title-wrap__link-education">
              <span>Все туры </span>
              <div class="link-arrow__icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="24"
                     height="24"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.5"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
                  <path d="M7 7h10v10"></path>
                  <path d="M7 17 17 7"></path>
                </svg>
              </div>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="promo-filter popular-tours-filter">
        <button class="promo-filter__btn --all active js-promo-filter-btn" data-country="">
          Все
        </button>

        <?php foreach ($promo_countries as $country): ?>
          <?php
          $country_id = (int) $country->ID;
          $country_title = (string) get_the_title($country_id);
          $country_slug = (string) $country->post_name;

          $flag_field = function_exists('get_field') ? get_field('flag', $country_id) : '';
          $flag_url = '';

          if ($flag_field) {
            if (is_array($flag_field) && !empty($flag_field['url'])) {
              $flag_url = (string) $flag_field['url'];
            } elseif (is_string($flag_field)) {
              $flag_url = (string) $flag_field;
            }
          }
          ?>

          <button class="promo-filter__btn js-promo-filter-btn" data-country="<?php echo esc_attr($country_id); ?>"
            data-country-slug="<?php echo esc_attr($country_slug); ?>">
            <?php if ($flag_url): ?>
              <span class="promo-filter__flag-wrap">
                <img src="<?php echo esc_url($flag_url); ?>" alt="<?php echo esc_attr($country_title); ?>"
                  class="promo-filter__flag" width="28" height="28" loading="eager" decoding="async">
              </span>
            <?php endif; ?>

            <span class="promo-filter__title"><?php echo esc_html($country_title); ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="popular-tours__content">
        <div class="swiper popular-tours-slider">
          <div class="swiper-wrapper">
            <?php foreach ($items as $item): ?>
              <div class="swiper-slide" data-country="<?php echo esc_attr($item['country_id'] ?? ''); ?>"
                data-country-slug="<?php echo esc_attr($item['country_slug'] ?? ''); ?>">
                <?php
                set_query_var('tour', $item);
                get_template_part('template-parts/tour/card');
                ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </section>
<?php endif; ?>