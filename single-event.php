<?php
/**
 * Single Event Template
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
$hero_extra = function_exists('get_field') ? trim((string) get_field('event_hero_extra_tag', $post_id)) : '';
$event_tickets = function_exists('get_field') ? get_field('event_tickets', $post_id) : [];
$event_venue = function_exists('get_field') ? trim((string) get_field('event_venue', $post_id)) : '';
$event_time = function_exists('get_field') ? trim((string) get_field('event_time', $post_id)) : '';
$tour_nights = function_exists('get_field') ? (int) get_field('tour_nights', $post_id) : 0;
$tour_transport = function_exists('get_field') ? trim((string) get_field('tour_transport', $post_id)) : '';
$venue_scheme = function_exists('get_field') ? get_field('venue_scheme', $post_id) : null;
$venue_scheme_legend = function_exists('get_field') ? get_field('venue_scheme_legend', $post_id) : [];
$event_faq = function_exists('get_field') ? get_field('event_faq', $post_id) : [];

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
$rel_terms = get_the_terms($post_id, 'tour_type');
$rel_type_ids = (!empty($rel_terms) && !is_wp_error($rel_terms)) ? wp_list_pluck($rel_terms, 'term_id') : [];
$rel_q = [
  'post_type' => 'event',
  'post_status' => 'publish',
  'posts_per_page' => 6,
  'post__not_in' => [$post_id],
  'orderby' => 'title',
  'order' => 'ASC',
  'no_found_rows' => true,
];
if (!empty($rel_type_ids)) {
  $rel_q['tax_query'] = [
    [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => $rel_type_ids,
    ],
  ];
} elseif ($country_id) {
  $rel_q['meta_query'] = [
    [
      'key' => 'tour_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ];
}
$rel_q['suppress_filters'] = false;
$related_events = get_posts($rel_q);
$min_ticket_price = null;
if (!empty($event_tickets) && is_array($event_tickets)) {
  foreach ($event_tickets as $ticket) {
    if (!empty($ticket['ticket_price'])) {
      $price = (int) $ticket['ticket_price'];
      if ($min_ticket_price === null || $price < $min_ticket_price) {
        $min_ticket_price = $price;
      }
    }
  }
}

$hero_url = get_the_post_thumbnail_url($post_id, 'full');
if (!$hero_url && !empty($tour_gallery) && is_array($tour_gallery)) {
  $first = $tour_gallery[0] ?? null;
  if (is_array($first)) {
    $hero_url = !empty($first['sizes']['large']) ? (string) $first['sizes']['large'] : (string) ($first['url'] ?? '');
  }
}

$hero_date_label = '';
if (!empty($event_dates_rows) && is_array($event_dates_rows)) {
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

$hero_type_terms = get_the_terms($post_id, 'tour_type');
if (is_wp_error($hero_type_terms)) {
  $hero_type_terms = [];
}

$hero_price_line = '';
if ($min_ticket_price !== null) {
  $hero_price_line = 'от ' . number_format($min_ticket_price, 0, ',', ' ') . ' ₽';
} elseif ($tour_price_from !== '') {
  $hero_price_line = $tour_price_from;
} else {
  $hero_price_line = 'Запросить';
}

$hero_style = '';
if ($hero_url) {
  $hero_style = '--single-event-hero-bg:url(' . esc_url($hero_url) . ')';
}

$event_title = get_the_title($post_id);

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
    $row_price = null;
    if (!empty($row['date_row_price'])) {
      $row_price = (int) $row['date_row_price'];
    }
    $display_price = $row_price !== null ? $row_price : $min_ticket_price;
    $ticket_idx = isset($row['date_ticket_index']) && $row['date_ticket_index'] !== '' ? (int) $row['date_ticket_index'] : -1;
    $ticket_for_row = ($ticket_idx >= 0 && !empty($event_tickets) && is_array($event_tickets) && !empty($event_tickets[$ticket_idx]))
      ? $event_tickets[$ticket_idx]
      : null;
    $dates_section_rows[] = [
      'date' => $d,
      'city' => $city,
      'venue' => $venue_row,
      'price' => $display_price,
      'ticket' => $ticket_for_row,
    ];
  }
}

$excerpt = get_the_excerpt($post_id);
$show_quick = $tour_duration || $tour_nights || $resort_term || $tour_transport;

$event_catalog_page = get_page_by_path('sobytiynye-tury');
$event_catalog_url = $event_catalog_page ? get_permalink($event_catalog_page->ID) : home_url('/sobytiynye-tury/');

get_header();
?>

<main class="site-main single-event-page">

  <section class="single-event__hero"<?= $hero_style !== '' ? ' style="' . esc_attr($hero_style) . '"' : ''; ?>>
    <div class="single-event__hero-bg" aria-hidden="true"></div>
    <div class="single-event__hero-overlay" aria-hidden="true"></div>

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
        <?php if ($hero_extra !== ''): ?>
          <span class="single-event__hero-tag"><?= esc_html($hero_extra); ?></span>
        <?php endif; ?>
      </div>

      <h1 class="h1 single-event__hero-title"><?= esc_html($event_title); ?></h1>

      <div class="single-event__hero-actions">
        <span class="single-event__hero-price numfont"><?= esc_html($hero_price_line); ?></span>
        <?php if ($tour_booking_url): ?>
          <a href="<?= esc_url($tour_booking_url); ?>" class="btn btn-accent single-event__hero-btn"
            target="_blank" rel="nofollow noopener">Забронировать</a>
        <?php else: ?>
          <button type="button" class="btn btn-accent single-event__hero-btn js-event-booking-btn"
            data-event-id="<?= esc_attr($post_id); ?>"
            data-event-title="<?= esc_attr($event_title); ?>"
            data-event-venue="<?= esc_attr($event_venue); ?>"
            data-event-time="<?= esc_attr($event_time); ?>"
            data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">Забронировать</button>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="single-tour__content single-event__body">
    <div class="container">

      <div class="single-event__about-grid" id="o-sobytii">
        <div class="single-event__about-main">
          <h2 class="h2">О событии</h2>
          <?php if ($excerpt !== ''): ?>
            <p class="single-event__lead"><?= esc_html($excerpt); ?></p>
          <?php endif; ?>

          <?php
          $lucide_attrs = ['width' => '20', 'height' => '20', 'stroke' => 'currentColor'];
          if ($show_quick):
            ?>
            <div class="event-aside-details single-event__quick-facts">
              <?php if ($tour_duration): ?>
                <div class="event-aside-detail">
                  <span class="event-aside-detail__value"><?= esc_html($tour_duration); ?></span>
                </div>
              <?php elseif ($tour_nights): ?>
                <div class="event-aside-detail">
                  <span class="event-aside-detail__icon" aria-hidden="true">
                    <?php echo function_exists('bsi_lucide_icon') ? bsi_lucide_icon('calendar', $lucide_attrs) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </span>
                  <span class="event-aside-detail__value numfont"><?= (int) $tour_nights; ?></span>
                </div>
              <?php endif; ?>

              <?php if ($tour_transport): ?>
                <div class="event-aside-detail">
                  <span class="event-aside-detail__icon" aria-hidden="true">
                    <?php echo function_exists('bsi_lucide_icon') ? bsi_lucide_icon('calendar', $lucide_attrs) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </span>
                  <span class="event-aside-detail__label">Транспорт</span>
                  <span class="event-aside-detail__value"><?= esc_html($tour_transport); ?></span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
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
              if ($region_term) {
                $region_link = get_term_link($region_term);
                $items[] = !is_wp_error($region_link)
                  ? '<a class="single-hotel__address-link" href="' . esc_url($region_link) . '">' . esc_html($region_term->name) . '</a>'
                  : '<span>' . esc_html($region_term->name) . '</span>';
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

            <?php if ($tour_duration || $tour_nights || $tour_transport): ?>
              <div class="single-event__widget-meta">
                <?php if ($tour_duration): ?>
                  <div class="single-event__widget-meta-row"><?= esc_html($tour_duration); ?></div>
                <?php elseif ($tour_nights): ?>
                  <div class="single-event__widget-meta-row"><?= esc_html((string) $tour_nights . ' ноч.'); ?></div>
                <?php endif; ?>
                <?php if ($tour_transport): ?>
                  <div class="single-event__widget-meta-row"><?= esc_html($tour_transport); ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="hotel-widget__price numfont">
              <?php if ($min_ticket_price !== null): ?>
                от <?= number_format($min_ticket_price, 0, ',', ' '); ?> ₽
              <?php elseif ($tour_price_from): ?>
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
                data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">
                Забронировать
              </button>
            <?php endif; ?>
          </div>
        </aside>
      </div>

      <div class="single-hotel__content__wrap single-event__columns">

        <div class="hotel-content single-event__main-column">

          <div class="single-tour-content editor-content">
            <?php the_content(); ?>
          </div>

          <?php if (!empty($dates_section_rows)): ?>
            <section class="single-event__dates-section">
              <h2 class="h2">Даты и места проведения</h2>
              <div class="single-event__dates-table-wrap">
                <table class="single-event__dates-table">
                  <thead>
                    <tr>
                      <th scope="col">Дата</th>
                      <th scope="col">Город</th>
                      <th scope="col">Площадка</th>
                      <th scope="col">Цена</th>
                      <th scope="col"><span class="screen-reader-text">Действие</span></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($dates_section_rows as $br): ?>
                      <tr>
                        <td class="numfont"><?= esc_html(date_i18n('d.m.Y', strtotime($br['date']))); ?></td>
                        <td><?= esc_html($br['city'] !== '' ? $br['city'] : '—'); ?></td>
                        <td><?= esc_html($br['venue'] !== '' ? $br['venue'] : '—'); ?></td>
                        <td class="numfont">
                          <?php if ($br['price'] !== null): ?>
                            от <?= esc_html(number_format((int) $br['price'], 0, ',', ' ')); ?> ₽
                          <?php else: ?>
                            —
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($tour_booking_url): ?>
                            <a href="<?= esc_url($tour_booking_url); ?>" class="single-event__dates-book link-arrow"
                              target="_blank" rel="nofollow noopener">Забронировать</a>
                          <?php elseif (!empty($br['ticket']) && is_array($br['ticket'])): ?>
                            <?php
                            $tt = !empty($br['ticket']['ticket_type']) ? (string) $br['ticket']['ticket_type'] : '';
                            $tp = !empty($br['ticket']['ticket_price']) ? (int) $br['ticket']['ticket_price'] : 0;
                            $td = !empty($br['ticket']['ticket_description']) ? (string) $br['ticket']['ticket_description'] : '';
                            ?>
                            <button type="button" class="single-event__dates-book js-event-ticket-booking-btn"
                              data-ticket-type="<?= esc_attr($tt); ?>"
                              data-ticket-price="<?= esc_attr($tp); ?>"
                              data-ticket-desc="<?= esc_attr($td); ?>"
                              data-event-title="<?= esc_attr($event_title); ?>"
                              data-event-venue="<?= esc_attr($br['venue']); ?>"
                              data-event-time="<?= esc_attr($event_time); ?>">Забронировать</button>
                          <?php else: ?>
                            <button type="button" class="single-event__dates-book js-event-booking-btn"
                              data-event-id="<?= esc_attr($post_id); ?>"
                              data-event-title="<?= esc_attr($event_title); ?>"
                              data-event-venue="<?= esc_attr($br['venue']); ?>"
                              data-event-time="<?= esc_attr($event_time); ?>"
                              data-min-price="<?= esc_attr($br['price'] ?? 0); ?>">Забронировать</button>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </section>
          <?php endif; ?>

          <?php if ($venue_scheme_url): ?>
            <section class="single-event__venue-scheme">
              <h2 class="h2">Схема зала</h2>
              <div class="single-event__venue-layout">
                <figure class="single-event__venue-figure">
                  <img src="<?= esc_url($venue_scheme_url); ?>" alt="<?= esc_attr($venue_scheme_alt); ?>" loading="lazy">
                </figure>
                <?php if (!empty($venue_scheme_legend) && is_array($venue_scheme_legend)): ?>
                  <ul class="single-event__venue-legend">
                    <?php foreach ($venue_scheme_legend as $leg): ?>
                      <?php
                      $lab = isset($leg['legend_label']) ? trim((string) $leg['legend_label']) : '';
                      $pr = isset($leg['legend_price']) ? trim((string) $leg['legend_price']) : '';
                      if ($lab === '' && $pr === '') {
                        continue;
                      }
                      ?>
                      <li class="single-event__venue-legend-item">
                        <span class="single-event__venue-legend-label"><?= esc_html($lab); ?></span>
                        <span class="single-event__venue-legend-price numfont"><?= esc_html($pr); ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </section>
          <?php endif; ?>

          <?php if (!empty(trim(strip_tags($tour_extra)))): ?>
            <section class="single-event__accommodation editor-content">
              <h2 class="h2">Размещение</h2>
              <?= wp_kses_post($tour_extra); ?>
            </section>
          <?php endif; ?>

          <?php if (!empty($event_tickets) && is_array($event_tickets)): ?>
            <div class="event-tickets">
              <h2 class="h2">Типы билетов</h2>
              <div class="event-tickets__list">
                <?php foreach ($event_tickets as $ticket): ?>
                  <?php
                  $ticket_type = !empty($ticket['ticket_type']) ? $ticket['ticket_type'] : '';
                  $ticket_price = !empty($ticket['ticket_price']) ? (int) $ticket['ticket_price'] : 0;
                  $ticket_desc = !empty($ticket['ticket_description']) ? $ticket['ticket_description'] : '';
                  ?>
                  <div class="event-ticket">
                    <div class="event-ticket__main">
                      <div class="event-ticket__type"><?= esc_html($ticket_type); ?></div>
                      <?php if ($ticket_desc): ?>
                        <div class="event-ticket__desc"><?= nl2br(esc_html($ticket_desc)); ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="event-ticket__sidebar">
                      <div class="event-ticket__price">
                        <span class="event-ticket__price-value numfont"><?= number_format($ticket_price, 0, ',', ' '); ?>
                          ₽</span>
                      </div>
                      <button type="button" class="event-ticket__btn js-event-ticket-booking-btn"
                        data-ticket-type="<?= esc_attr($ticket_type); ?>"
                        data-ticket-price="<?= esc_attr($ticket_price); ?>"
                        data-ticket-desc="<?= esc_attr($ticket_desc); ?>"
                        data-event-title="<?= esc_attr(get_the_title()); ?>"
                        data-event-venue="<?= esc_attr($event_venue); ?>" data-event-time="<?= esc_attr($event_time); ?>">
                        Купить
                      </button>
                    </div>
                    <div class="event-ticket__perforation"></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_program) && is_array($tour_program)): ?>
            <section class="accordion tour-program">
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

        </div>

        <aside class="hotel-aside hotel-aside--event-sticky single-event__aside" aria-label="Бронирование и контакты">
          <div class="hotel-widget single-event__booking-widget">
            <?php if ($widget_country_title || $region_term || $resort_term): ?>
              <?php
              $items = [];
              if ($widget_country_title) {
                $items[] = $widget_country_permalink
                  ? '<a class="single-hotel__address-link" href="' . esc_url($widget_country_permalink) . '">' . esc_html($widget_country_title) . '</a>'
                  : '<span>' . esc_html($widget_country_title) . '</span>';
              }
              if ($region_term) {
                $region_link = get_term_link($region_term);
                $items[] = !is_wp_error($region_link)
                  ? '<a class="single-hotel__address-link" href="' . esc_url($region_link) . '">' . esc_html($region_term->name) . '</a>'
                  : '<span>' . esc_html($region_term->name) . '</span>';
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

            <?php if ($tour_duration || $tour_nights || $tour_transport): ?>
              <div class="single-event__widget-meta">
                <?php if ($tour_duration): ?>
                  <div class="single-event__widget-meta-row"><?= esc_html($tour_duration); ?></div>
                <?php elseif ($tour_nights): ?>
                  <div class="single-event__widget-meta-row"><?= esc_html((string) $tour_nights . ' ноч.'); ?></div>
                <?php endif; ?>
                <?php if ($tour_transport): ?>
                  <div class="single-event__widget-meta-row"><?= esc_html($tour_transport); ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="aside-contact-item">
              <a class="aside-contact-item__link numfont" href="tel:84957855535">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-phone-call" aria-hidden="true">
                  <path d="M13 2a9 9 0 0 1 9 9"></path>
                  <path d="M13 6a5 5 0 0 1 5 5"></path>
                  <path
                    d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
                </svg>
                <span>8 (495) 785-55-35</span>
              </a>
              <a class="aside-contact-item__link numfont" href="tel:88002005535">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="lucide lucide-phone-call" aria-hidden="true">
                  <path d="M13 2a9 9 0 0 1 9 9"></path>
                  <path d="M13 6a5 5 0 0 1 5 5"></path>
                  <path
                    d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>
                </svg>
                <span>8 (800) 200-55-35 (из регионов)</span>
              </a>
            </div>

            <?php if (!empty($include_terms) && !is_wp_error($include_terms)): ?>
              <div class="sigle-tour-include tour-card-row__included">
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

            <div class="hotel-widget__price numfont">
              <?php if ($min_ticket_price !== null): ?>
                от <?= number_format($min_ticket_price, 0, ',', ' '); ?> ₽
              <?php elseif ($tour_price_from): ?>
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
                data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">
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

  <section class="single-event__booking-cta" aria-label="Забронировать событие">
    <div class="container single-event__booking-cta-inner">
      <h2 class="single-event__booking-cta-title">Забронировать</h2>
      <p class="single-event__booking-cta-text">
        Оставьте заявку — менеджер уточнит детали и поможет с бронированием.
      </p>
      <?php if ($tour_booking_url): ?>
        <a href="<?= esc_url($tour_booking_url); ?>" class="btn btn-accent single-event__booking-cta-btn" target="_blank"
          rel="nofollow noopener">Забронировать</a>
      <?php else: ?>
        <button type="button" class="btn btn-accent single-event__booking-cta-btn js-event-booking-btn"
          data-event-id="<?= esc_attr($post_id); ?>" data-event-title="<?= esc_attr($event_title); ?>"
          data-event-venue="<?= esc_attr($event_venue); ?>" data-event-time="<?= esc_attr($event_time); ?>"
          data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">Забронировать</button>
      <?php endif; ?>
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

  <?php if (!empty($related_events)): ?>
    <section class="single-event__related news-slider__section">
      <div class="container">
        <div class="title-wrap news-slider__title-wrap">
          <div class="news-slider__title-wrap-left">
            <h2 class="h2 news-slider__title">Похожие событийные туры</h2>
            <div class="slider-arrow-wrap news-slider__arrows-wrap">
              <div class="slider-arrow slider-arrow-prev single-event-related-prev" tabindex="0" role="button"
                aria-label="Предыдущие события"></div>
              <div class="slider-arrow slider-arrow-next single-event-related-next" tabindex="0" role="button"
                aria-label="Следующие события"></div>
            </div>
          </div>
          <div class="title-wrap__buttons">
            <a href="<?= esc_url($event_catalog_url); ?>" class="title-wrap__link link-arrow">
              <span>Все событийные туры</span>
              <div class="link-arrow__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M7 7h10v10" />
                  <path d="M7 17 17 7" />
                </svg>
              </div>
            </a>
          </div>
        </div>

        <div class="swiper single-event-related-slider">
          <div class="swiper-wrapper">
            <?php foreach ($related_events as $rel_post): ?>
              <?php
              if (!($rel_post instanceof WP_Post)) {
                continue;
              }
              ?>
              <div class="swiper-slide single-event-related-slide">
                <?php get_template_part('template-parts/event/card-row', null, ['post_id' => (int) $rel_post->ID]); ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

</main>

<?php
get_template_part('template-parts/event/ticket-booking-modal');

get_footer();
