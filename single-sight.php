<?php
/**
 * Single CPT `sight` — /country/{country_slug}/dostoprimechatelnosti/{slug}/
 *
 * Структура по образцу single-excursion.php: заголовок + location-line + excerpt,
 * галерея, контент, сайдбар с адресом и кнопкой «Хочу сюда» (модалка
 * template-parts/sight/request-modal.php), карта-точка, блок «Рядом».
 *
 * Карта рендерится общим модулем js/modules/maps.js по data-lat/data-lng.
 */

$post_id = (int) get_the_ID();

$country_id = function_exists('bsi_get_sight_country_id') ? bsi_get_sight_country_id($post_id) : 0;
$country_title = $country_id ? get_the_title($country_id) : '';
$country_flag = ($country_id && function_exists('bsi_get_country_flag_url'))
  ? bsi_get_country_flag_url($country_id)
  : '';

$region_terms = get_the_terms($post_id, 'region');
$region_term = (!is_wp_error($region_terms) && !empty($region_terms)) ? $region_terms[0] : null;
$region_name = $region_term ? (string) $region_term->name : '';

$resort_terms = get_the_terms($post_id, 'resort');
$resort_term = (!is_wp_error($resort_terms) && !empty($resort_terms)) ? $resort_terms[0] : null;
$resort_name = $resort_term ? (string) $resort_term->name : '';

$type_terms = get_the_terms($post_id, 'sight_type');
$type_names = [];
if (!is_wp_error($type_terms) && !empty($type_terms)) {
  foreach ($type_terms as $tt) {
    $type_names[] = $tt->name;
  }
}

$address = function_exists('get_field') ? trim((string) get_field('sight_address', $post_id)) : '';
$short = function_exists('get_field') ? trim((string) get_field('sight_short', $post_id)) : '';
$coords = function_exists('bsi_get_sight_coordinates') ? bsi_get_sight_coordinates($post_id) : null;
$map_zoom = function_exists('get_field') ? (int) get_field('sight_map_zoom', $post_id) : 0;
if ($map_zoom < 1 || $map_zoom > 20) {
  $map_zoom = 15;
}

/* Галерея — нормализация как в single-excursion.php */
$gallery_raw = function_exists('get_field') ? get_field('sight_gallery', $post_id) : [];
$gallery_raw = is_array($gallery_raw) ? $gallery_raw : [];

$gallery = [];
foreach ($gallery_raw as $image) {
  if (is_array($image) && !empty($image['url'])) {
    $gallery[] = $image;
    continue;
  }

  $img_id = is_array($image) ? ($image['ID'] ?? 0) : (int) $image;
  if (!$img_id) {
    continue;
  }

  $img_url = wp_get_attachment_image_url($img_id, 'large');
  if (!$img_url) {
    continue;
  }
  $img_full_url = wp_get_attachment_image_url($img_id, 'full');
  $img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);

  $gallery[] = [
    'url' => $img_full_url ?: $img_url,
    'sizes' => ['large' => $img_url, 'full' => $img_full_url ?: $img_url],
    'alt' => $img_alt ?: get_the_title($post_id),
  ];
}

$sight_title = get_the_title($post_id);
$catalog_url = $country_id
  ? home_url('/country/' . get_post_field('post_name', $country_id) . '/dostoprimechatelnosti/')
  : '';

