<?php
/**
 * Блок «Событийные туры» на главной (по образцу education/hotels popular).
 * Источник: ACF homepage_event_items (relationship) на главной; fallback — свежие события.
 * Фильтр по стране — клиентский (data-country на слайдах), как у education.
 */

$front_page_id = (int) get_option('page_on_front');
$event_ids = [];

if ($front_page_id && function_exists('get_field')) {
  $acf = get_field('homepage_event_items', $front_page_id);
  if (!empty($acf) && is_array($acf)) {
    $event_ids = array_map('intval', $acf);
  }
}

// Fallback: свежие события (с учётом расписания показа).
if (empty($event_ids)) {
  $event_ids = get_posts(bsi_query_args_append_schedule([
    'post_type' => 'event',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'fields' => 'ids',
    'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows' => true,
  ]));
  $event_ids = array_map('intval', $event_ids);
}

$event_ids = array_values(array_filter($event_ids));
if (empty($event_ids)) {
  return;
}

$event_posts = get_posts([
  'post_type' => 'event',
  'post_status' => 'publish',
  'post__in' => $event_ids,
  'orderby' => 'post__in',
  'posts_per_page' => count($event_ids),
  'ignore_sticky_posts' => true,
  'no_found_rows' => true,
]);

/** Страна события (tour_country) → id. */
$resolve_country = static function (int $event_id): int {
  if (!function_exists('get_field')) {
    return 0;
  }
  $c = get_field('tour_country', $event_id);
  if ($c instanceof WP_Post) {
    return (int) $c->ID;
  }
  if (is_array($c)) {
    return (int) reset($c);
  }
  return (int) $c;
};

$items = [];
$country_ids = [];

foreach ($event_posts as $p) {
  $eid = (int) $p->ID;
  $cid = $resolve_country($eid);
  if ($cid > 0) {
    $country_ids[] = $cid;
  }
  $items[] = [
    'id' => $eid,
    'country_id' => $cid,
    'country_slug' => $cid ? (string) get_post_field('post_name', $cid) : '',
  ];
}

if (empty($items)) {
  return;
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
    usort($promo_countries, static fn($a, $b) => $collator->compare($a->post_title, $b->post_title));
  } else {
    usort($promo_countries, static fn($a, $b) => strcasecmp($a->post_title, $b->post_title));
  }
}

$event_archive = get_page_by_path('sobytiynye-tury');
$event_archive_url = $event_archive ? get_permalink($event_archive->ID) : home_url('/sobytiynye-tury/');
?>

<section class="popular-event-tours__section">
  <div class="container">
    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title">Событийные туры</h2>
        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev popular-event-tours-arrow-prev" tabindex="-1" role="button"
            aria-label="Previous slide" aria-disabled="true"></div>
          <div class="slider-arrow slider-arrow-next popular-event-tours-arrow-next" tabindex="0" role="button"
            aria-label="Next slide" aria-disabled="false"></div>
        </div>
      </div>

      <a href="<?php echo esc_url($event_archive_url); ?>"
        class="title-wrap__link link-arrow popular-event-tours__all-link">
        <span>Все туры</span>
        <div class="link-arrow__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 7h10v10" />
            <path d="M7 17 17 7" />
          </svg>
        </div>
      </a>
    </div>

    <?php if (!empty($promo_countries)): ?>
      <div class="promo-filter popular-event-tours-filter">
        <button class="promo-filter__btn --all active js-promo-filter-btn" data-country="">Все</button>
        <?php foreach ($promo_countries as $country): ?>
          <?php
          $c_id = (int) $country->ID;
          $c_title = (string) get_the_title($c_id);
          $c_slug = (string) $country->post_name;
          $flag_field = function_exists('get_field') ? get_field('flag', $c_id) : '';
          $flag_url = '';
          if ($flag_field) {
            $flag_url = (is_array($flag_field) && !empty($flag_field['url'])) ? (string) $flag_field['url'] : (string) $flag_field;
          }
          ?>
          <button class="promo-filter__btn js-promo-filter-btn" data-country="<?php echo esc_attr($c_id); ?>"
            data-country-slug="<?php echo esc_attr($c_slug); ?>">
            <?php if ($flag_url): ?>
              <span class="promo-filter__flag-wrap">
                <img src="<?php echo esc_url($flag_url); ?>" alt="<?php echo esc_attr($c_title); ?>" class="promo-filter__flag">
              </span>
            <?php endif; ?>
            <span class="promo-filter__title"><?php echo esc_html($c_title); ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="popular-event-tours__content">
      <div class="swiper popular-event-tours-slider">
        <div class="swiper-wrapper">
          <?php foreach ($items as $item): ?>
            <div class="swiper-slide" data-country="<?php echo esc_attr((string) $item['country_id']); ?>"
              data-country-slug="<?php echo esc_attr($item['country_slug']); ?>">
              <?php get_template_part('template-parts/event/card', null, ['post_id' => (int) $item['id']]); ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="popular-event-tours__all-mob">
      <a href="<?php echo esc_url($event_archive_url); ?>" class="btn btn-gray">Все туры</a>
    </div>

  </div>
</section>
