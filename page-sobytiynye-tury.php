<?php
/**
 * Template Name: Событийные туры
 */
get_header();

$paged = max(1, (int) get_query_var('paged'));
$per_page = (int) BSI_EVENT_TOURS_CATALOG_PER_PAGE;

// Начальный запрос событийных туров
$tours_query = new WP_Query(bsi_query_args_append_schedule([
  'post_type' => 'event',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'orderby' => 'title',
  'order' => 'ASC',
]));

// Получаем страны, у которых есть событийные туры
$event_tours_countries_query = new WP_Query(bsi_query_args_append_schedule([
  'post_type' => 'event',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'fields' => 'ids',
]));

$country_ids = [];
if ($event_tours_countries_query->have_posts()) {
  bsi_event_tours_prime_meta_for_ids(array_map('intval', $event_tours_countries_query->posts));

  foreach ($event_tours_countries_query->posts as $tour_id) {
    $country_val = function_exists('get_field') ? get_field('tour_country', $tour_id) : null;
    if ($country_val) {
      if (is_array($country_val)) {
        $country_ids = array_merge($country_ids, array_map('intval', $country_val));
      } elseif (is_numeric($country_val)) {
        $country_ids[] = (int) $country_val;
      } elseif ($country_val instanceof WP_Post) {
        $country_ids[] = (int) $country_val->ID;
      }
    }
  }
  wp_reset_postdata();
}

$country_ids = array_values(array_unique(array_filter($country_ids)));

// Получаем страны по найденным ID
$countries = [];
if (!empty($country_ids)) {
  $countries = get_posts([
    'post_type' => 'country',
    'post_status' => 'publish',
    'post__in' => $country_ids,
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_parent' => 0,
  ]);
}

