<?php
$post_id = get_the_ID();
$gallery_raw = function_exists('get_field') ? get_field('education_gallery', $post_id) : [];
$gallery_raw = is_array($gallery_raw) ? $gallery_raw : [];


$gallery = [];
if (!empty($gallery_raw)) {
  foreach ($gallery_raw as $image) {

    if (is_array($image) && !empty($image['url'])) {
      $gallery[] = $image;
      continue;
    }

    $img_id = is_array($image) ? ($image['ID'] ?? 0) : (int) $image;
    if (!$img_id)
      continue;

    $img_url = wp_get_attachment_image_url($img_id, 'large');
    if (!$img_url)
      continue;

    $img_full_url = wp_get_attachment_image_url($img_id, 'full');
    $img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);

    $gallery[] = [
      'url' => $img_full_url ?: $img_url,
      'sizes' => [
        'large' => $img_url,
        'full' => $img_full_url ?: $img_url,
      ],
      'alt' => $img_alt ?: get_the_title($post_id),
    ];
  }
}

$country_id = 0;
if (function_exists('get_field')) {
  $c = get_field('education_country', $post_id);
  if ($c instanceof WP_Post) {
    $country_id = (int) $c->ID;
  } elseif (is_array($c)) {
    $country_id = (int) reset($c);
  } else {
    $country_id = (int) $c;
  }
}

$country_title = '';
$country_permalink = '';
$country_flag = '';

if ($country_id) {
  $country_title = get_the_title($country_id);
  $country_permalink = get_permalink($country_id);
  $country_flag_field = function_exists('get_field') ? get_field('flag', $country_id) : '';
  if ($country_flag_field) {
    if (is_array($country_flag_field) && !empty($country_flag_field['url'])) {
      $country_flag = (string) $country_flag_field['url'];
    } elseif (is_string($country_flag_field)) {
      $country_flag = (string) $country_flag_field;
    }
  }
}

// Получаем курорт
$resort_id = 0;
$resort_name = '';
$resort_permalink = '';
$resort_term = null;

// Сначала пробуем получить через ACF поле
if (function_exists('get_field')) {
  $resort_field = get_field('education_resort', $post_id);
  if ($resort_field) {
    // Если это объект WP_Term
    if ($resort_field instanceof WP_Term) {
      $resort_term = $resort_field;
      $resort_id = (int) $resort_term->term_id;
    }
    // Если это массив ID или объектов
    elseif (is_array($resort_field)) {
      $first_item = reset($resort_field);
      if ($first_item instanceof WP_Term) {
        $resort_term = $first_item;
        $resort_id = (int) $resort_term->term_id;
      } else {
        $resort_id = (int) $first_item;
      }
    }
    // Если это просто ID
    else {
      $resort_id = (int) $resort_field;
    }
  }
}

// Если не получили через ACF, пробуем через таксономию
if (!$resort_term && !$resort_id) {
  $resort_terms = get_the_terms($post_id, 'resort');
  if (!empty($resort_terms) && !is_wp_error($resort_terms)) {
    $resort_term = $resort_terms[0];
    $resort_id = (int) $resort_term->term_id;
  }
}

// Если есть ID, но нет объекта термина, получаем его
if ($resort_id && !$resort_term) {
  $resort_term = get_term($resort_id, 'resort');
  if (is_wp_error($resort_term)) {
    $resort_term = null;
  }
}

if ($resort_term && !is_wp_error($resort_term)) {
  $resort_name = $resort_term->name;
  $resort_permalink = get_term_link($resort_term);
  if (is_wp_error($resort_permalink)) {
    $resort_permalink = '';
  }
}

// Получаем регион через курорт (ACF поле resort_region у курорта)
$region_id = 0;
$region_name = '';
$region_permalink = '';
$region_term = null;

if ($resort_term && function_exists('get_field')) {
  $region_id_field = get_field('resort_region', 'term_' . $resort_term->term_id);
  if ($region_id_field) {
    if (is_array($region_id_field)) {
      $region_id = (int) reset($region_id_field);
    } else {
      $region_id = (int) $region_id_field;
    }
  }
}

