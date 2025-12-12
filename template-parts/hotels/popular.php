<?php

$promo_countries = get_posts([
  'post_type' => 'country',
  'posts_per_page' => -1,
  'post_status' => 'publish',
  'orderby' => 'title',
  'order' => 'ASC',
  'post_parent' => 0,
]);



$items = [
  [
    'url' => '#',
    'image' => get_template_directory_uri() . '/img/hotels/1.webp',
    'type' => 'Отель',
    'tags' => ['Хит продаж'],
    'title' => 'Buahan, A Banyan Tree Escape',
    'flag' => get_template_directory_uri() . '/img/flags/1x1/id.svg',
    'location_title' => 'Индонезия, Бали',
    'price' => 'от 16 876 руб',
  ],
  [
    'url' => '#',
    'image' => get_template_directory_uri() . '/img/hotels/2.jpg',
    'type' => 'Отель',
    'tags' => ['Популярно'],
    'title' => 'The Ocean View Resort',
    'flag' => get_template_directory_uri() . '/img/flags/1x1/it.svg',
    'location_title' => 'Италия, Сицилия',
    'price' => 'от 12 450 руб',
  ],
  [
    'url' => '#',
    'image' => get_template_directory_uri() . '/img/hotels/3.jpg',
    'type' => 'Отель',
    'tags' => [],
    'title' => 'Mountain Retreat Lodge',
    'flag' => get_template_directory_uri() . '/img/flags/1x1/ru.svg',
    'location_title' => 'Россия, Сочи',
    'price' => 'от 8 200 руб',
  ],
];
?>

<section class="popular-hotels__section">
  <div class="container">
    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title">Популярные отели</h2>
        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev popular-hotels-arrow-prev"
               tabindex="-1"
               role="button"
               aria-label="Previous slide"
               aria-controls="swiper-wrapper-6afd786aee0e5cee"
               aria-disabled="true">
          </div>
          <div class="slider-arrow slider-arrow-next popular-hotels-arrow-next"
               tabindex="0"
               role="button"
               aria-label="Next slide"
               aria-controls="swiper-wrapper-6afd786aee0e5cee"
               aria-disabled="false">
          </div>
        </div>
      </div>
      <!-- 
      <div class="title-wrap__buttons">
        <a href="http://localhost:8888/bsinew/novosti/"
           class="title-wrap__link link-arrow">
          <span>Смотреть все</span>
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
      </div> -->
    </div>

    <div class="promo-filter popular-hotels-filter">
      <button class="promo-filter__btn --all active js-promo-filter-btn"
              data-country="">
        Все
      </button>

      <?php foreach ($promo_countries as $country): ?>
        <?php
        $country_id = $country->ID;
        $country_title = get_the_title($country_id);
        $country_slug = $country->post_name;
        $flag_field = get_field('flag', $country_id);
        $flag_url = '';

        if ($flag_field) {
          if (is_array($flag_field) && !empty($flag_field['url'])) {
            $flag_url = $flag_field['url'];
          } elseif (is_string($flag_field)) {
            $flag_url = $flag_field;
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
      <?php wp_reset_postdata(); ?>
    </div>

    <div class="popular-hotels__content">

      <div class="swiper popular-hotels-slider">
        <div class="swiper-wrapper">
          <?php foreach ($items as $item): ?>
            <div class="swiper-slide">
              <?php set_query_var('hotel', $item);
              get_template_part('template-parts/hotels/card'); ?>
            </div>
          <?php endforeach; ?>


        </div>
      </div>
    </div>
</section>