// Получаем типы событийных туров (отдельная таксономия от «Типов туров»)
$tour_type_terms = get_terms([
  'taxonomy' => BSI_EVENT_TOUR_TYPE_TAXONOMY,
  'hide_empty' => true,
  'object_ids' => $event_tours_countries_query->posts,
  'orderby' => 'name',
  'order' => 'ASC',
]);
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')): ?>
    <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
  <?php endif; ?>

  <?php while (have_posts()):
    the_post(); ?>

    <section class="event-tours-page">
      <div class="container">

        <div class="title-wrap">
          <div class="">
            <h1 class="h1"><?php the_title(); ?></h1>
            <?php if (has_excerpt()): ?>
              <div class="tours-page__title-description">
                <?php the_excerpt(); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <?php
        $evt_banners = function_exists('get_field') ? get_field('event_tours_page_banners') : [];
        $evt_banners_active = [];
        if (!empty($evt_banners)) {
          foreach ($evt_banners as $b) {
            if (!empty($b['evt_banner_is_active'])) {
              $evt_banners_active[] = $b;
            }
          }
        }
        ?>
        <?php if (!empty($evt_banners_active)): ?>
          <?php $evt_is_slider = count($evt_banners_active) > 1; ?>
          <div class="promo-banner__wrap promo-banner__wrap--events">
            <div class="<?php echo $evt_is_slider ? 'swiper promo-banner-slider' : ''; ?>">
              <div class="<?php echo $evt_is_slider ? 'swiper-wrapper' : ''; ?>">
                <?php foreach ($evt_banners_active as $banner): ?>
                  <?php
                  $img_desktop = $banner['evt_banner_image_desktop'];
                  $img_mobile  = $banner['evt_banner_image_mobile'];
                  if (!$img_mobile && $img_desktop) $img_mobile = $img_desktop;
                  $link   = $banner['evt_banner_link'];
                  $target = !empty($banner['evt_banner_target']) ? '_blank' : '_self';
                  ?>
                  <div class="<?php echo $evt_is_slider ? 'swiper-slide' : ''; ?>">
                    <div class="promo-banner-card">
                      <?php if ($link): ?>
                        <a href="<?php echo esc_url($link); ?>"
                           target="<?php echo esc_attr($target); ?>"
                           class="promo-banner-card__link">
                      <?php endif; ?>
                        <picture class="promo-banner-card__picture">
                          <?php if (!empty($img_mobile['url'])): ?>
                            <source srcset="<?php echo esc_url($img_mobile['url']); ?>"
                                    media="(max-width: 767px)">
                          <?php endif; ?>
                          <img src="<?php echo esc_url($img_desktop['url'] ?? ''); ?>"
                               alt="<?php echo esc_attr($img_desktop['alt'] ?? ''); ?>"
                               class="promo-banner-card__img"
                               loading="lazy">
                        </picture>
                      <?php if ($link): ?>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if ($evt_is_slider): ?>
                <div class="swiper-pagination promo-banner-slider-pag"></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (get_the_content()): ?>
          <div class="page-content">
            <?php the_content(); ?>
          </div>
        <?php endif; ?>

        <div class="country-tours" data-event-tours-filter data-initial-paged="<?= (int) $paged; ?>">

          <form class="country-tours__filters" data-tours-form>
            <div class="country-tours__filters-row --events">
              <div class="tours-filter__field tours-filter__field--search">
                <div class="tours-filter__label">Поиск</div>
                <input type="search" class="tours-filter__input" name="event_search" autocomplete="off"
                  placeholder="Название или описание">
              </div>

              <div class="tours-filter__field">
                <div class="tours-filter__label">Направление</div>
                <select class="tours-filter__select" name="country" data-choice="single">
                  <option value="">Все страны</option>
                  <?php if (!empty($countries)): ?>
                    <?php foreach ($countries as $country): ?>
                      <option value="<?= (int) $country->ID; ?>"><?= esc_html($country->post_title); ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>

              <div class="tours-filter__field">
                <div class="tours-filter__label">Город</div>
                <select class="tours-filter__select" name="resort" data-choice="single">
                  <option value="">Все города</option>
                </select>
              </div>

              <div class="tours-filter__field">
                <div class="tours-filter__label">Тип</div>
                <select class="tours-filter__select" name="tour_type" data-choice="single">
                  <option value="">Все типы</option>
                  <?php if (!is_wp_error($tour_type_terms) && !empty($tour_type_terms)): ?>
                    <?php foreach ($tour_type_terms as $t): ?>
                      <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>

              <div class="tours-filter__field">
                <div class="tours-filter__label">Даты событий</div>
                <input type="text" class="tours-filter__input" name="departure_date" data-departure-date
                  placeholder="Выберите даты" readonly>
              </div>

            </div>
          </form>

          <div class="country-tours__head">
            <div class="country-tours__head-left">
              <div class="country-tours__counter js-tours-counter">
                Найдено: <?= (int) $tours_query->found_posts; ?>
              </div>

              <label class="ui-checkbox country-tours__currency-toggle">
                <input type="checkbox" class="ui-checkbox__input js-education-show-original-currency"
                  name="show_original_currency_event_catalog" value="1">
                <span class="ui-checkbox__mark"></span>
                <span class="ui-checkbox__text">Показать в валюте</span>
              </label>

              <button type="button" class="tours-page__reset-btn js-tours-reset" style="display: none;">
                Сбросить фильтры
              </button>
            </div>

            <div class="country-tours__head-right">
              <!-- Сортировка -->
              <div class="country-tours__sort js-dropdown" data-tours-sort>
                <button type="button" class="js-dropdown-trigger country-tours__sort-trigger">
                  <span class="country-tours__sort-text">По названию (А-Я)</span>
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                          stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>
                <div class="js-dropdown-panel country-tours__sort-panel">
                  <div class="country-tours__sort-options">
                    <button type="button" class="country-tours__sort-option is-active" data-value="title_asc">По названию (А-Я)</button>
                    <button type="button" class="country-tours__sort-option" data-value="title_desc">По названию (Я-А)</button>
                    <button type="button" class="country-tours__sort-option" data-value="price_asc">По цене (возрастание)</button>
                    <button type="button" class="country-tours__sort-option" data-value="price_desc">По цене (убывание)</button>
                  </div>
                </div>
              </div>

            </div>
          </div>


          <div class="country-tours__list is-tiles" data-tours-list>
            <?php if ($tours_query->have_posts()): ?>
              <?php while ($tours_query->have_posts()):
                $tours_query->the_post(); ?>
                <?php get_template_part('template-parts/event/card', null, ['post_id' => get_the_ID()]); ?>
              <?php endwhile; ?>
            <?php else: ?>
              <div class="country-tours__empty">
                Пока нет событийных туров.
              </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
          </div>

          <nav class="country-tours__pagination" data-event-tours-pagination
            aria-label="Навигация по страницам каталога"></nav>

        </div>

      </div>
    </section>

  <?php
  endwhile; ?>

</main>

<?php get_footer(); ?>