if ($region_id > 0) {
  $region_term = get_term($region_id, 'region');
  if ($region_term && !is_wp_error($region_term)) {
    $region_name = $region_term->name;
    $region_permalink = get_term_link($region_term);
    if (is_wp_error($region_permalink)) {
      $region_permalink = '';
    }
  }
}

// Получаем поля для стоимости (теперь это WYSIWYG поля)
$price_included = function_exists('get_field') ? get_field('education_price_included', $post_id) : '';
$price_included = trim((string) $price_included);

$price_extra = function_exists('get_field') ? get_field('education_price_extra', $post_id) : '';
$price_extra = trim((string) $price_extra);

// Получаем общие значения
$education_age = trim((string) (function_exists('get_field') ? get_field('education_age', $post_id) : ''));
$education_class_size = trim((string) (function_exists('get_field') ? get_field('education_class_size', $post_id) : ''));
$education_lesson_duration = trim((string) (function_exists('get_field') ? get_field('education_lesson_duration', $post_id) : ''));
$education_course_duration = trim((string) (function_exists('get_field') ? get_field('education_course_duration', $post_id) : ''));

$address = trim((string) (function_exists('get_field') ? get_field('education_address', $post_id) : ''));
$phone = trim((string) (function_exists('get_field') ? get_field('education_phone', $post_id) : ''));
$website = trim((string) (function_exists('get_field') ? get_field('education_website', $post_id) : ''));
$price = trim((string) (function_exists('get_field') ? get_field('education_price', $post_id) : ''));

$map_coords = function_exists('get_field') ? bsi_parse_map_coordinates(get_field('education_map_coordinates', $post_id)) : null;
if ($map_coords) {
  $map_lat = (string) $map_coords['lat'];
  $map_lng = (string) $map_coords['lng'];
} else {
  $map_lat = function_exists('get_field') ? get_field('education_map_lat', $post_id) : '';
  $map_lng = function_exists('get_field') ? get_field('education_map_lng', $post_id) : '';
}
$map_zoom = function_exists('get_field') ? get_field('education_map_zoom', $post_id) : 14;

$programs = function_exists('get_field') ? get_field('education_programs', $post_id) : [];
$programs = is_array($programs) ? $programs : [];

// Собираем доступные даты из программ
$available_dates = [];
$nearest_date = '';
if (!empty($programs)) {
  $today = new DateTime();
  $today->setTime(0, 0, 0);
  $nearest_date_obj = null;

  foreach ($programs as $program) {
    $date_from = isset($program['program_checkin_date_from']) ? trim($program['program_checkin_date_from']) : '';
    $date_to = isset($program['program_checkin_date_to']) ? trim($program['program_checkin_date_to']) : '';

    if ($date_from) {
      $date_from_obj = DateTime::createFromFormat('Y-m-d', $date_from);
      if ($date_from_obj) {
        $date_from_obj->setTime(0, 0, 0);

        // Добавляем дату начала в массив доступных дат
        $available_dates[] = $date_from;

        // Если есть диапазон, добавляем все даты в диапазоне
        if ($date_to) {
          $date_to_obj = DateTime::createFromFormat('Y-m-d', $date_to);
          if ($date_to_obj) {
            $date_to_obj->setTime(0, 0, 0);
            $current_date = clone $date_from_obj;

            while ($current_date <= $date_to_obj) {
              $date_str = $current_date->format('Y-m-d');
              if (!in_array($date_str, $available_dates)) {
                $available_dates[] = $date_str;
              }
              $current_date->modify('+1 day');
            }

            // Проверяем ближайшую дату
            if ($date_from_obj >= $today) {
              if (!$nearest_date_obj || $date_from_obj < $nearest_date_obj) {
                $nearest_date_obj = clone $date_from_obj;
                $nearest_date = $date_from;
              }
            }
          }
        } else {
          // Если только одна дата, проверяем её как ближайшую
          if ($date_from_obj >= $today) {
            if (!$nearest_date_obj || $date_from_obj < $nearest_date_obj) {
              $nearest_date_obj = clone $date_from_obj;
              $nearest_date = $date_from;
            }
          }
        }
      }
    }
  }

  // Сортируем даты и фильтруем пустые значения
  $available_dates = array_filter($available_dates, function ($date) {
    return !empty($date) && strlen(trim($date)) > 0;
  });
  sort($available_dates);
  $available_dates = array_values(array_unique($available_dates));
}

