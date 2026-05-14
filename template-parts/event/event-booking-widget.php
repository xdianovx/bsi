<?php
/**
 * Booking summary widget (compact or sticky aside).
 *
 * @var array $args {
 *   @type int  $post_id
 *   @type bool $show_phones
 *   @type bool $show_include_tags
 * }
 */
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$show_phones = !empty($args['show_phones']);
$show_include_tags = !empty($args['show_include_tags']);

$country_id = function_exists('get_field') ? get_field('tour_country', $post_id) : 0;
if ($country_id instanceof WP_Post) {
  $country_id = $country_id->ID;
} elseif (is_array($country_id)) {
  $country_id = (int) reset($country_id);
} else {
  $country_id = (int) $country_id;
}

$country_title = '';
$country_permalink = '';
$country_flag = '';
if ($country_id) {
  $country_title = get_the_title($country_id);
  $country_permalink = get_permalink($country_id);
  $flag = function_exists('get_field') ? get_field('flag', $country_id) : '';
  $country_flag = (is_array($flag) && !empty($flag['url'])) ? $flag['url'] : (string) $flag;
}

$region_terms = get_the_terms($post_id, 'region');
$region_term = (!empty($region_terms) && !is_wp_error($region_terms)) ? $region_terms[0] : null;

$resort_terms = get_the_terms($post_id, 'resort');
$resort_term = (!empty($resort_terms) && !is_wp_error($resort_terms)) ? $resort_terms[0] : null;

$tour_duration = function_exists('get_field') ? trim((string) get_field('tour_duration', $post_id)) : '';
$tour_nights = function_exists('get_field') ? (int) get_field('tour_nights', $post_id) : 0;
$tour_transport = function_exists('get_field') ? trim((string) get_field('tour_transport', $post_id)) : '';

$tour_booking_url = trim((string) (function_exists('get_field') ? get_field('tour_booking_url', $post_id) : ''));
$tour_price_from = function_exists('get_field') ? trim((string) get_field('price_from', $post_id)) : '';
$event_tickets = function_exists('get_field') ? get_field('event_tickets', $post_id) : [];
$event_venue = function_exists('get_field') ? trim((string) get_field('event_venue', $post_id)) : '';
$event_time = function_exists('get_field') ? trim((string) get_field('event_time', $post_id)) : '';

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

$include_terms = get_the_terms($post_id, 'tour_include');

$extra_class = isset($args['extra_class']) ? trim((string) $args['extra_class']) : '';
$wrap_class = 'hotel-widget single-event__booking-widget' . ($extra_class !== '' ? ' ' . $extra_class : '');
?>

<div class="<?= esc_attr($wrap_class); ?>">
  <?php if ($country_title || $region_term || $resort_term): ?>
    <?php
    $items = [];
    if ($country_title) {
      $items[] = $country_permalink
        ? '<a class="single-hotel__address-link" href="' . esc_url($country_permalink) . '">' . esc_html($country_title) . '</a>'
        : '<span>' . esc_html($country_title) . '</span>';
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
        <?php if (!empty($country_flag)): ?>
          <img src="<?= esc_url($country_flag); ?>" alt="">
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

  <?php if ($show_phones): ?>
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
  <?php endif; ?>

  <?php if ($show_include_tags && !empty($include_terms) && !is_wp_error($include_terms)): ?>
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
      target="_blank" rel="nofollow noopener">
      Забронировать
    </a>
  <?php else: ?>
    <button type="button" class="btn btn-accent hotel-widget__btn-book sm js-event-booking-btn"
      data-event-id="<?= esc_attr($post_id); ?>" data-event-title="<?= esc_attr(get_the_title($post_id)); ?>"
      data-event-venue="<?= esc_attr($event_venue); ?>" data-event-time="<?= esc_attr($event_time); ?>"
      data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">
      Забронировать
    </button>
  <?php endif; ?>
</div>
