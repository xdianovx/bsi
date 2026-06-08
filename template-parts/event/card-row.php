<?php
/**
 * Карточка событийного тура (CPT event) — каталог / страна / AJAX (макет Figma node 1667:973).
 *
 *   get_template_part('template-parts/event/card-row', null, ['post_id' => $event_id]);
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$country_raw = function_exists('get_field') ? get_field('tour_country', $post_id) : null;
$country_id = 0;
if ($country_raw instanceof WP_Post) {
  $country_id = (int) $country_raw->ID;
} elseif (is_array($country_raw)) {
  $country_id = (int) reset($country_raw);
} else {
  $country_id = (int) $country_raw;
}

$tour_gallery = function_exists('get_field') ? (array) get_field('tour_gallery', $post_id) : [];
$checkin_dates = function_exists('get_field') ? trim((string) get_field('tour_checkin_dates', $post_id)) : '';

$event_dates_rows = function_exists('get_field') ? get_field('event_dates', $post_id) : [];

$event_card_date = '';
$event_dates_more = 0;
if (!empty($event_dates_rows) && is_array($event_dates_rows)) {
  $ds = [];
  foreach ($event_dates_rows as $row) {
    if (!empty($row['date_value'])) {
      $ds[] = (string) $row['date_value'];
    }
  }
  $ds = array_values(array_unique($ds));
  sort($ds);
  if (!empty($ds[0])) {
    $event_card_date = date_i18n('j.m.Y', strtotime($ds[0]));
    $event_dates_more = max(0, count($ds) - 1);
  }
}
if ($event_card_date === '' && function_exists('get_field')) {
  $hero_d = get_field('event_hero_date', $post_id);
  if (is_string($hero_d) && $hero_d !== '') {
    $event_card_date = date_i18n('j.m.Y', strtotime($hero_d));
  }
}
if ($event_card_date === '' && $checkin_dates !== '') {
  $parts = array_map('trim', explode(',', $checkin_dates));
  $event_card_date = $parts[0] ?? '';
}

$resorts = get_the_terms($post_id, 'resort');

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

$country_title = $country_id ? get_the_title($country_id) : '';
$flag_url = ($country_id && function_exists('bsi_get_country_flag_url'))
  ? bsi_get_country_flag_url($country_id)
  : '';

$location_parts = [];
if ($country_title !== '') {
  $location_parts[] = $country_title;
}
if (!empty($resorts) && !is_wp_error($resorts)) {
  $location_parts[] = $resorts[0]->name;
}
$location_line = implode(', ', $location_parts);

$excerpt_raw = '';
if (function_exists('get_field')) {
  $about = get_field('event_about', $post_id);
  if (is_string($about) && trim($about) !== '') {
    $excerpt_raw = wp_strip_all_tags($about);
  }
}
if ($excerpt_raw === '') {
  $excerpt_raw = wp_strip_all_tags(get_the_excerpt($post_id));
}
$excerpt_raw = preg_replace('/\s+/u', ' ', trim($excerpt_raw));
?>

<article class="catalog-card catalog-card--event">
  <a href="<?= esc_url($link); ?>" class="catalog-card__media">
    <?php if ($img): ?>
      <img class="catalog-card__img" src="<?= esc_url($img); ?>" alt="<?= esc_attr($title); ?>" loading="lazy">
    <?php else: ?>
      <span class="catalog-card__img-placeholder" role="img" aria-label=""></span>
    <?php endif; ?>
  </a>

  <div class="catalog-card__body">
    <?php if ($location_line !== '' || $event_card_date !== ''): ?>
      <div class="catalog-card__row">
        <div class="catalog-card__row-left">
          <?php if ($flag_url !== ''): ?>
            <span class="catalog-card__flag">
              <img src="<?= esc_url($flag_url); ?>" alt="" width="22" height="22" loading="lazy">
            </span>
          <?php endif; ?>
          <?php if ($location_line !== ''): ?>
            <span class="catalog-card__location-text"><?= esc_html($location_line); ?></span>
          <?php endif; ?>
        </div>
        <?php if ($event_card_date !== ''): ?>
          <span class="catalog-card__date numfont"><?= esc_html($event_card_date); ?></span>
          <?php if ($event_dates_more > 0): ?>
            <span class="catalog-card__date-more">…и еще <span class="numfont"><?= (int) $event_dates_more; ?></span>
              <?= esc_html(function_exists('bsi_plural_ru') ? bsi_plural_ru($event_dates_more, 'дата', 'даты', 'дат') : 'дат'); ?></span>
          <?php endif; ?>
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
