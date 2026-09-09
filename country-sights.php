<?php
/**
 * Каталог достопримечательностей страны — /country/{slug}/dostoprimechatelnosti/
 *
 * Шаблон загружается роутером в single-country.php после установки глобальной
 * $country_sights_data ({country: WP_Post, country_slug: string}).
 *
 * Фильтры — обычные GET-параметры (region, resort, sight_type), без AJAX:
 * записей на страну немного, а ссылки с фильтром остаются шарящимися.
 * Карта — js/modules/sights-map.js, показывается только если у записей есть координаты.
 */

global $country_sights_data;

$country = $country_sights_data['country'] ?? null;
$country_slug = $country_sights_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;
$country_title = $country ? (string) $country->post_title : '';

/* H1 в предложном падеже («Достопримечательности Великобритании» → родительный) */
$country_genitive = ($country_id && function_exists('bsi_country_genitive_title'))
  ? bsi_country_genitive_title($country_id)
  : '';

$sights_h1 = $country_genitive !== ''
  ? 'Достопримечательности ' . $country_genitive
  : ($country_title !== '' ? 'Достопримечательности: ' . $country_title : 'Достопримечательности');

$base_url = home_url('/country/' . $country_slug . '/dostoprimechatelnosti/');

$active_region = isset($_GET['region']) ? (int) $_GET['region'] : 0;
$active_resort = isset($_GET['resort']) ? (int) $_GET['resort'] : 0;
$active_type = isset($_GET['sight_type']) ? (int) $_GET['sight_type'] : 0;
$has_filters = ($active_region > 0 || $active_resort > 0 || $active_type > 0);

$paged = max(1, (int) get_query_var('paged'));
$per_page = 24;

/* Все достопримечательности страны — база и для фильтров, и для карты */
$all_ids = [];
if ($country_id > 0) {
  $all_ids = get_posts([
    'post_type' => 'sight',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'orderby' => 'title',
    'order' => 'ASC',
    'no_found_rows' => true,
    'meta_query' => [
      ['key' => 'sight_country', 'value' => $country_id, 'compare' => '='],
    ],
  ]);
}

$region_terms = !empty($all_ids) ? wp_get_object_terms($all_ids, 'region', ['orderby' => 'name']) : [];
$resort_terms = !empty($all_ids) ? wp_get_object_terms($all_ids, 'resort', ['orderby' => 'name']) : [];
$type_terms = !empty($all_ids) ? wp_get_object_terms($all_ids, 'sight_type', ['orderby' => 'name']) : [];

$tax_query = [];
if ($active_region > 0) {
  $tax_query[] = ['taxonomy' => 'region', 'field' => 'term_id', 'terms' => [$active_region]];
}
if ($active_resort > 0) {
  $tax_query[] = ['taxonomy' => 'resort', 'field' => 'term_id', 'terms' => [$active_resort]];
}
if ($active_type > 0) {
  $tax_query[] = ['taxonomy' => 'sight_type', 'field' => 'term_id', 'terms' => [$active_type]];
}
if (count($tax_query) > 1) {
  $tax_query['relation'] = 'AND';
}

$query_args = [
  'post_type' => 'sight',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'orderby' => 'title',
  'order' => 'ASC',
  'post__in' => !empty($all_ids) ? $all_ids : [0],
];
if (!empty($tax_query)) {
  $query_args['tax_query'] = $tax_query;
}

$sights_query = new WP_Query($query_args);

