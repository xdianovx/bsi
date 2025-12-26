<?php
global $country_education_data;

$country = $country_education_data['country'] ?? null;
$country_slug = $country_education_data['country_slug'] ?? '';

if (!$country instanceof WP_Post) {
  $country = get_queried_object();
  $country_slug = $country ? $country->post_name : '';
}

$country_id = $country ? (int) $country->ID : 0;

$paged = max(1, (int) get_query_var('paged'));
$per_page = 12;

$education_query = new WP_Query([
  'post_type' => 'education',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'meta_query' => [
    [
      'key' => 'education_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ],
  'orderby' => 'title',
  'order' => 'ASC',
]);

$program_terms = get_terms([
  'taxonomy' => 'education_program',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
]);

$language_terms = get_terms([
  'taxonomy' => 'education_language',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
]);

$type_terms = get_terms([
  'taxonomy' => 'education_type',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
]);

$accommodation_terms = get_terms([
  'taxonomy' => 'education_accommodation_type',
  'hide_empty' => false,
  'orderby' => 'name',
  'order' => 'ASC',
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
          <div class="country-education"
               data-education-filter
               data-country-id="<?php echo (int) $country_id; ?>">
            <div class="country-education__head">
              <h1 class="h1 country-education__title">
                <?php echo esc_html($country ? $country->post_title : ''); ?> — обучение
              </h1>

              <div class="country-education__counter"
                   data-education-count>
                Найдено школ: <?php echo (int) $education_query->found_posts; ?>
              </div>
            </div>

            <form class="country-education__filters"
                  data-education-form>
              <div class="country-education__filters-row">
                <div class="education-filter__field">
                  <div class="education-filter__label">Программа</div>
                  <select class="education-filter__select"
                          name="program[]"
                          multiple
                          data-choice="multiple">
                    <?php if (!is_wp_error($program_terms) && !empty($program_terms)): ?>
                      <?php foreach ($program_terms as $t): ?>
                        <option value="<?php echo (int) $t->term_id; ?>"><?php echo esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="education-filter__field">
                  <div class="education-filter__label">Возраст</div>
                  <div class="education-filter__range">
                    <input type="number" class="education-filter__input" name="age_min" placeholder="От" min="0" step="1">
                    <span class="education-filter__range-separator">-</span>
                    <input type="number" class="education-filter__input" name="age_max" placeholder="До" min="0" step="1">
                  </div>
                </div>

                <div class="education-filter__field">
                  <div class="education-filter__label">Язык</div>
                  <select class="education-filter__select"
                          name="language[]"
                          multiple
                          data-choice="multiple">
                    <?php if (!is_wp_error($language_terms) && !empty($language_terms)): ?>
                      <?php foreach ($language_terms as $t): ?>
                        <option value="<?php echo (int) $t->term_id; ?>"><?php echo esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="education-filter__field">
                  <div class="education-filter__label">Длительность (недели)</div>
                  <div class="education-filter__range">
                    <input type="number" class="education-filter__input" name="duration_min" placeholder="От" min="1" step="1">
                    <span class="education-filter__range-separator">-</span>
                    <input type="number" class="education-filter__input" name="duration_max" placeholder="До" min="1" step="1">
                  </div>
                </div>

                <div class="education-filter__field">
                  <div class="education-filter__label">Тип обучения</div>
                  <select class="education-filter__select"
                          name="type[]"
                          multiple
                          data-choice="multiple">
                    <?php if (!is_wp_error($type_terms) && !empty($type_terms)): ?>
                      <?php foreach ($type_terms as $t): ?>
                        <option value="<?php echo (int) $t->term_id; ?>"><?php echo esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="education-filter__field">
                  <div class="education-filter__label">Размещение</div>
                  <select class="education-filter__select"
                          name="accommodation[]"
                          multiple
                          data-choice="multiple">
                    <?php if (!is_wp_error($accommodation_terms) && !empty($accommodation_terms)): ?>
                      <?php foreach ($accommodation_terms as $t): ?>
                        <option value="<?php echo (int) $t->term_id; ?>"><?php echo esc_html($t->name); ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>

                <div class="education-filter__field">
                  <div class="education-filter__label">Даты заезда</div>
                  <input type="text" class="education-filter__input education-filter__datepicker" name="date_range" placeholder="Выберите даты" readonly>
                  <input type="hidden" name="date_from" value="">
                  <input type="hidden" name="date_to" value="">
                </div>
              </div>
            </form>

            <div class="country-education__list"
                 data-education-list>
              <?php if ($education_query->have_posts()): ?>
                <?php while ($education_query->have_posts()):
                  $education_query->the_post(); ?>
                  <div class="country-education__item">
                    <?php get_template_part('template-parts/education/card'); ?>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="country-education__empty">
                  Пока нет школ для этой страны.
                </div>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

