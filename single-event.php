<?php
/**
 * Одиночная запись CPT `event` (событийный тур). Не шаблон обычного тура (`tour` → single-tour.php).
 */
$post_id = get_the_ID();

$country_id = function_exists('get_field') ? get_field('tour_country', $post_id) : 0;
if ($country_id instanceof WP_Post) {
  $country_id = $country_id->ID;
}
if (is_array($country_id)) {
  $country_id = (int) reset($country_id);
}
$country_id = (int) $country_id;

$tour_booking_url = trim((string) (function_exists('get_field') ? get_field('tour_booking_url', get_the_ID()) : ''));
$event_booking_cta_lead = function_exists('get_field') ? trim((string) get_field('event_booking_cta_lead', $post_id)) : '';
if ($event_booking_cta_lead === '' && defined('BSI_EVENT_BOOKING_CTA_LEAD_DEFAULT')) {
  $event_booking_cta_lead = BSI_EVENT_BOOKING_CTA_LEAD_DEFAULT;
}

$region_terms = get_the_terms($post_id, 'region');
$region_term = (!empty($region_terms) && !is_wp_error($region_terms)) ? $region_terms[0] : null;

$resort_terms = get_the_terms($post_id, 'resort');
$resort_term = (!empty($resort_terms) && !is_wp_error($resort_terms)) ? $resort_terms[0] : null;

$tour_gallery = function_exists('get_field') ? get_field('tour_gallery', $post_id) : [];
$tour_duration = function_exists('get_field') ? trim((string) get_field('tour_duration', $post_id)) : '';
$tour_program = function_exists('get_field') ? get_field('tour_program', $post_id) : [];
$tour_included = function_exists('get_field') ? (string) get_field('tour_included', $post_id) : '';
$tour_not_inc = function_exists('get_field') ? (string) get_field('tour_not_included', $post_id) : '';
$tour_extra = function_exists('get_field') ? (string) get_field('tour_extra', $post_id) : '';
$tour_price_from = function_exists('get_field') ? trim((string) get_field('price_from', $post_id)) : '';
$event_tickets = function_exists('get_field') ? get_field('event_tickets', $post_id) : [];
$event_venue = function_exists('get_field') ? trim((string) get_field('event_venue', $post_id)) : '';
$event_time = function_exists('get_field') ? trim((string) get_field('event_time', $post_id)) : '';
$tour_nights = function_exists('get_field') ? (int) get_field('tour_nights', $post_id) : 0;
$venue_scheme = function_exists('get_field') ? get_field('venue_scheme', $post_id) : null;
$venue_scheme_legend = function_exists('get_field') ? get_field('venue_scheme_legend', $post_id) : [];
$event_faq = function_exists('get_field') ? get_field('event_faq', $post_id) : [];
$event_about = function_exists('get_field') ? (string) get_field('event_about', $post_id) : '';
$event_additional = function_exists('get_field') ? (string) get_field('event_additional', $post_id) : '';

$event_widget_phone_primary = function_exists('get_field') ? trim((string) get_field('event_widget_phone_primary', $post_id)) : '';
$event_widget_phone_secondary = function_exists('get_field') ? trim((string) get_field('event_widget_phone_secondary', $post_id)) : '';
if ($event_widget_phone_primary === '') {
  $event_widget_phone_primary = '8 (495) 785-55-35';
}
if ($event_widget_phone_secondary === '') {
  $event_widget_phone_secondary = '8 (800) 200-55-35 (из регионов)';
}
$event_widget_phone_primary_tel = preg_replace('/\D/u', '', $event_widget_phone_primary);
$event_widget_phone_secondary_tel = preg_replace('/\D/u', '', $event_widget_phone_secondary);
if ($event_widget_phone_primary_tel === '') {
  $event_widget_phone_primary_tel = '84957855535';
}
if ($event_widget_phone_secondary_tel === '') {
  $event_widget_phone_secondary_tel = '88002005535';
}

$include_terms = get_the_terms($post_id, 'tour_include');

$widget_country_title = '';
$widget_country_permalink = '';
$widget_country_flag = '';
if ($country_id) {
  $widget_country_title = get_the_title($country_id);
  $widget_country_permalink = get_permalink($country_id);
  $flag = function_exists('get_field') ? get_field('flag', $country_id) : '';
  $widget_country_flag = (is_array($flag) && !empty($flag['url'])) ? $flag['url'] : (string) $flag;
}

$venue_scheme_url = '';
$venue_scheme_alt = '';
if (is_array($venue_scheme) && !empty($venue_scheme['url'])) {
  $venue_scheme_url = (string) $venue_scheme['url'];
  $venue_scheme_alt = !empty($venue_scheme['alt']) ? (string) $venue_scheme['alt'] : 'Схема зала';
}

$event_dates_rows = function_exists('get_field') ? get_field('event_dates', $post_id) : [];

$related_events = [];
if ($country_id > 0 && function_exists('bsi_build_tour_country_meta_query')) {
  $country_tour_meta = bsi_build_tour_country_meta_query($country_id);
  if (!empty($country_tour_meta)) {
    $related_events = get_posts(bsi_query_args_append_schedule([
      'post_type' => 'event',
      'post_status' => 'publish',
      'posts_per_page' => 10,
      'post__not_in' => [$post_id],
      'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
      'no_found_rows' => true,
      'meta_query' => $country_tour_meta,
    ]));
  }
}
$fallback_rub_from_post = function_exists('bsi_extract_price_number')
  ? bsi_extract_price_number($tour_price_from)
  : null;

