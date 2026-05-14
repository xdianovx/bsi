<?php
/**
 * Карточка экскурсионного тура (CPT tour) — листинги / AJAX (макет Figma node 1667:956).
 *
 *   get_template_part('template-parts/tour/card-row', null, ['post_id' => $tour_id]);
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$country_id = function_exists('bsi_get_tour_primary_country_id') ? bsi_get_tour_primary_country_id((int) $post_id) : 0;
$tour_gallery = function_exists('get_field') ? (array) get_field('tour_gallery', $post_id) : [];
$checkin_dates_raw = function_exists('get_field') ? trim((string) get_field('tour_checkin_dates', $post_id)) : '';

$checkin_dates = [];
if ($checkin_dates_raw !== '') {
  $checkin_dates = array_values(array_filter(array_map('trim', explode(',', $checkin_dates_raw))));
}

$regions = get_the_terms($post_id, 'region');
$resorts = get_the_terms($post_id, 'resort');

$country_entries = function_exists('bsi_get_tour_country_entries') ? bsi_get_tour_country_entries((int) $post_id) : [];
$country_location_text = function_exists('bsi_format_tour_country_location_line')
  ? bsi_format_tour_country_location_line($country_id, $country_entries)
  : '';

$tour_row_flag_rows = [];
foreach ($country_entries as $entry) {
  if (!is_array($entry)) {
    continue;
  }
  $fu = isset($entry['flag_url']) ? trim((string) $entry['flag_url']) : '';
  if ($fu !== '') {
    $tour_row_flag_rows[] = [
      'url' => $fu,
      'alt' => isset($entry['title']) ? (string) $entry['title'] : '',
    ];
  }
}

$tour_card_date = '';
if (!empty($checkin_dates)) {
  $first = (string) reset($checkin_dates);
  if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $first)) {
    $tour_card_date = $first;
  } else {
    $ts = strtotime($first);
    $tour_card_date = $ts ? date_i18n('j.m.Y', $ts) : $first;
  }
}

$location_line = $country_location_text;
$geo_suffix = '';
if (!empty($resorts) && !is_wp_error($resorts)) {
  $geo_suffix = $resorts[0]->name;
} elseif (!empty($regions) && !is_wp_error($regions)) {
  $geo_suffix = $regions[0]->name;
}
if ($geo_suffix !== '') {
  if ($location_line === '') {
    $location_line = $geo_suffix;
  } elseif (function_exists('mb_stripos')
    ? mb_stripos($location_line, $geo_suffix, 0, 'UTF-8') === false
    : stripos($location_line, $geo_suffix) === false) {
    $location_line .= ', ' . $geo_suffix;
  }
}

$flag_url_row = !empty($tour_row_flag_rows[0]['url']) ? (string) $tour_row_flag_rows[0]['url'] : '';

$link = get_permalink($post_id);
$title = get_the_title($post_id);

$img = get_the_post_thumbnail_url($post_id, 'large');
if (!$img && !empty($tour_gallery)) {
  $first = $tour_gallery[0] ?? null;
  if (is_array($first)) {
    if (!empty($first['sizes']['large'])) {
      $img = $first['sizes']['large'];
    } elseif (!empty($first['url'])) {
      $img = $first['url'];
    }
  }
}

$excerpt_raw = preg_replace('/\s+/u', ' ', trim(wp_strip_all_tags(get_the_excerpt($post_id))));
?>

<article class="catalog-card catalog-card--tour">
  <a href="<?= esc_url($link); ?>" class="catalog-card__media">
    <?php if ($img): ?>
      <img class="catalog-card__img" src="<?= esc_url($img); ?>" alt="<?= esc_attr($title); ?>" loading="lazy">
    <?php else: ?>
      <span class="catalog-card__img-placeholder" role="img" aria-label=""></span>
    <?php endif; ?>
  </a>

  <div class="catalog-card__body">
    <?php if ($location_line !== '' || $tour_card_date !== ''): ?>
      <div class="catalog-card__row">
        <div class="catalog-card__row-left">
          <?php if ($flag_url_row !== ''): ?>
            <span class="catalog-card__flag">
              <img src="<?= esc_url($flag_url_row); ?>" alt="" width="22" height="22" loading="lazy">
            </span>
          <?php endif; ?>
          <?php if ($location_line !== ''): ?>
            <span class="catalog-card__location-text"><?= esc_html($location_line); ?></span>
          <?php endif; ?>
        </div>
        <?php if ($tour_card_date !== ''): ?>
          <span class="catalog-card__date numfont"><?= esc_html($tour_card_date); ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <h3 class="catalog-card__title">
      <a href="<?= esc_url($link); ?>"><?= esc_html($title); ?></a>
    </h3>

    <?php if ($excerpt_raw !== ''): ?>
      <p class="catalog-card__excerpt"><?= esc_html($excerpt_raw); ?></p>
    <?php endif; ?>
  </div>
</article>