$booking_url = function_exists('get_field') ? get_field('education_booking_url', $post_id) : '';

if (function_exists('get_field')) {
  $price_val = get_field('education_price', $post_id);

  if (is_string($price_val) && $price_val !== '') {
    $price = (string) $price_val;
  }

  if (empty($price) && !empty($programs)) {
    $prices = [];
    foreach ($programs as $program) {
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

get_header();
?>

<main>
  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section class="">
    <div class="container">
      <div class="single-education__title-wrap">
        <div class="title-rating__wrap">
          <h1 class="h1 single-education__title"><?php the_title(); ?></h1>

          <?php if ($country_title || $region_name || $resort_name): ?>
            <div class="single-education__country single-hotel__address">
              <?php if ($country_flag): ?>
                <img src="<?php echo esc_url($country_flag); ?>" alt="<?php echo esc_attr($country_title); ?>"
                  class="single-education__flag">
              <?php endif; ?>
              <div class="single-education__location-text">
                <?php if ($country_title): ?>
                  <?php if ($country_permalink): ?>
                    <a href="<?php echo esc_url($country_permalink); ?>"
                      class="single-education__country-link"><?php echo esc_html($country_title); ?><?php if ($region_name || $resort_name): ?>,<?php endif; ?></a>
                  <?php else: ?>
                    <span
                      class="single-education__country-text"><?php echo esc_html($country_title); ?><?php if ($region_name || $resort_name): ?>,<?php endif; ?></span>
                  <?php endif; ?>
                <?php endif; ?>
                <?php if ($region_name): ?>
                  <span
                    class="single-education__region-text"><?php echo esc_html($region_name); ?><?php if ($resort_name): ?>,<?php endif; ?></span>
                <?php endif; ?>
                <?php if ($resort_name): ?>
                  <span class="single-education__resort-text"><?php echo esc_html($resort_name); ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (has_excerpt()): ?>
            <div class="single-education__excerpt page-country__descr">
              <?php the_excerpt(); ?>
            </div>
          <?php endif; ?>

          <?php if ($education_age || $education_class_size || $education_lesson_duration || $education_course_duration): ?>
            <div class="single-education__main-info">
              <?php if ($education_age): ?>
                <div class="single-education__info-item">
                  <span class="single-education__info-label">Возраст:</span>
                  <span class="single-education__info-value"><?php echo esc_html($education_age); ?></span>
                </div>
                <?php if ($education_class_size || $education_lesson_duration || $education_course_duration): ?>
                  <span class="single-education__info-separator"></span>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($education_class_size): ?>
                <div class="single-education__info-item">
                  <span class="single-education__info-label">В классе:</span>
                  <span class="single-education__info-value"><?php echo esc_html($education_class_size); ?></span>
                </div>
                <?php if ($education_lesson_duration || $education_course_duration): ?>
                  <span class="single-education__info-separator"></span>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($education_lesson_duration): ?>
                <div class="single-education__info-item">
                  <span class="single-education__info-label">Длительность урока:</span>
                  <span class="single-education__info-value"><?php echo esc_html($education_lesson_duration); ?></span>
                </div>
                <?php if ($education_course_duration): ?>
                  <span class="single-education__info-separator"></span>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($education_course_duration): ?>
                <div class="single-education__info-item">
                  <span class="single-education__info-label">Продолжительность обучения:</span>
                  <span class="single-education__info-value"><?php echo esc_html($education_course_duration); ?></span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($gallery)): ?>
    <section class="single-education__gallery-section">
      <div class="container">
        <div class="country-page__gallery">
          <?php
          get_template_part('template-parts/sections/gallery', null, [
            'gallery' => $gallery,
            'id' => 'education_' . $post_id,
          ]);
          ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="single-education__content-section" data-education-id="<?php echo esc_attr($post_id); ?>">
    <div class="container">
      <div class="single-education__content__wrap">
        <?php if (!empty($programs)): ?>
          <div class="single-education__programs-column">
            <div class="single-education__programs js-education-programs"
              data-education-id="<?php echo esc_attr($post_id); ?>"
              data-available-dates="<?php echo esc_attr(wp_json_encode($available_dates)); ?>"
              data-nearest-date="<?php echo esc_attr($nearest_date); ?>">


              <div class="single-education__programs-title__wrap">
                <h2 class="h2 single-education__programs-title">Учебные программы</h2>

                <button type="button" class="education-programs-filter__reset-btn js-education-programs-reset">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                      stroke-linejoin="round" />
                  </svg>
                  Сбросить фильтры
                </button>
                <div class="single-education__programs-sort js-dropdown">
                  <button type="button" class="js-dropdown-trigger single-education__programs-sort-trigger">
                    <span class="single-education__programs-sort-text">Цена: по возрастанию</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                      <path
                        d="M2.5 13.3333L5.83333 16.6667M5.83333 16.6667L9.16667 13.3333M5.83333 16.6667V3.33333M9.16667 3.33333H17.5M9.16667 6.66666H15M9.16667 9.99999H12.5"
                        stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                  <div class="js-dropdown-panel single-education__programs-sort-panel">
                    <button type="button" class="single-education__programs-sort-option" data-value="price_asc">Цена: по
                      возрастанию</button>
                    <button type="button" class="single-education__programs-sort-option" data-value="price_desc">Цена: по
                      убыванию</button>
                    <button type="button" class="single-education__programs-sort-option" data-value="age_asc">Возраст: по
                      возрастанию</button>
                    <button type="button" class="single-education__programs-sort-option" data-value="age_desc">Возраст: по
                      убыванию</button>
                  </div>
                </div>
              </div>

              <div class="single-education__programs-filters js-education-programs-filters">
                <div class="education-programs-filter__field">
                  <div class="education-programs-filter__label">Возраст</div>
                  <select name="program_age" class="js-education-age-select education-programs-filter__select">
                    <option value="">Показать все</option>
                  </select>
                </div>

                <div class="education-programs-filter__field">
                  <div class="education-programs-filter__label">Продолжительность</div>
                  <select name="program_duration" class="js-education-duration-select education-programs-filter__select">
                    <option value="">Показать все</option>
                  </select>
                </div>

                <div class="education-programs-filter__field">
                  <div class="education-programs-filter__label">Язык</div>
                  <select name="program_language" class="js-education-language-select education-programs-filter__select">
                    <option value="">Показать все</option>
                    <?php
                    $languages = get_terms([
                      'taxonomy' => 'education_language',
                      'hide_empty' => false,
                    ]);
                    if (!empty($languages) && !is_wp_error($languages)) {
                      foreach ($languages as $lang) {
                        echo '<option value="' . esc_attr($lang->term_id) . '">' . esc_html($lang->name) . '</option>';
                      }
                    }
                    ?>
                  </select>
                </div>

                <div class="education-programs-filter__field">
                  <div class="education-programs-filter__label">Дата заезда</div>
                  <input type="text" class="education-programs-filter__input js-education-program-date"
                    name="program_date" placeholder="Выберите даты" readonly>
                </div>
              </div>



              <div class="single-education__programs-list js-education-programs-list">
                <?php foreach ($programs as $index => $program): ?>
                  <?php
                  set_query_var('program', $program);
                  set_query_var('program_index', $index);
                  set_query_var('booking_url', $booking_url);
                  set_query_var('school_name', get_the_title());
                  get_template_part('template-parts/education/program-card');
                  ?>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <aside class="single-education__aside-column">
          <div class="hotel-widget">
            <div class="single-education__school-title">
              <?php the_title(); ?>
            </div>

            <?php if ($phone || $address || $website): ?>
              <div class="hotel-widget__contacts">
                <?php if ($phone): ?>
                  <div class="hotel-widget__phone hotel-widget__contacts-item">
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>">
                      <img src="<?php echo esc_url(get_template_directory_uri() . '/img/icons/hotel/call.svg'); ?>" alt="">
                      <span><?php echo esc_html($phone); ?></span>
                    </a>
                  </div>
                <?php endif; ?>

                <?php if ($address): ?>
                  <div class="hotel-widget__address hotel-widget__contacts-item">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/img/icons/hotel/home.svg'); ?>" alt="">
                    <span><?php echo esc_html($address); ?></span>
                  </div>
                <?php endif; ?>

                <?php if ($website): ?>
                  <div class="hotel-widget__site hotel-widget__contacts-item">
                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="nofollow noopener">
                      <img src="<?php echo esc_url(get_template_directory_uri() . '/img/icons/hotel/url.svg'); ?>" alt="">
                      <span>Сайт школы</span>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($price): ?>
              <div class="single-education__info">
                <div class="single-education__info-item">
                  <div class="single-education__info-value"><?php echo esc_html(format_price_with_from($price, true)); ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>



            <div class="single-education__booking">
              <?php if ($booking_url): ?>
                <a href="<?php echo esc_url($booking_url); ?>" class="btn btn-accent single-education__booking-btn"
                  target="_blank" rel="noopener noreferrer">
                  Забронировать
                </a>
              <?php else: ?>
                <button class="btn btn-accent single-education__booking-btn js-education-booking-btn"
                  data-education-id="<?php echo esc_attr($post_id); ?>">
                  Забронировать
                </button>
              <?php endif; ?>
            </div>

          </div>
          <?php if ($map_lat && $map_lng): ?>
            <div class="hotel-widget">
              <a href="#education-map" class="btn btn-black sm hotel-widget__btn-map">Смотреть на карте</a>
            </div>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </section>



  <?php if (have_posts()): ?>
    <?php while (have_posts()):
      the_post(); ?>
      <?php if (get_the_content()): ?>
        <section class="single-education__description-section">
          <div class="container">
            <div class="single-education__description editor-content">
              <?php the_content(); ?>
            </div>
          </div>
        </section>
      <?php endif; ?>
    <?php endwhile; ?>
  <?php endif; ?>



  <?php if (!empty($price_included) || !empty($price_extra)): ?>
    <section class="single-education__price-details-section">
      <div class="container">
        <div class="single-education__price-details">
          <?php if (!empty($price_included)): ?>
            <div class="single-education__price-included">
              <h3 class="single-education__price-title">В стоимость входит</h3>
              <div class="single-education__price-content">
                <?php echo wp_kses_post($price_included); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($price_extra)): ?>
            <div class="single-education__price-extra">
              <h3 class="single-education__price-title">Оплачивается дополнительно</h3>
              <div class="single-education__price-content">
                <?php echo wp_kses_post($price_extra); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($map_lat && $map_lng): ?>
    <?php
    $map_zoom_safe = max(1, min(17, (int) $map_zoom));
    $yandex_map_url = 'https://yandex.ru/maps/?ll=' . rawurlencode((string) $map_lng) . '%2C' . rawurlencode((string) $map_lat) . '&z=' . $map_zoom_safe . '&pt=' . rawurlencode((string) $map_lng) . ',' . rawurlencode((string) $map_lat);
    $marker_icon_url = get_template_directory_uri() . '/img/icons/hotel/home-map.svg';
    ?>
    <section class="single-education__map-section map-section" id="education-map">
      <div class="container">
        <h2 class="h2 map-section__title">Расположение</h2>
        <div class="hotel-map map-wrap" id="education-map-container" data-lat="<?php echo esc_attr($map_lat); ?>"
          data-lng="<?php echo esc_attr($map_lng); ?>" data-zoom="<?php echo esc_attr($map_zoom); ?>"
          data-marker-icon="<?php echo esc_url($marker_icon_url); ?>" style="width: 100%; height: 400px;"></div>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php
// Модальное окно бронирования программы
get_template_part('template-parts/education/program-booking-modal');

get_footer();