$hero_cover = function_exists('get_field') ? get_field('event_hero_cover', $post_id) : null;
$hero_url = '';
if (is_array($hero_cover) && !empty($hero_cover['ID'])) {
  $hero_url = (string) wp_get_attachment_image_url((int) $hero_cover['ID'], 'full');
} elseif (is_array($hero_cover) && !empty($hero_cover['url'])) {
  $hero_url = (string) $hero_cover['url'];
}
if ($hero_url === '') {
  $hero_url = get_the_post_thumbnail_url($post_id, 'full');
}
if (!$hero_url && !empty($tour_gallery) && is_array($tour_gallery)) {
  $first = $tour_gallery[0] ?? null;
  if (is_array($first)) {
    $hero_url = !empty($first['sizes']['large']) ? (string) $first['sizes']['large'] : (string) ($first['url'] ?? '');
  }
}

$hero_cover_mobile = function_exists('get_field') ? get_field('event_hero_cover_mobile', $post_id) : null;
$hero_url_mobile = '';
if (is_array($hero_cover_mobile) && !empty($hero_cover_mobile['ID'])) {
  $hero_url_mobile = (string) wp_get_attachment_image_url((int) $hero_cover_mobile['ID'], 'full');
} elseif (is_array($hero_cover_mobile) && !empty($hero_cover_mobile['url'])) {
  $hero_url_mobile = (string) $hero_cover_mobile['url'];
}

if ($hero_url === '' && $hero_url_mobile !== '') {
  $hero_url = $hero_url_mobile;
}

$hero_date_label = '';
$hero_concert_date = function_exists('get_field') ? get_field('event_hero_date', $post_id) : '';
if (is_string($hero_concert_date) && $hero_concert_date !== '') {
  $hero_ts = strtotime($hero_concert_date);
  if ($hero_ts) {
    $hero_date_label = date_i18n('j F Y', $hero_ts);
  }
}
if ($hero_date_label === '' && !empty($event_dates_rows) && is_array($event_dates_rows)) {
  $ds = [];
  foreach ($event_dates_rows as $row) {
    if (!empty($row['date_value'])) {
      $ds[] = (string) $row['date_value'];
    }
  }
  $ds = array_values(array_unique($ds));
  sort($ds);
  if (!empty($ds)) {
    if (count($ds) === 1) {
      $hero_date_label = date_i18n('j F Y', strtotime($ds[0]));
    } else {
      $hero_date_label = date_i18n('j F Y', strtotime($ds[0])) . ' — ' . date_i18n('j F Y', strtotime($ds[count($ds) - 1]));
    }
  }
}

$dates_section_rows = [];
if (!empty($event_dates_rows) && is_array($event_dates_rows)) {
  foreach ($event_dates_rows as $row) {
    if (empty($row['date_value'])) {
      continue;
    }
    $d = (string) $row['date_value'];
    $city = isset($row['date_city']) ? trim((string) $row['date_city']) : '';
    $venue_row = isset($row['date_venue']) ? trim((string) $row['date_venue']) : '';
    if ($venue_row === '') {
      $venue_row = $event_venue;
    }

    $currency = isset($row['date_row_price_currency']) ? strtoupper(trim((string) $row['date_row_price_currency'])) : 'RUB';
    if ($currency === '') {
      $currency = 'RUB';
    }
    $amount_raw = $row['date_row_price'] ?? null;
    $amount = ($amount_raw !== null && $amount_raw !== '') ? (float) $amount_raw : null;

    $price_rub = null;
    $price_original = null;
    $price_currency = null;

    if ($amount !== null && $amount > 0 && function_exists('bsi_education_convert_price_to_rub')) {
      $converted = bsi_education_convert_price_to_rub($amount, $currency);
      if ($converted !== null && $converted > 0) {
        $price_rub = (int) $converted;
        if ($currency !== 'RUB') {
          $price_original = $amount;
          $price_currency = $currency;
        }
      }
    }

    if ($price_rub === null && $fallback_rub_from_post !== null && $fallback_rub_from_post > 0) {
      $price_rub = (int) $fallback_rub_from_post;
    }

    $dates_section_rows[] = [
      'date' => $d,
      'city' => $city,
      'venue' => $venue_row,
      'price_rub' => $price_rub,
      'price_original' => $price_original,
      'price_currency' => $price_currency,
    ];
  }
}

$row_rubs_positive = [];
foreach ($dates_section_rows as $r_row) {
  if (isset($r_row['price_rub']) && (int) $r_row['price_rub'] > 0) {
    $row_rubs_positive[] = (int) $r_row['price_rub'];
  }
}
$price_from_amount = !empty($row_rubs_positive) ? min($row_rubs_positive) : $fallback_rub_from_post;

