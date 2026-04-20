<?php
global $country_tours_data;

$country = $country_tours_data['country'] ?? null;
$country_slug = $country_tours_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;
$country_title = $country ? (string) $country->post_title : '';

$country_title_prepositional = '';
if ($country_id && function_exists('get_field')) {
  $country_case_fields = [
    'country_title_prepositional',
    'country_name_prepositional',
    'title_prepositional',
    'name_prepositional',
  ];

  foreach ($country_case_fields as $field_name) {
    $value = trim((string) get_field($field_name, $country_id));
    if ($value !== '') {
      $country_title_prepositional = $value;
      break;
    }
  }
}

if ($country_title_prepositional === '' && $country_title !== '') {
  $country_prepositional_map = [
    'Австрия' => 'Австрию',
    'Азербайджан' => 'Азербайджан',
    'Албания' => 'Албанию',
    'Армения' => 'Армению',
    'Бахрейн' => 'Бахрейн',
    'Белоруссия' => 'Белоруссию',
    'Бельгия' => 'Бельгию',
    'Бруней' => 'Бруней',
    'Бутан' => 'Бутан',
    'Великобритания' => 'Великобританию',
    'Венгрия' => 'Венгрию',
    'Вьетнам' => 'Вьетнам',
    'Испания' => 'Испанию',
    'Италия' => 'Италию',
    'Греция' => 'Грецию',
    'Грузия' => 'Грузию',
    'Индия' => 'Индию',
    'Индонезия' => 'Индонезию',
    'Ирландия' => 'Ирландию',
    'Исландия' => 'Исландию',
    'Казахстан' => 'Казахстан',
    'Камбоджа' => 'Камбоджу',
    'Катар' => 'Катар',
    'Кипр' => 'Кипр',
    'Китай' => 'Китай',
    'Лаос' => 'Лаос',
    'Люксембург' => 'Люксембург',
    'Маврикий' => 'Маврикий',
    'Малайзия' => 'Малайзию',
    'Мальдивы' => 'Мальдивы',
    'Мьянма' => 'Мьянму',
    'Непал' => 'Непал',
    'Нидерланды' => 'Нидерланды',
    'ОАЭ' => 'ОАЭ',
    'Оман' => 'Оман',
    'Португалия' => 'Португалию',
    'Россия' => 'Россию',
    'Саудовская Аравия' => 'Саудовскую Аравию',
    'Сейшелы' => 'Сейшелы',
    'Сербия' => 'Сербию',
    'Сингапур' => 'Сингапур',
    'Словакия' => 'Словакию',
    'Словения' => 'Словению',
    'США' => 'США',
    'Таиланд' => 'Таиланд',
    'Турция' => 'Турцию',
    'Узбекистан' => 'Узбекистан',
    'Филиппины' => 'Филиппины',
    'Франция' => 'Францию',
    'Хорватия' => 'Хорватию',
    'Черногория' => 'Черногорию',
    'Чехия' => 'Чехию',
    'Швейцария' => 'Швейцарию',
    'Шри-Ланка' => 'Шри-Ланку',
    'Южная Корея' => 'Южную Корею',
    'Япония' => 'Японию',
    'Египет' => 'Египет',
  ];

  $country_title_prepositional = $country_prepositional_map[$country_title] ?? $country_title;
}

$country_tours_h1 = $country_title_prepositional !== ''
  ? 'Туры в ' . $country_title_prepositional
  : 'Туры';

$paged = max(1, (int) get_query_var('paged'));
$per_page = 12;

$tours_query = new WP_Query([
  'post_type' => 'tour',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'meta_query' => bsi_build_tour_country_meta_query((int) $country_id),
  'orderby' => 'title',
  'order' => 'ASC',
]);

/**
 * ID всех туров страны (используется для фильтрации регионов и типов)
 */
$country_tour_ids = get_posts([
  'post_type'      => 'tour',
  'post_status'    => 'publish',
  'posts_per_page' => -1,
  'fields'         => 'ids',
  'meta_query'     => bsi_build_tour_country_meta_query((int) $country_id),
]);

/**
 * Регионы: только те, в которых есть туры этой страны
 */
