<?php
/**
 * Каталог экскурсий страны — /country/{slug}/ekskursii/
 *
 * Шаблон загружается через роутер в single-country.php после установки
 * глобальной $country_excursions_data ({country: WP_Post, country_slug: string}).
 *
 * Использует:
 *  - bsi_get_country_excursion_candidate_ids_cached() — кандидаты страны (15-мин transient)
 *  - bsi_get_excursion_price_from_rub() / bsi_get_excursion_dates_rows() — цены
 *  - bsi_education_convert_price_to_rub() — конвертация в рубли
 *  - AJAX-эндпоинт excursions_filter (inc/requests/excursions-filter.php)
 *
 * Глобальный переключатель валюты `.js-education-show-original-currency` —
 * обработчик в js/modules/education-currency-switcher.js.
 */

global $country_excursions_data;

$country = $country_excursions_data['country'] ?? null;
$country_slug = $country_excursions_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;
$country_title = $country ? (string) $country->post_title : '';

/* H1 в предложном падеже («Экскурсии в Японии») — через хелпер inc/helpers/country-cases.php */
$country_locative = $country_id && function_exists('bsi_country_locative_title')
  ? bsi_country_locative_title($country_id)
  : $country_title;

$excursions_h1 = $country_locative !== ''
  ? 'Экскурсии в ' . $country_locative
  : 'Экскурсии';

$paged = max(1, (int) get_query_var('paged'));
$per_page = 12;

/* Кандидаты — все экскурсии страны, без расписания публикации */
$excursion_ids = $country_id > 0 && function_exists('bsi_get_country_excursion_candidate_ids_cached')
  ? bsi_get_country_excursion_candidate_ids_cached((int) $country_id)
  : [];

$total = count($excursion_ids);
$total_pages = ($total > 0 && $per_page > 0) ? (int) ceil($total / $per_page) : 0;

if ($total_pages > 0) {
  $paged = min($paged, $total_pages);
} else {
  $paged = 1;
}

$page_ids = ($total > 0) ? array_slice($excursion_ids, ($paged - 1) * $per_page, $per_page) : [];

$excursions_query = new WP_Query([
  'post_type'              => 'excursion',
  'post_status'            => 'publish',
  'posts_per_page'         => $per_page,
  'post__in'               => $page_ids !== [] ? $page_ids : [0],
  'orderby'                => 'post__in',
  'no_found_rows'          => true,
  'bsi_skip_schedule'      => true,
  'update_post_meta_cache' => false,
]);
$excursions_query->found_posts = $total;
$excursions_query->max_num_pages = $total_pages;

/* Регионы / курорты / типы / языки — только встречающиеся у экскурсий страны */
$region_terms = !empty($excursion_ids)
  ? wp_get_object_terms($excursion_ids, 'region', ['orderby' => 'name', 'order' => 'ASC'])
  : [];

$type_terms = !empty($excursion_ids)
  ? wp_get_object_terms($excursion_ids, 'excursion_type', ['orderby' => 'name', 'order' => 'ASC'])
  : [];

$language_terms = !empty($excursion_ids)
  ? wp_get_object_terms($excursion_ids, 'excursion_language', ['orderby' => 'name', 'order' => 'ASC'])
  : [];

$region_ids = [];
if (!is_wp_error($region_terms) && !empty($region_terms)) {
  foreach ($region_terms as $rt) {
    $region_ids[] = (int) $rt->term_id;
  }
}

