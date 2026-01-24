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
        <?php if (get_the_content()): ?>
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
          <select class="education-filter__select" name="language" data-choice="single">
            <option value="">Все языки</option>
            <?php if (!is_wp_error($language_terms) && !empty($language_terms)): ?>
              <?php foreach ($language_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Программа</div>
          <select class="education-filter__select" name="program" data-choice="single">
            <option value="">Показать все</option>
            <?php if (!is_wp_error($program_terms) && !empty($program_terms)): ?>
              <?php foreach ($program_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Тип обучения</div>
          <select class="education-filter__select" name="type" data-choice="single">
            <option value="">Показать все</option>
            <?php if (!is_wp_error($type_terms) && !empty($type_terms)): ?>
              <?php foreach ($type_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Размещение</div>
          <select class="education-filter__select" name="accommodation" data-choice="single">
            <option value="">Показать все</option>
            <?php if (!is_wp_error($accommodation_terms) && !empty($accommodation_terms)): ?>
              <?php foreach ($accommodation_terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <div class="education-filter__field">
          <div class="education-filter__label">Возраст</div>
          <select class="education-filter__select" name="age" data-choice="single">
            <option value="">Показать все</option>
            <?php for ($age = 5; $age <= 25; $age++): ?>
              <option value="<?php echo esc_attr($age); ?>"><?php echo esc_html($age . ' лет'); ?></option>
            <?php endfor; ?>
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

        <button type="button" class="education-page__reset-btn js-education-reset" style="display: none;">
          Сбросить фильтры
        </button>
      </div>

      <div class="education-page__controls-right">
        <div class="education-page__sort js-dropdown">
          <button type="button" class="js-dropdown-trigger education-page__sort-trigger">
            <span class="education-page__sort-text">По названию (А-Я)</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path
                d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
          <div class="js-dropdown-panel education-page__sort-panel">
            <div class="education-page__sort-options">
              <button type="button" class="education-page__sort-option" data-value="title_asc">По названию
                (А-Я)</button>
              <button type="button" class="education-page__sort-option" data-value="title_desc">По названию
                (Я-А)</button>
              <button type="button" class="education-page__sort-option" data-value="price_asc">По цене
                (возрастание)</button>
              <button type="button" class="education-page__sort-option" data-value="price_desc">По цене
                (убывание)</button>
            </div>
          </div>
        </div>


      </div>
    </div>

    <div class="education-page__list js-education-list">
      <?php if ($initial_query->have_posts()): ?>
        <?php
        $items = [];
        while ($initial_query->have_posts()):
          $initial_query->the_post();
          $education_id = (int) get_the_ID();

          $country_id = 0;
          if (function_exists('get_field')) {
            $country_val = get_field('education_country', $education_id);
            if ($country_val instanceof WP_Post) {
              $country_id = (int) $country_val->ID;
            } elseif (is_array($country_val)) {
              $country_id = (int) reset($country_val);
            } else {
              $country_id = (int) $country_val;
            }
          }

          $country_title = $country_id ? (string) get_the_title($country_id) : '';
          $country_slug = $country_id ? (string) get_post_field('post_name', $country_id) : '';

          $flag_url = '';
          if ($country_id && function_exists('get_field')) {
            $flag_field = get_field('flag', $country_id);
            if ($flag_field) {
              if (is_array($flag_field) && !empty($flag_field['url'])) {
                $flag_url = (string) $flag_field['url'];
              } elseif (is_string($flag_field)) {
                $flag_url = (string) $flag_field;
              }
            }
          }

          $resort_title = '';
          if (function_exists('get_field')) {
            $resort_field = get_field('education_resort', $education_id);
            if ($resort_field) {
              if ($resort_field instanceof WP_Term) {
                $resort_title = (string) $resort_field->name;
              } elseif (is_array($resort_field)) {
                $first_item = reset($resort_field);
                if ($first_item instanceof WP_Term) {
                  $resort_title = (string) $first_item->name;
                } else {
                  $resort_id = (int) $first_item;
                  $resort_term = get_term($resort_id, 'resort');
                  if ($resort_term && !is_wp_error($resort_term)) {
                    $resort_title = (string) $resort_term->name;
                  }
                }
              } else {
                $resort_id = (int) $resort_field;
                $resort_term = get_term($resort_id, 'resort');
                if ($resort_term && !is_wp_error($resort_term)) {
                  $resort_title = (string) $resort_term->name;
                }
              }
            }
          }

          $image_url = '';
          $thumb = get_the_post_thumbnail_url($education_id, 'large');
          if ($thumb) {
            $image_url = (string) $thumb;
          } else {
            $gallery = function_exists('get_field') ? get_field('education_gallery', $education_id) : [];
            $gallery = is_array($gallery) ? $gallery : [];
            if (!empty($gallery[0])) {
              if (is_array($gallery[0]) && !empty($gallery[0]['ID'])) {
                $first_id = (int) $gallery[0]['ID'];
              } elseif (is_numeric($gallery[0])) {
                $first_id = (int) $gallery[0];
              }
              if ($first_id) {
                $img = wp_get_attachment_image_url($first_id, 'large');
                if ($img) {
                  $image_url = (string) $img;
                }
              }
            }
          }

          $price = '';
          if (function_exists('get_field')) {
            $price_val = get_field('education_price', $education_id);

            if (is_string($price_val) && $price_val !== '') {
              $price = (string) $price_val;
            }

            $education_programs = get_field('education_programs', $education_id);
            $education_programs = is_array($education_programs) ? $education_programs : [];

            if (empty($price) && !empty($education_programs)) {
              $prices = [];
              foreach ($education_programs as $program) {
                $program_price = '';
                if (isset($program['program_price_per_week'])) {
                  $program_price = (string) $program['program_price_per_week'];
                } elseif (isset($program['price_per_week'])) {
                  $program_price = (string) $program['price_per_week'];
                }
                if ($program_price) {
                  preg_match('/[\d\s]+/', $program_price, $matches);
                  if (!empty($matches[0])) {
                    $prices[] = (int) str_replace(' ', '', $matches[0]);
                  }
                }
              }

              if (!empty($prices)) {
                $min_price_value = min($prices);
                $price = number_format($min_price_value, 0, ',', ' ') . ' ₽/неделя';
              }
            }

            if (!empty($price)) {
              $price = format_price_text($price);
            }
          }

          $languages = wp_get_post_terms($education_id, 'education_language', ['fields' => 'names']);
          $languages = is_wp_error($languages) ? [] : $languages;

          $programs = wp_get_post_terms($education_id, 'education_program', ['fields' => 'names']);
          $programs = is_wp_error($programs) ? [] : $programs;

          $booking_url = '';
          if (function_exists('get_field')) {
            $booking_url_val = get_field('education_booking_url', $education_id);
            if ($booking_url_val) {
              $booking_url = trim((string) $booking_url_val);
            }
          }

          $age_min = 0;
          $age_max = 0;
          $nearest_date = '';

          if (function_exists('get_field')) {
            $education_programs = get_field('education_programs', $education_id);
            $education_programs = is_array($education_programs) ? $education_programs : [];

            if (!empty($education_programs)) {
              $ages_min = [];
              $ages_max = [];
              $all_dates = [];

              foreach ($education_programs as $program) {
                $program_age_min = isset($program['program_age_min']) && $program['program_age_min'] !== '' ? (int) $program['program_age_min'] : 0;
                $program_age_max = isset($program['program_age_max']) && $program['program_age_max'] !== '' ? (int) $program['program_age_max'] : 0;

                if ($program_age_min > 0) {
                  $ages_min[] = $program_age_min;
                }
                if ($program_age_max > 0) {
                  $ages_max[] = $program_age_max;
                }

                $date_from = isset($program['program_checkin_date_from']) ? (string) $program['program_checkin_date_from'] : '';
                if ($date_from) {
                  $all_dates[] = $date_from;
                }
              }

              if (!empty($ages_min)) {
                $age_min = min($ages_min);
              }
              if (!empty($ages_max)) {
                $age_max = max($ages_max);
              }

              if (!empty($all_dates)) {
                $today = date('Y-m-d');
                $future_dates = array_filter($all_dates, function ($date) use ($today) {
                  return $date >= $today;
                });

                if (!empty($future_dates)) {
                  sort($future_dates);
                  $nearest_date = $future_dates[0];
                } elseif (!empty($all_dates)) {
                  sort($all_dates);
                  $nearest_date = $all_dates[0];
                }
              }
            }
          }

          $items[] = [
            'id' => $education_id,
            'url' => get_permalink($education_id),
            'image' => $image_url,
            'title' => get_the_title($education_id),
            'flag' => $flag_url,
            'country_title' => $country_title,
            'resort_title' => $resort_title,
            'price' => $price,
            'languages' => $languages,
            'programs' => $programs,
            'country_id' => $country_id,
            'country_slug' => $country_slug,
            'booking_url' => $booking_url,
            'age_min' => $age_min,
            'age_max' => $age_max,
            'nearest_date' => $nearest_date,
          ];
        endwhile;
        wp_reset_postdata();

        foreach ($items as $item):
          ?>
          <div class="education-page__item">
            <?php
            set_query_var('education', $item);
            get_template_part('template-parts/education/card');
            ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="education-page__empty">
          Школы не найдены.
        </div>
      <?php endif; ?>
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

