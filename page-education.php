<?php
/**
 * Template Name: Образование
 *
 * Шаблон страницы для каталога образовательных программ с фильтрами
 */

get_header();

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

// Получаем все школы для определения стран, у которых есть школы
$all_education = get_posts([
  'post_type' => 'education',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'fields' => 'ids',
]);

// Собираем уникальные ID стран из школ
$country_ids = [];

if (!empty($all_education) && function_exists('get_field')) {
  foreach ($all_education as $education_id) {
    $c = get_field('education_country', $education_id);
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

// Получаем только те страны, у которых есть школы
$countries = [];
if (!empty($country_ids)) {
  $countries = get_posts([
    'post_type' => 'country',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'post_parent' => 0,
    'orderby' => 'title',
    'order' => 'ASC',
    'post__in' => $country_ids,
  ]);
}

// Начальный запрос - показываем все школы (если страна не выбрана)
$initial_query = new WP_Query([
  'post_type' => 'education',
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'orderby' => 'title',
  'order' => 'ASC',
]);
?>

<?php if (function_exists('yoast_breadcrumb')): ?>
  <?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
<?php endif; ?>

<section class="education-page js-education-page">
  <div class="container">
    <div class="title-wrap">
      <div class="">
        <h1 class="h1"><?php the_title(); ?></h1>
        <?php if (has_excerpt()): ?>
          <div class="news-slider__title-description">
            <?php the_excerpt(); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (have_posts()): ?>
      <?php while (have_posts()):
        the_post(); ?>
        <?php if (bsi_is_page_empty()): ?>
          <div class="page-content">
            <div class="page-empty-message">
              Страница заполняется
            </div>
          </div>
        <?php elseif (get_the_content()): ?>
          <div class="page-content">
            <?php the_content(); ?>
          </div>
        <?php endif; ?>
      <?php endwhile; ?>
    <?php endif; ?>

    <form class="education-filter js-education-filter" data-education-form>
      <div class="education-filter__row">
        <div class="education-filter__field">
          <div class="education-filter__label">Страна</div>
          <select class="education-filter__select" name="country" data-choice="single">
            <option value="">Все страны</option>
            <?php if (!empty($countries)): ?>
              <?php foreach ($countries as $country): ?>
                <option value="<?php echo esc_attr($country->ID); ?>"><?php echo esc_html($country->post_title); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Язык</div>
          <select class="education-filter__select" name="language[]" multiple data-choice="multiple">
            <?php if (!is_wp_error($language_terms) && !empty($language_terms)): ?>
              <?php foreach ($language_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Программа</div>
          <select class="education-filter__select" name="program[]" multiple data-choice="multiple">
            <?php if (!is_wp_error($program_terms) && !empty($program_terms)): ?>
              <?php foreach ($program_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Тип обучения</div>
          <select class="education-filter__select" name="type[]" multiple data-choice="multiple">
            <?php if (!is_wp_error($type_terms) && !empty($type_terms)): ?>
              <?php foreach ($type_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Размещение</div>
          <select class="education-filter__select" name="accommodation[]" multiple data-choice="multiple">
            <?php if (!is_wp_error($accommodation_terms) && !empty($accommodation_terms)): ?>
              <?php foreach ($accommodation_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
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
          <div class="education-filter__label">Длительность (недели)</div>
          <div class="education-filter__range">
            <input type="number" class="education-filter__input" name="duration_min" placeholder="От" min="1" step="1">
            <span class="education-filter__range-separator">-</span>
            <input type="number" class="education-filter__input" name="duration_max" placeholder="До" min="1" step="1">
          </div>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Даты заезда</div>
          <input type="text" class="education-filter__input education-filter__datepicker" name="date_range"
            placeholder="Выберите даты" readonly>
          <input type="hidden" name="date_from" value="">
          <input type="hidden" name="date_to" value="">
        </div>
      </div>
    </form>

    <div class="education-page__controls">
      <div class="education-page__counter-wrap">
        <div class="education-page__counter js-education-counter">
          Найдено: <?php echo (int) $initial_query->found_posts; ?>
        </div>
      </div>

      <div class="education-page__controls-right">
        <div class="education-page__sort">
          <label class="education-page__sort-label">Сортировка:</label>
          <select class="education-page__sort-select js-education-sort" name="sort">
            <option value="title_asc">По названию (А-Я)</option>
            <option value="title_desc">По названию (Я-А)</option>
            <option value="price_asc">По цене (возрастание)</option>
            <option value="price_desc">По цене (убывание)</option>
          </select>
        </div>

        <button type="button" class="education-page__reset-btn js-education-reset" style="display: none;">
          Сбросить фильтры
        </button>
      </div>
    </div>

    <div class="education-page__list js-education-list">
      <?php if ($initial_query->have_posts()): ?>
        <?php while ($initial_query->have_posts()):
          $initial_query->the_post(); ?>
          <div class="education-page__item">
            <?php get_template_part('template-parts/education/card'); ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="education-page__empty">
          Школы не найдены.
        </div>
      <?php endif; ?>
      <?php wp_reset_postdata(); ?>
    </div>

    <div class="education-page__load-more js-education-load-more" style="display: none;">
      <button type="button" class="education-page__load-more-btn">
        Показать еще
      </button>
    </div>
  </div>
</section>

<?php
get_footer();