$event_accommodation_raw = function_exists('get_field') ? get_field('event_accommodation', $post_id) : [];
$accommodation_rows = [];
if (!empty($event_accommodation_raw) && is_array($event_accommodation_raw)) {
  foreach ($event_accommodation_raw as $acc_row) {
    $acc_name = isset($acc_row['accommodation_hotel_name']) ? trim((string) $acc_row['accommodation_hotel_name']) : '';
    if ($acc_name === '') {
      continue;
    }
    $acc_stars_raw = isset($acc_row['accommodation_stars']) ? (int) $acc_row['accommodation_stars'] : 0;
    $acc_stars = max(0, min(5, $acc_stars_raw));
    $acc_descr = isset($acc_row['accommodation_description']) ? trim((string) $acc_row['accommodation_description']) : '';
    $acc_amount_raw = $acc_row['accommodation_price'] ?? null;
    $acc_amount = ($acc_amount_raw !== null && $acc_amount_raw !== '') ? (float) $acc_amount_raw : null;
    $acc_cur = isset($acc_row['accommodation_price_currency']) ? strtoupper(trim((string) $acc_row['accommodation_price_currency'])) : 'RUB';
    if ($acc_cur === '') {
      $acc_cur = 'RUB';
    }

    $acc_price_rub = null;
    $acc_price_original = null;
    $acc_price_currency = null;
    if ($acc_amount !== null && $acc_amount > 0 && function_exists('bsi_education_convert_price_to_rub')) {
      $acc_converted = bsi_education_convert_price_to_rub($acc_amount, $acc_cur);
      if ($acc_converted !== null && $acc_converted > 0) {
        $acc_price_rub = (int) $acc_converted;
        if ($acc_cur !== 'RUB') {
          $acc_price_original = $acc_amount;
          $acc_price_currency = $acc_cur;
        }
      }
    }

    $accommodation_rows[] = [
      'name' => $acc_name,
      'stars' => $acc_stars,
      'descr' => $acc_descr,
      'price_rub' => $acc_price_rub,
      'price_original' => $acc_price_original,
      'price_currency' => $acc_price_currency,
    ];
  }
}

$event_price_original = null;
$event_price_currency = null;
if ($price_from_amount !== null && (int) $price_from_amount > 0 && !empty($dates_section_rows)) {
  $tied_rows = array_values(array_filter(
    $dates_section_rows,
    static function ($r) use ($price_from_amount) {
      return isset($r['price_rub']) && (int) $r['price_rub'] === (int) $price_from_amount;
    }
  ));
  if (!empty($tied_rows)) {
    usort(
      $tied_rows,
      static function ($a, $b) {
        $a_fc = !empty($a['price_currency']);
        $b_fc = !empty($b['price_currency']);
        if ($a_fc !== $b_fc) {
          return $b_fc <=> $a_fc;
        }
        return 0;
      }
    );
    $winner_row = $tied_rows[0];
    if (!empty($winner_row['price_original']) && !empty($winner_row['price_currency'])) {
      $event_price_original = (float) $winner_row['price_original'];
      $event_price_currency = (string) $winner_row['price_currency'];
    }
  }
}

if ($event_price_original === null && !empty($accommodation_rows)) {
  $acc_non_rub = array_values(array_filter(
    $accommodation_rows,
    static function ($r) {
      return !empty($r['price_currency']) && (float) ($r['price_original'] ?? 0) > 0 && (int) ($r['price_rub'] ?? 0) > 0;
    }
  ));
  if (!empty($acc_non_rub)) {
    usort(
      $acc_non_rub,
      static function ($a, $b) {
        return (int) $a['price_rub'] <=> (int) $b['price_rub'];
      }
    );
    $event_price_original = (float) $acc_non_rub[0]['price_original'];
    $event_price_currency = (string) $acc_non_rub[0]['price_currency'];
  }
}

$hero_type_terms = get_the_terms($post_id, BSI_EVENT_TOUR_TYPE_TAXONOMY);
if (is_wp_error($hero_type_terms)) {
  $hero_type_terms = [];
}

$hero_price_line = '';
if ($price_from_amount !== null && (int) $price_from_amount > 0) {
  $hero_price_line = 'от ' . number_format((int) $price_from_amount, 0, ',', ' ') . ' ₽';
} elseif ($tour_price_from !== '') {
  $hero_price_line = $tour_price_from;
} else {
  $hero_price_line = 'Запросить';
}

$hero_style = '';
if ($hero_url) {
  $hero_style = '--single-event-hero-bg:url(' . esc_url($hero_url) . ')';
  if ($hero_url_mobile !== '' && $hero_url_mobile !== $hero_url) {
    $hero_style .= ';--single-event-hero-bg-mobile:url(' . esc_url($hero_url_mobile) . ')';
  }
}

$event_title = get_the_title($post_id);

$excerpt = get_the_excerpt($post_id);
$show_quick = $tour_duration || $tour_nights || $resort_term;

get_header();
?>

