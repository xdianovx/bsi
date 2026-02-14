<?php
/**
 * Template Name: Событийные туры
 */
get_header();

$paged = max(1, (int) get_query_var('paged'));
$per_page = 12;

// Начальный запрос событийных туров
$tours_query = new WP_Query([
  'post_type' => 'event',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'orderby' => 'title',
  'order' => 'ASC',
]);

// Получаем страны, у которых есть событийные туры
$event_tours_countries_query = new WP_Query([
  'post_type' => 'event',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'fields' => 'ids',
]);

$country_ids = [];
if ($event_tours_countries_query->have_posts()) {
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

// Получаем все регионы (будут фильтроваться по стране через AJAX)
$region_terms = get_terms([
  'taxonomy' => 'region',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
]);

// Получаем доступные типы туров, привязанные к event
$tour_type_terms = get_terms([
  'taxonomy' => 'tour_type',
  'hide_empty' => true,
  'object_ids' => $event_tours_countries_query->posts,
  'orderby' => 'name',
  'order' => 'ASC',
]);
?>

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
      <div class="">

        <div class="page-country__content">

          <div class="country-tours" data-event-tours-filter>

            <div class="country-tours__head">
              <h1 class="h1 country-tours__title">
                <?php the_title(); ?>
              </h1>

              <?php if (get_the_excerpt()): ?>
                <div class="page-country__descr">
                  <?php echo wp_kses_post(get_the_excerpt()); ?>
                </div>
              <?php endif; ?>

            </div>

            <form class="country-tours__filters" data-tours-form>
              <div class="country-tours__filters-row">

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
                  <div class="tours-filter__label">Куда (регион)</div>
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
                  <div class="tours-filter__label">Даты проведения</div>
                  <input type="text" class="tours-filter__input" name="departure_date" data-departure-date
                    placeholder="Выберите диапазон дат" readonly>
                </div>

              </div>
            </form>

            <div class="country-tours__controls">
              <div class="country-tours__counter" data-tours-count>
                Нашли туров:
                <?= (int) $tours_query->found_posts; ?>
              </div>

              <button type="button" class="country-tours__reset-btn js-tours-reset" style="display: none;">
                Сбросить фильтры
              </button>
            </div>


            <div class="country-tours__list" data-tours-list>
              <?php if ($tours_query->have_posts()): ?>
                <?php while ($tours_query->have_posts()):
                  $tours_query->the_post(); ?>
                  <?php get_template_part('template-parts/event/card-row', null, ['post_id' => get_the_ID()]); ?>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="country-tours__empty">
                  Пока нет событийных туров.
                </div>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </div>

          </div>

        </div>

      </div>
    </div>
  </section>

  <?php
  // Дополнительный контент страницы (если есть)
  if (get_the_content()): ?>
    <section>
      <div class="container">
        <div class="editor-content">
          <?php the_content(); ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

</main>

<?php get_footer(); ?>