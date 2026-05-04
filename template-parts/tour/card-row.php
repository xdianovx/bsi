<?php
/**
 * Template part: Tour card (row)
 * Usage:
 *   get_template_part('template-parts/tour/card-row', null, ['post_id' => $tour_id]);
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id)
  return;

// ACF
$country_id = function_exists('bsi_get_tour_primary_country_id') ? bsi_get_tour_primary_country_id((int) $post_id) : 0;
$tour_gallery = function_exists('get_field') ? (array) get_field('tour_gallery', $post_id) : [];
$duration = function_exists('get_field') ? trim((string) get_field('tour_duration', $post_id)) : '';
$route = function_exists('get_field') ? trim((string) get_field('tour_route', $post_id)) : '';
$booking_url = function_exists('get_field') ? trim((string) get_field('tour_booking_url', $post_id)) : '';
$checkin_dates_raw = function_exists('get_field') ? trim((string) get_field('tour_checkin_dates', $post_id)) : '';

// Парсим даты (разделены запятыми)
$checkin_dates = [];
if (!empty($checkin_dates_raw)) {
  $checkin_dates = array_map('trim', explode(',', $checkin_dates_raw));
  $checkin_dates = array_filter($checkin_dates);
}

// Taxonomies
$types = get_the_terms($post_id, 'tour_type');
$regions = get_the_terms($post_id, 'region');
$resorts = get_the_terms($post_id, 'resort');

// ✅ Включено в тур (имя таксы как в CPT: tour_include)
$included = [];
if (taxonomy_exists('tour_include')) {
  $included = get_the_terms($post_id, 'tour_include');
  if (is_wp_error($included) || empty($included))
    $included = [];
}

// URLs / text
$link = get_permalink($post_id);
$title = get_the_title($post_id);
$excerpt = get_the_excerpt($post_id);

// Image: featured -> first gallery
$img = get_the_post_thumbnail_url($post_id, 'large');
if (!$img && !empty($tour_gallery)) {
  $first = $tour_gallery[0] ?? null;
  if (is_array($first)) {
    if (!empty($first['sizes']['large']))
      $img = $first['sizes']['large'];
    elseif (!empty($first['url']))
      $img = $first['url'];
  }
}

// Country line: primary + остальные через запятую; флаги — все страны тура.
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

$types_list = [];
if (!empty($types) && !is_wp_error($types)) {
  $types_list = $types;
}
?>

<article class="tour-card-row">

  <?php if (!empty($types_list)): ?>

  <?php endif; ?>

  <div class="tour-card-row__poster">
    <div class="tour-card-row__tags">
      <?php foreach ($types_list as $t): ?>
        <span class="tour-card-row__tag">
          <?= esc_html($t->name); ?>
        </span>
      <?php endforeach; ?>
    </div>
    <a href="<?= esc_url($link); ?>" class="tour-card-row__poster-link">
      <?php if ($img): ?>
        <img class="tour-card-row__img" src="<?= esc_url($img); ?>" alt="<?= esc_attr($title); ?>" loading="lazy">
      <?php else: ?>
        <div class="tour-card-row__img-placeholder"></div>
      <?php endif; ?>
    </a>
  </div>

  <div class="tour-card-row__content">

    <div class="tour-card-row__top">

      <?php if (!empty($checkin_dates)): ?>
        <div class="tour-card-row__dates">
          <div class="tour-card-row__dates-list">
            <?php
              $display_count = 3;
              $displayed = array_slice($checkin_dates, 0, $display_count);
              $remaining = count($checkin_dates) - $display_count;

              foreach ($displayed as $date) {
                echo '<span class="tour-card-row__date">' . esc_html($date) . '</span>';
              }

              if ($remaining > 0) {
                echo '<span class="tour-card-row__date-more">еще ' . (int) $remaining . '</span>';
              }
            ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($tour_row_flag_rows) || $country_location_text !== ''): ?>
        <div class="tour-card-row__location">
          <?php if (!empty($tour_row_flag_rows)): ?>
            <span class="tour-card-row__flags">
              <?php foreach ($tour_row_flag_rows as $fr): ?>
                <span class="tour-card-row__flag">
                  <img src="<?= esc_url($fr['url']); ?>" alt="<?= esc_attr($fr['alt']); ?>" width="18" height="18" loading="eager" decoding="async">
                </span>
              <?php endforeach; ?>
            </span>
          <?php endif; ?>
          <?php if ($country_location_text !== ''): ?>
            <span class="tour-card-row__country-name"><?= esc_html($country_location_text); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <h3 class="tour-card-row__title"><a href="<?= esc_url($link); ?>"><?= esc_html($title); ?></a></h3>

      <?php if ($duration): ?>
        <div class="tour-card-row__duration">
          <span class="tour-card-row__duration-label">
            <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/tour/cal.svg'); ?>" alt="">
          </span>
          <span class="tour-card-row__duration-value numfont"><?= esc_html($duration); ?></span>
        </div>
      <?php endif; ?>

    </div>

    <?php if ($route): ?>
      <div class="tour-card-row__route numfont">
        <span class="tour-card-row__route-label">
          <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/tour/route.svg'); ?>" alt="">
        </span>
        <span class="tour-card-row__route-value"><?= esc_html($route); ?></span>
      </div>
    <?php endif; ?>




  </div>

  <div class="tour-card-row__actions">
    <?php if (!empty($included)): ?>
      <p class="tour-card-row__actions_title">Включено:</p>
      <div class="tour-card-row__included">
        <?php foreach ($included as $t): ?>
          <?php
          // ✅ ACF поле у термина: tour_include_icon
          $icon = function_exists('get_field') ? get_field('tour_include_icon', 'term_' . $t->term_id) : null;
          $icon_url = (is_array($icon) && !empty($icon['url'])) ? $icon['url'] : '';
          ?>
          <span class="tour-tag">
            <?php if ($icon_url): ?>
              <img class="tour-tag__icon" src="<?= esc_url($icon_url); ?>" alt="" loading="lazy">
            <?php endif; ?>
            <span class="tour-tag__text"><?= esc_html($t->name); ?></span>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="tour-card-row__btns">
      <a class="tour-card-row__more sm btn btn-gray" href="<?= esc_url($link); ?>">Подробнее</a>

      <?php if ($booking_url): ?>
        <?php
          $cached_price = class_exists('PriceLoaderService') ? PriceLoaderService::getCachedTourPrice($post_id) : null;
          $price_text = $cached_price ? $cached_price['price_formatted'] . ' ₽ / чел' : '';
          $is_price_loaded = !empty($price_text);
        ?>
        <a class="tour-card-row__book sm btn btn-accent" href="<?= esc_url($booking_url); ?>" target="_blank"
          rel="nofollow noopener" data-tour-price data-tour-id="<?= esc_attr($post_id); ?>"<?= $is_price_loaded ? ' data-price-loaded' : ''; ?>><?= $is_price_loaded ? esc_html($price_text) : 'Загрузка...'; ?></a>
      <?php endif; ?>
    </div>
  </div>

</article>