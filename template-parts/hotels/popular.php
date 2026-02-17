<?php

$popular_hotel_ids = get_posts([
  'post_type' => 'hotel',
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

if (!empty($popular_hotel_ids) && function_exists('get_field')) {
  foreach ($popular_hotel_ids as $hotel_id) {
    $c = get_field('hotel_country', $hotel_id);

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

  if (class_exists('Collator')) {
    $collator = new Collator('ru_RU');
    usort($promo_countries, function ($a, $b) use ($collator) {
      return $collator->compare($a->post_title, $b->post_title);
    });
  } else {
    usort($promo_countries, function ($a, $b) {
      return mb_strcasecmp($a->post_title, $b->post_title);
    });
  }
}

$hotel_posts = [];

if (!empty($popular_hotel_ids)) {
  $hotel_posts = get_posts([
    'post_type' => 'hotel',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'post__in' => $popular_hotel_ids,
    'ignore_sticky_posts' => true,
    'no_found_rows' => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
  ]);
}

$items = [];

foreach ($hotel_posts as $hotel_post) {
  $hotel_id = (int) $hotel_post->ID;

  $country_id = 0;
  if (function_exists('get_field')) {
    $country_val = get_field('hotel_country', $hotel_id);
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
  $region_terms = wp_get_post_terms($hotel_id, 'region', ['orderby' => 'name', 'order' => 'ASC']);
  if (!is_wp_error($region_terms) && !empty($region_terms)) {
    $region_name = (string) $region_terms[0]->name;
  }

  $resort_name = '';
  $resort_terms = wp_get_post_terms($hotel_id, 'resort', ['orderby' => 'name', 'order' => 'ASC']);
  if (!is_wp_error($resort_terms) && !empty($resort_terms)) {
    $resort_name = (string) $resort_terms[0]->name;
  }

  $price = '';
  $price_text = '';
  $nights = 0;
  $checkin_date = '';
  $show_from = true;
  if (function_exists('get_field')) {
    $price_val = get_field('price', $hotel_id);
    $show_from_field = get_field('show_price_from', $hotel_id);
    $show_from = $show_from_field !== false;
    if (is_numeric($price_val)) {
      $price = number_format((float) $price_val, 0, '.', ' ');
    } elseif (is_string($price_val) && $price_val !== '') {
      $price = $price_val;
    }

    $price_text_val = get_field('price_text', $hotel_id);
    if ($price_text_val) {
      $price_text = (string) $price_text_val;
    }

    $nights_val = get_field('nights', $hotel_id);
    if (is_numeric($nights_val)) {
      $nights = (int) $nights_val;
    }

    $checkin_date_val = get_field('checkin_date', $hotel_id);
    if ($checkin_date_val) {
      $checkin_date = (string) $checkin_date_val;
    }
  }

  $items[] = [
    'id' => $hotel_id,
    'url' => get_permalink($hotel_id),
    'image' => get_the_post_thumbnail_url($hotel_id, 'large') ?: '',
    'type' => 'Отель',
    'tags' => [],
    'title' => get_the_title($hotel_id),
    'flag' => $flag_url,
    'country_title' => $country_title,
    'region_title' => $region_name,
    'resort_title' => $resort_name,
    'price' => $price,
    'price_text' => $price_text,
    'nights' => $nights,
    'checkin_date' => $checkin_date,
    'country_id' => $country_id,
    'country_slug' => $country_slug,
    'show_price_from' => $show_from,
  ];
}
?>

<?php if (!empty($items)): ?>
  <section class="popular-hotels__section">
    <div class="container">
      <div class="title-wrap news-slider__title-wrap">
        <div class="news-slider__title-wrap-left">
          <h2 class="h2 news-slider__title">Спецпредложения</h2>
          <div class="slider-arrow-wrap news-slider__arrows-wrap">
            <div class="slider-arrow slider-arrow-prev popular-hotels-arrow-prev" tabindex="-1" role="button"
              aria-label="Previous slide" aria-controls="swiper-wrapper-6afd786aee0e5cee" aria-disabled="true">
            </div>
            <div class="slider-arrow slider-arrow-next popular-hotels-arrow-next" tabindex="0" role="button"
              aria-label="Next slide" aria-controls="swiper-wrapper-6afd786aee0e5cee" aria-disabled="false">
            </div>
          </div>
        </div>
      </div>

      <div class="promo-filter popular-hotels-filter">
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
                  class="promo-filter__flag">
              </span>
            <?php endif; ?>

            <span class="promo-filter__title"><?php echo esc_html($country_title); ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="popular-hotels__content">
        <div class="swiper popular-hotels-slider">
          <div class="swiper-wrapper">
            <?php foreach ($items as $item): ?>
              <div class="swiper-slide" data-country="<?php echo esc_attr($item['country_id']); ?>"
                data-country-slug="<?php echo esc_attr($item['country_slug']); ?>">
                <?php
                set_query_var('hotel', $item);
                get_template_part('template-parts/hotels/card');
                ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </section>
<?php endif; ?>