$region_terms = [];
if (!empty($country_tour_ids)) {
  $region_terms = wp_get_object_terms($country_tour_ids, 'region', [
    'orderby' => 'name',
    'order'   => 'ASC',
  ]);
}

/**
 * Типы туров: только те, что есть у туров этой страны (только родительские)
 */
$tour_type_terms = [];
if (!empty($country_tour_ids)) {
  $tour_type_terms = wp_get_object_terms($country_tour_ids, 'tour_type', [
    'orderby' => 'name',
    'order'   => 'ASC',
    'parent'  => 0,
  ]);
}

/**
 * Курорты страны: все регионы страны -> все курорты этих регионов
 */
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
      [
        'key' => 'resort_region',
        'value' => $region_ids,
        'compare' => 'IN',
      ],
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

          <div class="country-tours"
               data-tours-filter
               data-country-id="<?= (int) $country_id; ?>">

            <div class="country-tours__head">
              <div class="country-tours__head-left">
                <h1 class="h1 country-tours__title">
                  <?= esc_html($country_tours_h1); ?>
                </h1>

                <div class="country-tours__counter"
                     data-tours-count>
                  Найдено туров: <?= (int) $tours_query->found_posts; ?>
                </div>
              </div>

              <div class="country-tours__head-right">
                <!-- Датапикер -->
                <div class="country-tours__date-wrap">
                  <input type="text"
                         class="country-tours__date-input"
                         name="date_range"
                         placeholder="Выбрать дату"
                         readonly>
                  <input type="hidden"
                         name="date_from"
                         value="">
                  <input type="hidden"
                         name="date_to"
                         value="">
                </div>

                <!-- Сортировка -->
                <div class="country-tours__sort js-dropdown">
                  <button type="button"
                          class="js-dropdown-trigger country-tours__sort-trigger">
                    <span class="country-tours__sort-text">По цене (возрастание)</span>
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="20"
                         height="20"
                         viewBox="0 0 20 20"
                         fill="none">
                      <path d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                            stroke="black"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                  </button>
                  <div class="js-dropdown-panel country-tours__sort-panel">
                    <div class="country-tours__sort-options">
                      <button type="button"
                              class="country-tours__sort-option is-active"
                              data-value="price_asc">По цене (возрастание)</button>
                      <button type="button"
                              class="country-tours__sort-option"
                              data-value="price_desc">По цене (убывание)</button>
                      <button type="button"
                              class="country-tours__sort-option"
                              data-value="title_asc">По названию (А-Я)</button>
                      <button type="button"
                              class="country-tours__sort-option"
                              data-value="title_desc">По названию (Я-А)</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <form class="country-tours__filters"
                  data-tours-form>
              <div class="country-tours__filters-row">

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Регион</div>
                  <select class="tours-filter__select"
                          name="region"
                          data-choice="single">
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
                  <select class="tours-filter__select"
                          name="resort"
                          data-choice="single">
                    <option value="">Все курорты</option>
                    <?php if (!is_wp_error($resort_terms) && !empty($resort_terms)): ?>
                      <?php foreach ($resort_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="tours-filter__field">
                  <div class="tours-filter__label">Типы туров</div>
                  <select class="tours-filter__select"
                          name="tour_type"
                          data-choice="single">
                    <option value="">Все типы</option>
                    <?php if (!is_wp_error($tour_type_terms) && !empty($tour_type_terms)): ?>
                      <?php foreach ($tour_type_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>"><?= esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

              </div>
            </form>

            <div class="country-tours__list"
                 data-tours-list>
              <?php if ($tours_query->have_posts()): ?>
                <?php while ($tours_query->have_posts()):
                  $tours_query->the_post(); ?>
                  <?php get_template_part('template-parts/tour/card-row', null, ['post_id' => get_the_ID()]); ?>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="country-tours__empty">
                  Пока нет туров для этой страны.
                </div>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </div>

            <div class="country-tours__pagination news-pagination" data-tours-pagination>
              <?php if ($tours_query->max_num_pages > 1): ?>
                <?php
                echo paginate_links([
                  'total'   => $tours_query->max_num_pages,
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

<?php get_footer(); ?>