/* «Рядом» — сначала тот же город, добор по стране */
$nearby = [];
if ($country_id > 0) {
  $base_args = [
    'post_type' => 'sight',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'post__not_in' => [$post_id],
    'orderby' => 'rand',
    'no_found_rows' => true,
    'meta_query' => [
      ['key' => 'sight_country', 'value' => $country_id, 'compare' => '='],
    ],
  ];

  if ($resort_term) {
    $nearby = get_posts($base_args + [
      'tax_query' => [
        ['taxonomy' => 'resort', 'field' => 'term_id', 'terms' => [(int) $resort_term->term_id]],
      ],
    ]);
  }

  $missing = 3 - count($nearby);
  if ($missing > 0) {
    $exclude = array_map(static fn($p) => (int) $p->ID, $nearby);
    $exclude[] = $post_id;

    $nearby = array_merge($nearby, get_posts(array_merge($base_args, [
      'posts_per_page' => $missing,
      'post__not_in' => $exclude,
    ])));
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

  <section class="single-sight-title-section">
    <div class="container">
      <div class="single-education__title-wrap">
        <div class="title-rating__wrap">
          <h1 class="h1 single-education__title"><?= esc_html($sight_title); ?></h1>

          <?php
          get_template_part('template-parts/ui/location-line', null, [
            'country_id' => $country_id,
            'flag_url' => $country_flag,
            'parts' => [
              ['label' => $region_name],
              ['label' => $resort_name],
            ],
          ]);
          ?>

          <?php if ($short !== ''): ?>
            <div class="single-education__excerpt page-country__descr">
              <p><?= esc_html($short); ?></p>
            </div>
          <?php endif; ?>

          <?php if (!empty($type_names) || $address !== ''): ?>
            <div class="single-education__main-info">
              <?php if (!empty($type_names)): ?>
                <div class="single-education__info-item">
                  <span class="single-education__info-label">Тип:</span>
                  <span class="single-education__info-value"><?= esc_html(implode(', ', $type_names)); ?></span>
                </div>
              <?php endif; ?>

              <?php if (!empty($type_names) && $address !== ''): ?>
                <span class="single-education__info-separator"></span>
              <?php endif; ?>

              <?php if ($address !== ''): ?>
                <div class="single-education__info-item">
                  <span class="single-education__info-label">Адрес:</span>
                  <span class="single-education__info-value"><?= esc_html($address); ?></span>
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
            'id' => 'sight_' . $post_id,
          ]);
          ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="single-education__content-section">
    <div class="container">
      <div class="single-education__content__wrap single-sight-content-wrap">

        <div class="single-sight-main-column">
          <?php if (have_posts()): ?>
            <?php while (have_posts()):
              the_post(); ?>
              <?php if (get_the_content()): ?>
                <section class="single-sight-about">
                  <h2 class="h2">О месте</h2>
                  <div class="editor-content">
                    <?php the_content(); ?>
                  </div>
                </section>
              <?php endif; ?>
            <?php endwhile;
            rewind_posts(); ?>
          <?php endif; ?>

          <?php if ($coords !== null): ?>
            <section class="single-sight-map-section">
              <h2 class="h2">На карте</h2>
              <div class="single-sight-map hotel-map"
                   data-lat="<?= esc_attr((string) $coords['lat']); ?>"
                   data-lng="<?= esc_attr((string) $coords['lng']); ?>"
                   data-zoom="<?= esc_attr((string) $map_zoom); ?>"></div>
            </section>
          <?php endif; ?>
        </div>

        <aside class="single-education__aside-column">
          <div class="hotel-widget">
            <div class="single-education__school-title"><?= esc_html($sight_title); ?></div>

            <?php if ($address !== ''): ?>
              <div class="single-sight-address"><?= esc_html($address); ?></div>
            <?php endif; ?>

            <p class="single-sight-widget-lead">
              Подберём тур или экскурсию, где это место есть в программе.
            </p>

            <div class="single-education__booking">
              <button type="button" class="btn btn-accent single-education__booking-btn js-sight-request-btn"
                      data-sight-title="<?= esc_attr($sight_title); ?>"
                      data-sight-country="<?= esc_attr($country_title); ?>">
                Хочу сюда
              </button>
            </div>

            <?php if ($catalog_url !== ''): ?>
              <a class="single-sight-catalog-link" href="<?= esc_url($catalog_url); ?>">
                Все достопримечательности<?= $country_title !== '' ? ' — ' . esc_html($country_title) : ''; ?>
              </a>
            <?php endif; ?>
          </div>
        </aside>

      </div>
    </div>
  </section>

  <?php if (!empty($nearby)): ?>
    <section class="single-sight-nearby">
      <div class="container">
        <h2 class="h2">Рядом</h2>
        <div class="country-sights-list">
          <?php foreach ($nearby as $nearby_post): ?>
            <?php get_template_part('template-parts/sight/card', null, ['post_id' => (int) $nearby_post->ID]); ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

</main>

<?php
get_template_part('template-parts/sight/request-modal');
get_footer();
?>
