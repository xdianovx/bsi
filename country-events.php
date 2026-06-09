<?php
/**
 * Каталог событийных туров страны — /country/{slug}/sobytiynye-tury/
 *
 * Шаблон загружается через роутер в single-country.php после установки
 * глобальной $country_events_data ({country: WP_Post, country_slug: string}).
 *
 * Страна «залочена»: фильтр по стране не показывается, AJAX-эндпоинты
 * (event_tours_filter) получают country из data-locked-country на корне
 * [data-event-tours-filter] (см. js/modules/ajax/event-tours.js). На бэке
 * country → tour_country (=).
 *
 * Управление: счётчик, переключатель валюты, сортировка, пагинация
 * (по умолчанию 4 карточки на страницу через data-per-page).
 *
 * Использует функции из inc/requests/event-tours-filter.php:
 *  - bsi_event_tours_parse_request_filters()
 *  - bsi_event_tours_get_matching_post_ids()
 *  - bsi_event_tours_sort_ids()
 */

global $country_events_data;

$country = $country_events_data['country'] ?? null;
$country_slug = $country_events_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;
$country_title = $country ? (string) $country->post_title : '';

/* tour_country хранит корневую страну ветки — поднимаемся к корню. */
$country_root_id = $country_id;
$parent = $country_root_id ? wp_get_post_parent_id($country_root_id) : 0;
while ($parent) {
  $country_root_id = (int) $parent;
  $parent = wp_get_post_parent_id($country_root_id);
}

/* H1 в предложном падеже («Событийные туры в Японии»). */
$country_locative = $country_id && function_exists('bsi_country_locative_title')
  ? bsi_country_locative_title($country_id)
  : $country_title;

$events_h1 = $country_locative !== ''
  ? 'Событийные туры в ' . $country_locative
  : 'Событийные туры';

/* По умолчанию — 4 карточки на страницу. */
$per_page = 4;

/* Начальный набор — все событийные туры страны, сортировка «сначала ближайшие». */
$event_tours_total = 0;
$event_tours_slice_ids = [];
if (function_exists('bsi_event_tours_get_matching_post_ids') && function_exists('bsi_event_tours_sort_ids')) {
  $f = function_exists('bsi_event_tours_parse_request_filters')
    ? bsi_event_tours_parse_request_filters()
    : ['country_id' => 0, 'region_id' => 0, 'tour_type_id' => 0, 'resort_id' => 0, 'search' => '', 'date_from' => '', 'date_to' => '', 'paged' => 1, 'sort' => 'date_asc', 'view' => 'tiles'];
  $f['country_id'] = $country_root_id;

  $country_event_ids = bsi_event_tours_get_matching_post_ids($f, [], false);
  $country_event_ids = bsi_event_tours_sort_ids($country_event_ids, 'date_asc');
  $event_tours_total = count($country_event_ids);
  $event_tours_slice_ids = array_slice($country_event_ids, 0, $per_page);
}

$tours_query = new WP_Query([
  'post_type' => 'event',
  'post_status' => 'publish',
  'post__in' => !empty($event_tours_slice_ids) ? $event_tours_slice_ids : [0],
  'orderby' => 'post__in',
  'posts_per_page' => $per_page,
  'no_found_rows' => true,
]);

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
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">

          <div class="title-wrap">
            <h1 class="h1"><?= esc_html($events_h1); ?></h1>
          </div>

          <div class="country-tours country-tours--country-events" data-event-tours-filter data-initial-paged="1"
            data-locked-country="<?= (int) $country_root_id; ?>" data-per-page="<?= (int) $per_page; ?>">

            <div class="country-tours__head">
              <div class="country-tours__head-left">
                <label class="ui-checkbox country-tours__currency-toggle">
                  <input type="checkbox" class="ui-checkbox__input js-education-show-original-currency"
                    name="show_original_currency_event_catalog" value="1">
                  <span class="ui-checkbox__mark"></span>
                  <span class="ui-checkbox__text">Показать в валюте</span>
                </label>
              </div>

              <div class="country-tours__head-right">
                <!-- Сортировка -->
                <div class="country-tours__sort js-dropdown" data-tours-sort>
                  <button type="button" class="js-dropdown-trigger country-tours__sort-trigger">
                    <span class="country-tours__sort-text">По дате (сначала ближайшие)</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                      <path d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                            stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                  <div class="js-dropdown-panel country-tours__sort-panel">
                    <div class="country-tours__sort-options">
                      <button type="button" class="country-tours__sort-option is-active" data-value="date_asc">По дате (сначала ближайшие)</button>
                      <button type="button" class="country-tours__sort-option" data-value="date_desc">По дате (сначала поздние)</button>
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
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
