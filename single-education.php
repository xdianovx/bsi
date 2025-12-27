<?php
$post_id = get_the_ID();
$gallery_raw = function_exists('get_field') ? get_field('education_gallery', $post_id) : [];
$gallery_raw = is_array($gallery_raw) ? $gallery_raw : [];

// Преобразуем галерею в формат для template-parts/sections/gallery.php
$gallery = [];
if (!empty($gallery_raw)) {
  foreach ($gallery_raw as $image) {
    // Если элемент уже в правильном формате ACF (с 'url' и 'alt')
    if (is_array($image) && !empty($image['url'])) {
      $gallery[] = $image;
      continue;
    }

    // Иначе преобразуем из ID
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

$address = trim((string) (function_exists('get_field') ? get_field('education_address', $post_id) : ''));
$phone = trim((string) (function_exists('get_field') ? get_field('education_phone', $post_id) : ''));
$website = trim((string) (function_exists('get_field') ? get_field('education_website', $post_id) : ''));
$price = trim((string) (function_exists('get_field') ? get_field('education_price', $post_id) : ''));

$map_lat = function_exists('get_field') ? get_field('education_map_lat', $post_id) : '';
$map_lng = function_exists('get_field') ? get_field('education_map_lng', $post_id) : '';
$map_zoom = function_exists('get_field') ? get_field('education_map_zoom', $post_id) : 14;

$programs = function_exists('get_field') ? get_field('education_programs', $post_id) : [];
$programs = is_array($programs) ? $programs : [];

$booking_button_text = function_exists('get_field') ? get_field('education_booking_button_text', $post_id) : 'Запронировать';
$booking_url = function_exists('get_field') ? get_field('education_booking_url', $post_id) : '';

if (empty($booking_button_text)) {
  $booking_button_text = 'Запронировать';
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
          <?php if ($country_title): ?>
            <div class="single-education__country">
              <?php if ($country_flag): ?>
                <img src="<?php echo esc_url($country_flag); ?>" alt="<?php echo esc_attr($country_title); ?>"
                  class="single-education__flag">
              <?php endif; ?>
              <?php if ($country_permalink): ?>
                <a href="<?php echo esc_url($country_permalink); ?>"
                  class="single-education__country-link"><?php echo esc_html($country_title); ?></a>
              <?php else: ?>
                <span class="single-education__country-text"><?php echo esc_html($country_title); ?></span>
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

  <section class="single-education__content" data-education-id="<?php echo esc_attr($post_id); ?>">
    <div class="container">
      <div class="single-hotel__content__wrap">
        <div class="hotel-content editor-content">
          <?php if (have_posts()): ?>
            <?php while (have_posts()):
              the_post(); ?>
              <div class="single-education__description">
                <?php the_content(); ?>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>

          <?php if (!empty($programs)): ?>
            <div class="single-education__programs js-education-programs">
              <h2 class="h2 single-education__programs-title">Учебные программы</h2>

              <div class="single-education__programs-filters js-education-programs-filters">
                <div class="education-programs-filter__field">
                  <div class="education-programs-filter__label">Возраст</div>
                  <div class="education-programs-filter__range">
                    <input type="number" class="education-programs-filter__input" name="program_age_min" placeholder="От"
                      min="0" step="1">
                    <span class="education-programs-filter__range-separator">-</span>
                    <input type="number" class="education-programs-filter__input" name="program_age_max" placeholder="До"
                      min="0" step="1">
                  </div>
                </div>

                <div class="education-programs-filter__field">
                  <div class="education-programs-filter__label">Длительность (недели)</div>
                  <div class="education-programs-filter__range">
                    <input type="number" class="education-programs-filter__input" name="program_duration_min"
                      placeholder="От" min="1" step="1">
                    <span class="education-programs-filter__range-separator">-</span>
                    <input type="number" class="education-programs-filter__input" name="program_duration_max"
                      placeholder="До" min="1" step="1">
                  </div>
                </div>

                <div class="education-programs-filter__field">
                  <div class="education-programs-filter__label">Дата заселения</div>
                  <input type="date" class="education-programs-filter__input" name="program_date">
                </div>
              </div>

              <div class="single-education__programs-list js-education-programs-list">
                <?php foreach ($programs as $program): ?>
                  <div class="single-education__program-item">
                    <?php
                    set_query_var('program', $program);
                    get_template_part('template-parts/education/program-card');
                    ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <aside class="hotel-aside">
          <div class="hotel-widget">
            <div class="single-education__info">
              <?php if ($price): ?>
                <div class="single-education__info-item">
                  <div class="single-education__info-label">Стоимость</div>
                  <div class="single-education__info-value"><?php echo esc_html($price); ?></div>
                </div>
              <?php endif; ?>

              <?php if ($address): ?>
                <div class="single-education__info-item">
                  <div class="single-education__info-label">Адрес</div>
                  <div class="single-education__info-value"><?php echo esc_html($address); ?></div>
                </div>
              <?php endif; ?>

              <?php if ($phone): ?>
                <div class="single-education__info-item">
                  <div class="single-education__info-label">Телефон</div>
                  <div class="single-education__info-value">
                    <a
                      href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($website): ?>
                <div class="single-education__info-item">
                  <div class="single-education__info-label">Сайт</div>
                  <div class="single-education__info-value">
                    <a href="<?php echo esc_url($website); ?>" target="_blank"
                      rel="noopener noreferrer"><?php echo esc_html($website); ?></a>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($map_lat && $map_lng): ?>
              <div class="single-education__map" id="education-map-container"></div>
              <script>
                async function initEducationMap() {
                  if (typeof ymaps3 === 'undefined') {
                    setTimeout(initEducationMap, 100);
                    return;
                  }
                  await ymaps3.ready;
                  const {YMap, YMapDefaultSchemeLayer, YMapMarker} = ymaps3;
                  const map = new YMap(
                    document.getElementById('education-map-container'),
                    {
                      location: {
                        center: [<?php echo esc_js($map_lng); ?>, <?php echo esc_js($map_lat); ?>],
                        zoom: <?php echo esc_js($map_zoom); ?>
                      }
                    }
                  );
                  map.addChild(new YMapDefaultSchemeLayer());
                  const marker = new YMapMarker({
                    coordinates: [<?php echo esc_js($map_lng); ?>, <?php echo esc_js($map_lat); ?>],
                    mapFollowsOnClick: false,
                  });
                  map.addChild(marker);
                }
                initEducationMap();
              </script>
            <?php endif; ?>

            <?php if ($booking_button_text): ?>
              <div class="single-education__booking">
                <?php if ($booking_url): ?>
                  <a href="<?php echo esc_url($booking_url); ?>" class="btn btn-primary single-education__booking-btn"
                    target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html($booking_button_text); ?>
                  </a>
                <?php else: ?>
                  <button class="btn btn-primary single-education__booking-btn js-education-booking-btn"
                    data-education-id="<?php echo esc_attr($post_id); ?>">
                    <?php echo esc_html($booking_button_text); ?>
                  </button>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();

