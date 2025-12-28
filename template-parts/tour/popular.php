<?php

$popular_tour_ids = get_posts([
  'post_type' => 'tour',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'fields' => 'ids',
  'no_found_rows' => true,
  'update_post_meta_cache' => false,
  'update_post_term_cache' => false,
  'meta_query' => [
    [
      'key' => 'is_popular',
      'value' => '1',
      'compare' => '=',
    ],
  ],
]);

$country_ids = [];

if (!empty($popular_tour_ids) && function_exists('get_field')) {
  foreach ($popular_tour_ids as $tour_id) {
    $c = get_field('tour_country', $tour_id);

    if ($c instanceof WP_Post) {
      $c = (int) $c->ID;
    } elseif (is_array($c)) {
      $c = (int) reset($c);
    } else {
      $c = (int) $c;
    }

    if ($c > 0) {
      $country_ids[] = $c;
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
}

$tour_posts = [];

if (!empty($popular_tour_ids)) {
  $tour_posts = get_posts([
    'post_type' => 'tour',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'post__in' => $popular_tour_ids,
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);
}

$items = [];

foreach ($tour_posts as $tour_post) {
  $tour_id = (int) $tour_post->ID;

  $country_id = 0;
  if (function_exists('get_field')) {
    $country_val = get_field('tour_country', $tour_id);
    if ($country_val instanceof WP_Post) {
      $country_id = (int) $country_val->ID;
    } elseif (is_array($country_val)) {
      $country_id = (int) reset($country_val);
    } else {
      $country_id = (int) $country_val;
    }
  }

  $country_title = $country_id ? (string) get_the_title($country_id) : '';
  $country_slug = $country_id ? (string) get_post_field('post_name', $country_id) : '';

  $flag_url = '';
  if ($country_id && function_exists('get_field')) {
    $flag_field = get_field('flag', $country_id);
    if ($flag_field) {
      if (is_array($flag_field) && !empty($flag_field['url'])) {
        $flag_url = (string) $flag_field['url'];
      } elseif (is_string($flag_field)) {
        $flag_url = (string) $flag_field;
      }
    }
  }

  $region_name = '';
  $region_terms = wp_get_post_terms($tour_id, 'region', ['orderby' => 'name', 'order' => 'ASC']);
  if (!is_wp_error($region_terms) && !empty($region_terms)) {
    $region_name = (string) $region_terms[0]->name;
  }

  $location_title = trim($country_title . ($region_name ? ', ' . $region_name : ''));

  $price = '';
  if (function_exists('get_field')) {
    $price_val = get_field('price_from', $tour_id);
    $show_from = get_field('show_price_from', $tour_id) !== false;
    if (is_numeric($price_val)) {
      $price_formatted = number_format((float) $price_val, 0, '.', ' ') . ' руб';
      $price = format_price_with_from($price_formatted, $show_from);
    } elseif (is_string($price_val) && $price_val !== '') {
      $price = format_price_with_from($price_val, $show_from);
    }
  }

  $duration = '';
  if (function_exists('get_field')) {
    $duration_val = get_field('tour_duration', $tour_id);
    if (is_string($duration_val) && $duration_val !== '') {
      $duration = (string) $duration_val;
    }
  }

  $tour_types = [];
  $type_terms = wp_get_post_terms($tour_id, 'tour_type', ['orderby' => 'name', 'order' => 'ASC']);
  if (!is_wp_error($type_terms) && !empty($type_terms)) {
    $tour_types = array_map(function($term) {
      return $term->name;
    }, $type_terms);
  }

  $items[] = [
    'id' => $tour_id,
    'url' => get_permalink($tour_id),
    'image' => get_the_post_thumbnail_url($tour_id, 'large') ?: '',
    'type' => 'Тур',
    'tags' => $tour_types,
    'title' => get_the_title($tour_id),
    'flag' => $flag_url,
    'location_title' => $location_title,
    'price' => $price,
    'duration' => $duration,
    'country_id' => $country_id,
    'country_slug' => $country_slug,
  ];
}
?>

<?php if (!empty($items)): ?>
  <section class="popular-tours__section">
    <div class="container">
      <div class="title-wrap news-slider__title-wrap">
        <div class="news-slider__title-wrap-left">
          <h2 class="h2 news-slider__title">Популярные туры</h2>
          <div class="slider-arrow-wrap news-slider__arrows-wrap">
            <div class="slider-arrow slider-arrow-prev popular-tours-arrow-prev"
                 tabindex="-1"
                 role="button"
                 aria-label="Previous slide"
                 aria-controls="swiper-wrapper-popular-tours"
                 aria-disabled="true">
            </div>
            <div class="slider-arrow slider-arrow-next popular-tours-arrow-next"
                 tabindex="0"
                 role="button"
                 aria-label="Next slide"
                 aria-controls="swiper-wrapper-popular-tours"
                 aria-disabled="false">
            </div>
          </div>
        </div>
      </div>

      <div class="promo-filter popular-tours-filter">
        <button class="promo-filter__btn --all active js-promo-filter-btn"
                data-country="">
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

          <button class="promo-filter__btn js-promo-filter-btn"
                  data-country="<?php echo esc_attr($country_id); ?>"
                  data-country-slug="<?php echo esc_attr($country_slug); ?>">
            <?php if ($flag_url): ?>
              <span class="promo-filter__flag-wrap">
                <img src="<?php echo esc_url($flag_url); ?>"
                     alt="<?php echo esc_attr($country_title); ?>"
                     class="promo-filter__flag">
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
              <div class="swiper-slide"
                   data-country="<?php echo esc_attr($item['country_id']); ?>"
                   data-country-slug="<?php echo esc_attr($item['country_slug']); ?>">
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