/* Точки для карты — все отфильтрованные записи, а не только текущая страница */
$map_points = [];
$map_icons = [];
if (!empty($all_ids) && function_exists('bsi_get_sight_coordinates')) {
  $map_ids = $all_ids;

  if (!empty($tax_query)) {
    $map_ids = get_posts([
      'post_type' => 'sight',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'fields' => 'ids',
      'no_found_rows' => true,
      'post__in' => $all_ids,
      'tax_query' => $tax_query,
    ]);
  }

  foreach ($map_ids as $map_id) {
    $coords = bsi_get_sight_coordinates((int) $map_id);
    if ($coords === null) {
      continue;
    }

    /* Иконка маркера — по типу достопримечательности. */
    $icon = 'map-pin';
    $point_types = get_the_terms((int) $map_id, 'sight_type');
    if (!is_wp_error($point_types) && !empty($point_types)) {
      $icon = bsi_sight_type_icon((int) $point_types[0]->term_id);
    }
    $map_icons[$icon] = true;

    $map_points[] = [
      'lat' => $coords['lat'],
      'lng' => $coords['lng'],
      'title' => get_the_title((int) $map_id),
      'url' => get_permalink((int) $map_id),
      'icon' => $icon,
      'image' => (string) get_the_post_thumbnail_url((int) $map_id, 'medium'),
    ];
  }
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
          <div class="country-sights">

            <div class="country-sights-head">
              <h1 class="h1 country-sights-title"><?= esc_html($sights_h1); ?></h1>
            </div>

            <?php if (!empty($map_points)): ?>
              <?php
              /* Разметка иконок отдаётся отдельным словарём, а не в каждой точке:
                 один и тот же SVG на 70+ маркеров раздул бы атрибут в разы. */
              $icons_markup = [];
              foreach (array_keys($map_icons) as $icon_slug) {
                $inner = bsi_sight_icon_inner((string) $icon_slug);
                if ($inner !== '') {
                  $icons_markup[$icon_slug] = $inner;
                }
              }
              ?>
              <div class="country-sights-map"
                   data-sights-map
                   data-icons="<?= esc_attr(wp_json_encode($icons_markup)); ?>"
                   data-points="<?= esc_attr(wp_json_encode($map_points)); ?>"></div>
            <?php endif; ?>

            <?php if (!empty($region_terms) || !empty($type_terms)): ?>
              <form class="country-sights-filters" method="get" action="<?= esc_url($base_url); ?>" data-sights-filters>
                <div class="country-sights-filters-fields">

                <?php if (!is_wp_error($region_terms) && !empty($region_terms)): ?>
                  <div class="tours-filter__field">
                    <div class="tours-filter__label">Регион</div>
                    <select class="tours-filter__select" name="region">
                      <option value="">Все регионы</option>
                      <?php foreach ($region_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>" <?= selected($active_region, (int) $t->term_id, false); ?>>
                          <?= esc_html($t->name); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php endif; ?>

                <?php if (!is_wp_error($resort_terms) && !empty($resort_terms)): ?>
                  <div class="tours-filter__field">
                    <div class="tours-filter__label">Город</div>
                    <select class="tours-filter__select" name="resort">
                      <option value="">Все города</option>
                      <?php foreach ($resort_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>" <?= selected($active_resort, (int) $t->term_id, false); ?>>
                          <?= esc_html($t->name); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php endif; ?>

                <?php if (!is_wp_error($type_terms) && !empty($type_terms)): ?>
                  <div class="tours-filter__field">
                    <div class="tours-filter__label">Тип</div>
                    <select class="tours-filter__select" name="sight_type">
                      <option value="">Все типы</option>
                      <?php foreach ($type_terms as $t): ?>
                        <option value="<?= (int) $t->term_id; ?>" <?= selected($active_type, (int) $t->term_id, false); ?>>
                          <?= esc_html($t->name); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                <?php endif; ?>

                  <div class="country-sights-filters-actions">
                    <button type="submit" class="btn btn-black sm country-sights-submit">Показать</button>
                  </div>
                </div>

                <div class="country-sights-filters-bottom">
                  <div class="country-sights-chips" data-sights-chips hidden></div>
                  <?php if ($has_filters): ?>
                    <a class="country-sights-reset" href="<?= esc_url($base_url); ?>">Сбросить всё</a>
                  <?php endif; ?>
                </div>
              </form>
            <?php endif; ?>

            <?php if ($sights_query->have_posts()): ?>
              <div class="country-sights-list">
                <?php while ($sights_query->have_posts()):
                  $sights_query->the_post(); ?>
                  <?php get_template_part('template-parts/sight/card', null, ['post_id' => get_the_ID()]); ?>
                <?php endwhile; ?>
              </div>
            <?php else: ?>
              <div class="country-sights-empty">
                <?= $has_filters
                  ? 'По выбранным фильтрам ничего не нашлось.'
                  : 'Для этой страны достопримечательности пока не добавлены.'; ?>
              </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>

            <?php
            bsi_pagination([
              /* %_% + format: первая страница остаётся базовым URL, без /page/1/ */
              'base' => trailingslashit($base_url) . '%_%',
              'format' => 'page/%#%/',
              'total' => $sights_query->max_num_pages,
              'current' => $paged,
              'add_args' => array_filter([
                'region' => $active_region ?: null,
                'resort' => $active_resort ?: null,
                'sight_type' => $active_type ?: null,
              ]),
            ], ['class' => 'country-sights-pagination']);
            ?>

          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>