$resort_terms = [];
if (!empty($region_ids)) {
  $resort_terms = get_terms([
    'taxonomy' => 'resort',
    'hide_empty' => false,
    'meta_query' => [
      ['key' => 'resort_region', 'value' => $region_ids, 'compare' => 'IN'],
    ],
    'orderby' => 'name',
    'order' => 'ASC',
  ]);
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
      <div class="coutry-page__wrap">

        <aside class="coutry-page__aside">
          <?php get_template_part('template-parts/pages/country/child-pages-menu'); ?>
        </aside>

        <div class="page-country__content">

          <div class="country-excursions"
               data-excursions-filter
               data-country-id="<?= (int) $country_id; ?>">

            <div class="country-excursions__head">
              <div class="country-excursions__head-left">
                <h1 class="h1 country-excursions__title">
                  <?= esc_html($excursions_h1); ?>
                </h1>

                <div class="country-excursions__counter" data-excursions-count>
                  Найдено экскурсий: <?= (int) $excursions_query->found_posts; ?>
                </div>
              </div>

              <div class="country-excursions__head-right">
                <!-- Сортировка -->
                <div class="country-excursions__sort js-dropdown">
                  <button type="button" class="js-dropdown-trigger country-excursions__sort-trigger">
                    <span class="country-excursions__sort-text">По цене (возрастание)</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                      <path d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                            stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                  <div class="js-dropdown-panel country-excursions__sort-panel">
                    <div class="country-excursions__sort-options">
                      <button type="button" class="country-excursions__sort-option is-active" data-value="price_asc">По цене (возрастание)</button>
                      <button type="button" class="country-excursions__sort-option" data-value="price_desc">По цене (убывание)</button>
                      <button type="button" class="country-excursions__sort-option" data-value="title_asc">По названию (А-Я)</button>
                      <button type="button" class="country-excursions__sort-option" data-value="title_desc">По названию (Я-А)</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <form class="country-excursions__filters" data-excursions-form>
              <div class="country-excursions__filters-row">

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Регион</div>
                  <select class="tours-filter__select" name="region" data-choice="single">
                    <option value="">Все регионы</option>
                    <?php if (!is_wp_error($region_terms) && !empty($region_terms)): ?>
                      <?php foreach ($region_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Курорты</div>
                  <select class="tours-filter__select" name="resort" data-choice="single">
                    <option value="">Все курорты</option>
                    <?php if (!is_wp_error($resort_terms) && !empty($resort_terms)): ?>
                      <?php foreach ($resort_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Тип экскурсии</div>
                  <select class="tours-filter__select" name="excursion_type" data-choice="single">
                    <option value="">Все типы</option>
                    <?php if (!is_wp_error($type_terms) && !empty($type_terms)): ?>
                      <?php foreach ($type_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Язык гида</div>
                  <select class="tours-filter__select" name="excursion_language" data-choice="single">
                    <option value="">Все языки</option>
                    <?php if (!is_wp_error($language_terms) && !empty($language_terms)): ?>
                      <?php foreach ($language_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

              </div>
            </form>

            <div class="country-excursions__toolbar">
              <label class="ui-checkbox country-excursions__currency-toggle">
                <input type="checkbox"
                       class="ui-checkbox__input js-education-show-original-currency"
                       name="show_original_currency"
                       value="1">
                <span class="ui-checkbox__mark"></span>
                <span class="ui-checkbox__text">Стоимость в валюте</span>
              </label>

              <button type="button" class="country-excursions__reset is-hidden" data-excursions-reset>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Сбросить фильтры
              </button>
            </div>

            <div class="country-excursions__list" data-excursions-list>
              <?php if ($excursions_query->have_posts()): ?>
                <?php while ($excursions_query->have_posts()):
                  $excursions_query->the_post(); ?>
                  <?php get_template_part('template-parts/excursion/card-row', null, ['post_id' => get_the_ID()]); ?>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="country-excursions__empty">
                  Пока нет экскурсий для этой страны.
                </div>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </div>

            <div class="country-excursions__pagination news-pagination" data-excursions-pagination>
              <?php if ($excursions_query->max_num_pages > 1): ?>
                <?php
                echo paginate_links([
                  'total'   => $excursions_query->max_num_pages,
                  'current' => $paged,
                  'prev_text' => '&larr; Назад',
                  'next_text' => 'Вперед &rarr;',
                  'mid_size' => 2,
                ]);
                ?>
              <?php endif; ?>
            </div>

          </div>

        </div>
      </div>
    </div>
  </section>

</main>

<?php
get_template_part('template-parts/excursion/booking-modal');
get_footer();
?>