<main class="site-main single-event-page">

  <!-- Hero Start -->
  <section class="single-event__hero<?= $hero_url ? '' : ' single-event__hero--no-poster'; ?>"
    aria-labelledby="event-hero-title" <?= $hero_style !== '' ? ' style="' . esc_attr($hero_style) . '"' : ''; ?>>
    <div class="single-event__hero-bg" aria-hidden="true"></div>

    <?php
    if (function_exists('yoast_breadcrumb')) {
      yoast_breadcrumb(
        '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
        '</p></div></div>'
      );
    }
    ?>

    <div class="container single-event__hero-inner">
      <div class="single-event__hero-tags">
        <?php if ($hero_date_label !== ''): ?>
          <span class="single-event__hero-tag"><?= esc_html($hero_date_label); ?></span>
        <?php endif; ?>
        <?php foreach ($hero_type_terms as $t): ?>
          <span class="single-event__hero-tag"><?= esc_html($t->name); ?></span>
        <?php endforeach; ?>
      </div>

      <h1 class="h1 single-event__hero-title" id="event-hero-title"><?= esc_html($event_title); ?></h1>

      <div class="single-event__hero-actions">

        <?php if ($tour_booking_url): ?>
          <a href="<?= esc_url($tour_booking_url); ?>" class="btn btn-accent single-event__hero-btn" target="_blank"
            rel="nofollow noopener">Забронировать</a>
        <?php else: ?>
          <button type="button" class="btn btn-accent single-event__hero-btn js-event-booking-btn"
            data-event-id="<?= esc_attr($post_id); ?>" data-event-title="<?= esc_attr($event_title); ?>"
            data-event-venue="<?= esc_attr($event_venue); ?>" data-event-time="<?= esc_attr($event_time); ?>"
            data-min-price="<?= esc_attr($price_from_amount ?? 0); ?>">Забронировать</button>
        <?php endif; ?>

        <?php if ($price_from_amount !== null && (int) $price_from_amount > 0): ?>
          <span class="single-event__hero-price numfont js-event-price"
            data-price-rub="<?= esc_attr((string) (int) $price_from_amount); ?>"
            <?php if ($event_price_original !== null && $event_price_original > 0 && $event_price_currency !== null && $event_price_currency !== ''): ?>
            data-price-original="<?= esc_attr((string) $event_price_original); ?>"
            data-price-currency="<?= esc_attr($event_price_currency); ?>"
            <?php endif; ?>
            data-has-from="true"><?= esc_html($hero_price_line); ?></span>
        <?php else: ?>
          <span class="single-event__hero-price numfont"><?= esc_html($hero_price_line); ?></span>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <!-- Hero End -->


  <section class="single-tour__content single-event__body">
    <div class="container">

      <div class="single-event__about-grid" id="o-sobytii">
        <div class="single-hotel__content__wrap single-event__columns">

          <!-- About start -->
          <div class="single-event__about-main">
            <h2 class="h2">О событии</h2>
            <?php
            if ($event_about !== '' && trim(wp_strip_all_tags($event_about)) !== ''):
              ?>
              <div class="single-event__about-body editor-content">
                <?= wp_kses_post($event_about); ?>
              </div>
            <?php elseif ($excerpt !== ''): ?>
              <p class="single-event__lead"><?= esc_html($excerpt); ?></p>
            <?php endif; ?>

            <?php
            $lucide_attrs = ['width' => '20', 'height' => '20', 'stroke' => 'currentColor'];
            if ($show_quick):
              ?>

            <?php endif; ?>
          </div>

          <!-- About end -->

          <!-- Dates start -->
          <?php if (!empty($dates_section_rows)): ?>
            <section class="single-event__dates-section">
              <h2 class="h2 single-event__dates-title">Даты и места концертов</h2>
              <ul class="single-event__dates-list">
                <?php foreach ($dates_section_rows as $br): ?>
                  <?php
                  $row_city = $br['city'] !== '' ? $br['city'] : '—';
                  $row_venue = $br['venue'] !== '' ? $br['venue'] : '—';
                  $row_date_label = date_i18n('d.m.Y', strtotime($br['date']));
                  $row_price_rub = isset($br['price_rub']) ? $br['price_rub'] : null;
                  $row_orig = isset($br['price_original']) ? $br['price_original'] : null;
                  $row_cur = isset($br['price_currency']) ? trim((string) $br['price_currency']) : '';
                  ?>
                  <li class="single-event__dates-row">
                    <div class="single-event__dates-row-left">
                      <span class="single-event__dates-dot" aria-hidden="true"></span>
                      <div class="single-event__dates-meta">
                        <span class="single-event__dates-meta-txt numfont"><?= esc_html($row_date_label); ?></span>
                        <span class="single-event__dates-sep" aria-hidden="true"></span>
                        <span class="single-event__dates-meta-txt"><?= esc_html($row_city); ?></span>
                        <span class="single-event__dates-sep" aria-hidden="true"></span>
                        <span class="single-event__dates-meta-txt"><?= esc_html($row_venue); ?></span>
                      </div>
                    </div>
                    <div class="single-event__dates-row-right">
                      <?php if ($row_price_rub !== null && (int) $row_price_rub > 0): ?>
                        <span class="single-event__dates-price numfont js-event-price"
                          data-price-rub="<?= esc_attr((string) (int) $row_price_rub); ?>"
                          <?php if ($row_orig !== null && (float) $row_orig > 0 && $row_cur !== ''): ?>
                          data-price-original="<?= esc_attr((string) $row_orig); ?>"
                          data-price-currency="<?= esc_attr($row_cur); ?>"
                          <?php endif; ?>
                          data-has-from="true">от
                          <?= esc_html(number_format((int) $row_price_rub, 0, ',', ' ')); ?>
                          ₽</span>
                      <?php else: ?>
                        <span class="single-event__dates-price numfont">—</span>
                      <?php endif; ?>
                      <span class="single-event__dates-sep" aria-hidden="true"></span>
                      <span class="single-event__dates-book-wrap">
                        <?php if ($tour_booking_url): ?>
                          <a href="<?= esc_url($tour_booking_url); ?>" class="single-event__dates-book" target="_blank"
                            rel="nofollow noopener">забронировать</a>
                        <?php else: ?>
                          <button type="button" class="single-event__dates-book js-event-booking-btn"
                            data-event-id="<?= esc_attr($post_id); ?>" data-event-title="<?= esc_attr($event_title); ?>"
                            data-event-venue="<?= esc_attr($br['venue']); ?>" data-event-time="<?= esc_attr($event_time); ?>"
                            data-min-price="<?= esc_attr($row_price_rub !== null ? (int) $row_price_rub : 0); ?>">забронировать</button>
                        <?php endif; ?>
                      </span>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endif; ?>
          <!-- Dates end -->

          <!-- Scheme start -->
          <?php if ($venue_scheme_url): ?>
            <section class="single-event__venue-scheme">
              <h2 class="h2">Схема зала</h2>
              <div class="single-event__venue-layout">
                <figure class="single-event__venue-figure">
                  <img src="<?= esc_url($venue_scheme_url); ?>" alt="<?= esc_attr($venue_scheme_alt); ?>" loading="lazy">
                </figure>
                <?php if (!empty($venue_scheme_legend) && is_array($venue_scheme_legend)): ?>
                  <?php
                  $legend_currency_map = [
                    '₽' => 'RUB', 'руб' => 'RUB', 'руб.' => 'RUB', 'rub' => 'RUB',
                    '$' => 'USD', 'usd' => 'USD',
                    '€' => 'EUR', 'eur' => 'EUR',
                    '£' => 'GBP', 'gbp' => 'GBP',
                  ];
                  ?>
                  <ul class="single-event__venue-legend">
                    <?php foreach ($venue_scheme_legend as $leg): ?>
                      <?php
                      $lab = isset($leg['legend_label']) ? trim((string) $leg['legend_label']) : '';
                      $curr = isset($leg['legend_currency']) ? trim((string) $leg['legend_currency']) : '';
                      $pr = function_exists('bsi_format_venue_scheme_legend_price')
                        ? bsi_format_venue_scheme_legend_price($leg['legend_price'] ?? '', $curr)
                        : trim((string) ($leg['legend_price'] ?? ''));
                      if ($lab === '' && trim($pr) === '') {
                        continue;
                      }

                      $leg_amount_units = null;
                      $leg_iso = null;
                      $leg_raw = $leg['legend_price'] ?? null;
                      if ($leg_raw !== null && $leg_raw !== '' && is_numeric($leg_raw)) {
                        $leg_units = (int) round((int) $leg_raw / 1000);
                        if ($leg_units > 0) {
                          $leg_amount_units = $leg_units;
                        }
                      }
                      $curr_key = mb_strtolower($curr);
                      if (isset($legend_currency_map[$curr_key])) {
                        $leg_iso = $legend_currency_map[$curr_key];
                      }
                      $leg_price_rub = null;
                      $leg_price_original = null;
                      $leg_price_currency = null;
                      if ($leg_amount_units !== null && $leg_iso !== null && function_exists('bsi_education_convert_price_to_rub')) {
                        $leg_rub_converted = bsi_education_convert_price_to_rub((float) $leg_amount_units, $leg_iso);
                        if ($leg_rub_converted !== null && $leg_rub_converted > 0) {
                          $leg_price_rub = (int) $leg_rub_converted;
                          if ($leg_iso !== 'RUB') {
                            $leg_price_original = (float) $leg_amount_units;
                            $leg_price_currency = $leg_iso;
                          }
                        }
                      }
                      ?>
                      <li class="single-event__venue-legend-item">
                        <span class="single-event__venue-legend-label">
                          <?= esc_html($lab); ?>
                        </span>
                        <span class="single-event__venue-legend-leader" aria-hidden="true"></span>
                        <?php if ($leg_price_rub !== null): ?>
                          <span class="single-event__venue-legend-price numfont js-event-price"
                            data-price-rub="<?= esc_attr((string) (int) $leg_price_rub); ?>"
                            <?php if ($leg_price_original !== null && $leg_price_currency !== null): ?>
                            data-price-original="<?= esc_attr((string) $leg_price_original); ?>"
                            data-price-currency="<?= esc_attr($leg_price_currency); ?>"
                            <?php endif; ?>>
                            <?= esc_html($pr); ?>
                          </span>
                        <?php else: ?>
                          <span class="single-event__venue-legend-price numfont">
                            <?= esc_html($pr); ?>
                          </span>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </section>
          <?php endif; ?>

          <!-- Scheme end -->


          <!-- Living start -->
          <?php
          $event_accommodation = function_exists('get_field') ? get_field('event_accommodation', $post_id) : [];
          $accommodation_rows = [];
          if (!empty($event_accommodation) && is_array($event_accommodation)) {
            foreach ($event_accommodation as $acc_row) {
              $acc_name = isset($acc_row['accommodation_hotel_name']) ? trim((string) $acc_row['accommodation_hotel_name']) : '';
              if ($acc_name === '') {
                continue;
              }
              $acc_stars_raw = isset($acc_row['accommodation_stars']) ? (int) $acc_row['accommodation_stars'] : 0;
              $acc_stars = max(0, min(5, $acc_stars_raw));
              $acc_descr = isset($acc_row['accommodation_description']) ? trim((string) $acc_row['accommodation_description']) : '';
              $acc_amount_raw = $acc_row['accommodation_price'] ?? null;
              $acc_amount = ($acc_amount_raw !== null && $acc_amount_raw !== '') ? (float) $acc_amount_raw : null;
              $acc_cur = isset($acc_row['accommodation_price_currency']) ? strtoupper(trim((string) $acc_row['accommodation_price_currency'])) : 'RUB';
              if ($acc_cur === '') {
                $acc_cur = 'RUB';
              }

              $acc_price_rub = null;
              $acc_price_original = null;
              $acc_price_currency = null;
              if ($acc_amount !== null && $acc_amount > 0 && function_exists('bsi_education_convert_price_to_rub')) {
                $acc_converted = bsi_education_convert_price_to_rub($acc_amount, $acc_cur);
                if ($acc_converted !== null && $acc_converted > 0) {
                  $acc_price_rub = (int) $acc_converted;
                  if ($acc_cur !== 'RUB') {
                    $acc_price_original = $acc_amount;
                    $acc_price_currency = $acc_cur;
                  }
                }
              }

              $acc_date_from = isset($acc_row['accommodation_date_from']) ? trim((string) $acc_row['accommodation_date_from']) : '';
              $acc_date_to = isset($acc_row['accommodation_date_to']) ? trim((string) $acc_row['accommodation_date_to']) : '';
              $acc_stay = function_exists('bsi_format_stay_range')
                ? bsi_format_stay_range($acc_date_from, $acc_date_to)
                : ['label' => '', 'duration' => '', 'nights' => 0, 'days' => 0, 'from_ts' => 0, 'to_ts' => 0];

              $accommodation_rows[] = [
                'name' => $acc_name,
                'stars' => $acc_stars,
                'descr' => $acc_descr,
                'price_rub' => $acc_price_rub,
                'price_original' => $acc_price_original,
                'price_currency' => $acc_price_currency,
                'date_from' => $acc_date_from,
                'date_to' => $acc_date_to,
                'stay_label' => $acc_stay['label'],
                'stay_duration' => $acc_stay['duration'],
                'stay_nights' => $acc_stay['nights'],
                'stay_days' => $acc_stay['days'],
                'stay_from_ts' => $acc_stay['from_ts'],
              ];
            }
          }
          ?>

          <?php if (!empty($accommodation_rows)): ?>
            <section class="single-event__accommodation-section">
              <h2 class="h2">Варианты проживания и цены</h2>
              <ul class="single-event__accommodation-grid">
                <?php foreach ($accommodation_rows as $acc): ?>
                  <li class="single-event__accommodation-card" data-stay-from="<?= esc_attr($acc['date_from']); ?>"
                    data-stay-to="<?= esc_attr($acc['date_to']); ?>"
                    data-stay-from-ts="<?= esc_attr((string) $acc['stay_from_ts']); ?>"
                    data-stay-nights="<?= esc_attr((string) $acc['stay_nights']); ?>"
                    data-price-rub="<?= esc_attr((string) (int) ($acc['price_rub'] ?? 0)); ?>">
                    <div class="single-event__accommodation-card-head">
                      <?php if ($acc['stars'] > 0): ?>
                        <span class="single-event__accommodation-card-stars" aria-label="<?= esc_attr($acc['stars'] . ' из 5'); ?>">
                          <span class="single-event__accommodation-card-stars-num"><?= esc_html((string) $acc['stars']); ?></span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star" aria-hidden="true">
                            <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>
                          </svg>
                        </span>
                      <?php endif; ?>
                      <h3 class="single-event__accommodation-card-name"><?= esc_html($acc['name']); ?></h3>
                    </div>
                    <?php if ($acc['stay_label'] !== ''): ?>
                      <div class="single-event__accommodation-card-stay">
                        <svg class="single-event__accommodation-card-stay-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>
                        </svg>
                        <span class="single-event__accommodation-card-stay-dates numfont"><?= esc_html($acc['stay_label']); ?></span>
                        <?php if ($acc['stay_duration'] !== ''): ?>
                          <span class="single-event__accommodation-card-stay-dot" aria-hidden="true"></span>
                          <span class="single-event__accommodation-card-stay-duration"><?= esc_html($acc['stay_duration']); ?></span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                    <?php if ($acc['descr'] !== ''): ?>
                      <p class="single-event__accommodation-card-descr"><?= esc_html($acc['descr']); ?></p>
                    <?php endif; ?>
                    <div class="single-event__accommodation-card-foot">
                      <?php if ($acc['price_rub'] !== null && (int) $acc['price_rub'] > 0): ?>
                        <span class="single-event__accommodation-card-price numfont js-event-price"
                          data-price-rub="<?= esc_attr((string) (int) $acc['price_rub']); ?>"
                          <?php if ($acc['price_original'] !== null && (float) $acc['price_original'] > 0 && $acc['price_currency'] !== null && $acc['price_currency'] !== ''): ?>
                          data-price-original="<?= esc_attr((string) $acc['price_original']); ?>"
                          data-price-currency="<?= esc_attr($acc['price_currency']); ?>"
                          <?php endif; ?>><?= esc_html(number_format((int) $acc['price_rub'], 0, ',', ' ')); ?>
                          ₽</span>
                      <?php else: ?>
                        <span class="single-event__accommodation-card-price numfont">Цена по запросу</span>
                      <?php endif; ?>
                      <button type="button"
                        class="single-event__accommodation-card-link js-event-booking-btn"
                        data-event-id="<?= esc_attr((string) $post_id); ?>"
                        data-event-title="<?= esc_attr($event_title); ?>"
                        data-event-venue="<?= esc_attr($event_venue); ?>"
                        data-event-time="<?= esc_attr($event_time); ?>"
                        data-accommodation-name="<?= esc_attr($acc['name']); ?>"
                        data-accommodation-stars="<?= esc_attr((string) $acc['stars']); ?>"
                        <?php if ($acc['price_rub'] !== null && (int) $acc['price_rub'] > 0): ?>
                        data-min-price="<?= esc_attr((string) (int) $acc['price_rub']); ?>"
                        <?php endif; ?>
                        <?php if ($acc['price_original'] !== null && $acc['price_currency'] !== null): ?>
                        data-accommodation-price-original="<?= esc_attr((string) $acc['price_original']); ?>"
                        data-accommodation-price-currency="<?= esc_attr($acc['price_currency']); ?>"
                        <?php endif; ?>><span>Забронировать</span><svg class="single-event__accommodation-card-link-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></button>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endif; ?>

          <?php if (!empty(trim(strip_tags($tour_extra)))): ?>
            <section class="single-event__accommodation editor-content">
              <?php if (empty($accommodation_rows)): ?>
                <h2 class="h2">Размещение</h2>
              <?php endif; ?>
              <?= wp_kses_post($tour_extra); ?>
            </section>
          <?php endif; ?>
          <!-- Living end -->

          <!-- Program start -->
          <?php if (!empty($tour_program) && is_array($tour_program)): ?>
            <section class="accordion tour-program single-event__program">
              <div class="tour-program__acc-head accordion__head">
                <h2 class="h2 tour-program__title">Программа тура</h2>
                <button class="btn-expand  accordion__toggle-all" type="button">Раскрыть все</button>
              </div>

              <div class="accordion__list tour-program__list">
                <?php foreach ($tour_program as $i => $day): ?>
                  <?php
                  $day_title = !empty($day['day_title']) ? (string) $day['day_title'] : '';
                  $day_text = !empty($day['day_content']) ? (string) $day['day_content'] : '';
                  if (!$day_title && !$day_text) {
                    continue;
                  }
                  $is_open = false;
                  ?>
                  <div class="accordion__item tour-program__day <?= $is_open ? 'is-open' : ''; ?>">
                    <button class="accordion__btn tour-program__day-btn" type="button">
                      <span class="accordion__title">
                        <?= esc_html($day_title ?: ('День ' . ($i + 1))); ?>
                      </span>
                      <span class="accordion__icon" aria-hidden="true">
                        <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
                      </span>
                    </button>

                    <div class="accordion__panel">
                      <div class="accordion__content">
                        <?php if ($day_text): ?>
                          <div class="editor-content">
                            <?= wp_kses_post($day_text); ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>
          <!-- Program end -->

        </div>



        <aside class="single-event__about-aside" aria-label="Краткая информация и бронирование">
          <div class="hotel-widget single-event__booking-widget single-event__booking-widget--inline">
            <?php if ($widget_country_title || $region_term || $resort_term): ?>
              <?php
              $items = [];
              if ($widget_country_title) {
                $items[] = $widget_country_permalink
                  ? '<a class="single-hotel__address-link" href="' . esc_url($widget_country_permalink) . '">' . esc_html($widget_country_title) . '</a>'
                  : '<span>' . esc_html($widget_country_title) . '</span>';
              }

              if ($resort_term) {
                $resort_link = get_term_link($resort_term);
                $items[] = !is_wp_error($resort_link)
                  ? '<a class="single-hotel__address-link" href="' . esc_url($resort_link) . '">' . esc_html($resort_term->name) . '</a>'
                  : '<span>' . esc_html($resort_term->name) . '</span>';
              }
              ?>
              <div class="single-hotel__top-line">
                <div class="single-hotel__address">
                  <?php if (!empty($widget_country_flag)): ?>
                    <img src="<?= esc_url($widget_country_flag); ?>" alt="">
                  <?php endif; ?>
                  <div class="single-hotel__address-text">
                    <?= implode(', ', $items); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>



            <div class="aside-contact-item single-event__booking-widget-phones">
              <a class="aside-contact-item__link numfont" href="tel:<?= esc_attr($event_widget_phone_primary_tel); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-phone-call" aria-hidden="true">
                  <path d="M13 2a9 9 0 0 1 9 9"></path>
                  <path d="M13 6a5 5 0 0 1 5 5"></path>
                  <path
                    d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384">
                  </path>
                </svg>
                <span><?= esc_html($event_widget_phone_primary); ?></span>
              </a>
              <a class="aside-contact-item__link numfont"
                href="tel:<?= esc_attr($event_widget_phone_secondary_tel); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-phone-call" aria-hidden="true">
                  <path d="M13 2a9 9 0 0 1 9 9"></path>
                  <path d="M13 6a5 5 0 0 1 5 5"></path>
                  <path
                    d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384">
                  </path>
                </svg>
                <span><?= esc_html($event_widget_phone_secondary); ?></span>
              </a>
            </div>

            <?php if (!empty($include_terms) && !is_wp_error($include_terms)): ?>
              <div class="sigle-tour-include tour-card-row__included single-event__booking-widget-includes">
                <?php foreach ($include_terms as $t): ?>
                  <?php
                  $icon = function_exists('get_field') ? get_field('tour_include_icon', 'term_' . $t->term_id) : null;
                  $icon_url = (is_array($icon) && !empty($icon['url'])) ? $icon['url'] : '';
                  ?>
                  <span class="tour-include__item tour-tag white">
                    <?php if ($icon_url): ?>
                      <img class="tour-include__icon" src="<?= esc_url($icon_url); ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <span class="tour-include__text"><?= esc_html($t->name); ?></span>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if ($price_from_amount !== null && (int) $price_from_amount > 0): ?>
              <div class="single-event__currency-toggle">
                <label class="ui-checkbox">
                  <input type="checkbox" class="ui-checkbox__input js-education-show-original-currency" name="show_original_currency_event"
                    value="1">
                  <span class="ui-checkbox__mark"></span>
                  <span class="ui-checkbox__text">Показать в валюте</span>
                </label>
              </div>
            <?php endif; ?>

            <div class="hotel-widget__price numfont">
              <?php if ($price_from_amount !== null && (int) $price_from_amount > 0): ?>
                <span class="js-event-price"
                  data-price-rub="<?= esc_attr((string) (int) $price_from_amount); ?>"
                  <?php if ($event_price_original !== null && $event_price_original > 0 && $event_price_currency !== null && $event_price_currency !== ''): ?>
                  data-price-original="<?= esc_attr((string) $event_price_original); ?>"
                  data-price-currency="<?= esc_attr($event_price_currency); ?>"
                  <?php endif; ?>
                  data-has-from="true"><?= esc_html($hero_price_line); ?></span>
              <?php elseif ($tour_price_from !== ''): ?>
                <?= esc_html($tour_price_from); ?>
              <?php else: ?>
                Запросить
              <?php endif; ?>
            </div>

            <?php if ($tour_booking_url): ?>
              <a href="<?= esc_url($tour_booking_url); ?>" class="btn btn-accent hotel-widget__btn-book sm"
                target="_blank" rel="nofollow noopener">Забронировать</a>
            <?php else: ?>
              <button type="button" class="btn btn-accent hotel-widget__btn-book sm js-event-booking-btn"
                data-event-id="<?= esc_attr($post_id); ?>" data-event-title="<?= esc_attr($event_title); ?>"
                data-event-venue="<?= esc_attr($event_venue); ?>" data-event-time="<?= esc_attr($event_time); ?>"
                data-min-price="<?= esc_attr($price_from_amount ?? 0); ?>">
                Забронировать
              </button>
            <?php endif; ?>
          </div>
        </aside>
      </div>

    </div>
  </section>


  <?php if (!empty($tour_included) || !empty($tour_not_inc)): ?>
    <section class="single-education__price-details-section single-event__price-details-section">
      <div class="container">
        <div class="single-education__price-details">
          <?php if (!empty($tour_included)): ?>
            <div class="single-education__price-included single-event__price-col">
              <h3 class="single-education__price-title">В стоимость входит</h3>
              <div class="single-education__price-content">
                <?= wp_kses_post($tour_included); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_not_inc)): ?>
            <div class="single-education__price-extra single-event__price-col">
              <h3 class="single-education__price-title">Оплачивается дополнительно</h3>
              <div class="single-education__price-content">
                <?= wp_kses_post($tour_not_inc); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty(trim(strip_tags($event_additional)))): ?>
    <section class="tour-extra-section single-event__additional">
      <div class="container">
        <h3 class="h3 tour-extra-section__title">Дополнительно</h3>
        <div class="tour-extra-section__content editor-content">
          <?= wp_kses_post($event_additional); ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="single-event__booking-cta" aria-label="Забронировать событие">
    <div class="container">
      <div class="single-event__booking-cta-inner">
        <div class="single-event__booking-cta-head">
          <h2 class="single-event__booking-cta-title">Забронировать</h2>
          <p class="single-event__booking-cta-lead"><?= esc_html($event_booking_cta_lead); ?></p>
        </div>
        <?php if ($tour_booking_url): ?>
          <div class="single-event__booking-cta-actions">
            <a href="<?= esc_url($tour_booking_url); ?>"
              class="single-event__booking-cta-submit single-event__booking-cta-submit--wide" target="_blank"
              rel="nofollow noopener">Забронировать</a>
          </div>
        <?php else: ?>
          <form id="event-booking-cta-form" class="single-event__booking-cta-form" novalidate
            data-event-id="<?= esc_attr((string) $post_id); ?>" data-event-title="<?= esc_attr($event_title); ?>">
            <input type="hidden" name="action" value="event_ticket_booking">
            <input type="hidden" name="event_booking_minimal" value="1">
            <input type="hidden" name="event_title" value="<?= esc_attr($event_title); ?>">
            <input type="hidden" name="page_url" value="<?= esc_url(get_permalink($post_id)); ?>">

            <div class="single-event__booking-cta-row">
              <div class="single-event__booking-cta-field">
                <label class="screen-reader-text" for="event-booking-cta-name">Имя</label>
                <input id="event-booking-cta-name" type="text" name="name" class="single-event__booking-cta-input"
                  placeholder="имя" autocomplete="name" required data-field="name">
                <span class="single-event__booking-cta-field-error js-field-error" data-error-for="name"></span>
              </div>
              <div class="single-event__booking-cta-field">
                <label class="screen-reader-text" for="event-booking-cta-phone">Телефон</label>
                <input id="event-booking-cta-phone" type="tel" name="phone"
                  class="single-event__booking-cta-input js-phone-mask" placeholder="Телефон" autocomplete="tel" required
                  data-field="phone">
                <span class="single-event__booking-cta-field-error js-field-error" data-error-for="phone"></span>
              </div>
              <button type="submit" class="single-event__booking-cta-submit" data-default-label="Забронировать">
                Забронировать
              </button>
            </div>

            <?php
            if (function_exists('bsi_render_privacy_consent_checkbox')) {
              bsi_render_privacy_consent_checkbox([
                'variant' => 'event-booking-cta',
                'checkbox_id' => 'event-booking-cta-privacy',
              ]);
            }
            ?>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if (!empty($event_faq) && is_array($event_faq)): ?>
    <section class="single-event__faq">
      <div class="container">
        <h2 class="h2">Ответы на часто задаваемые вопросы</h2>
        <div class="accordion single-event__faq-acc">
          <div class="accordion__list single-event__faq-list">
            <?php foreach ($event_faq as $faq_row): ?>
              <?php
              $fq = isset($faq_row['faq_question']) ? trim((string) $faq_row['faq_question']) : '';
              $fa = isset($faq_row['faq_answer']) ? (string) $faq_row['faq_answer'] : '';
              if ($fq === '') {
                continue;
              }
              ?>
              <div class="accordion__item single-event__faq-item">
                <button class="accordion__btn single-event__faq-btn" type="button">
                  <span class="accordion__title"><?= esc_html($fq); ?></span>
                  <span class="accordion__icon" aria-hidden="true">
                    <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
                  </span>
                </button>
                <div class="accordion__panel">
                  <div class="accordion__content editor-content">
                    <?= wp_kses_post($fa); ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php
  if (!empty($related_events)) {
    get_template_part('template-parts/event/related-tours-slider', null, [
      'posts' => $related_events,
    ]);
  }
  ?>



</main>



<?php
get_template_part('template-parts/event/ticket-booking-modal');

get_footer();
