<?php
/**
 * Hero cover for single event (CPT event).
 *
 * @var array $args {
 *   @type int $post_id
 * }
 */
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$hero_url = get_the_post_thumbnail_url($post_id, 'full');
if (!$hero_url && function_exists('get_field')) {
  $tour_gallery = (array) get_field('tour_gallery', $post_id);
  $first = $tour_gallery[0] ?? null;
  if (is_array($first)) {
    $hero_url = !empty($first['sizes']['large']) ? (string) $first['sizes']['large'] : (string) ($first['url'] ?? '');
  }
}

$hero_extra = function_exists('get_field') ? trim((string) get_field('event_hero_extra_tag', $post_id)) : '';

$event_dates_rows = function_exists('get_field') ? get_field('event_dates', $post_id) : [];
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

$type_terms = get_the_terms($post_id, 'tour_type');
if (is_wp_error($type_terms)) {
  $type_terms = [];
}

$event_tickets = function_exists('get_field') ? get_field('event_tickets', $post_id) : [];
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

$tour_price_from = function_exists('get_field') ? trim((string) get_field('price_from', $post_id)) : '';
$tour_booking_url = trim((string) (function_exists('get_field') ? get_field('tour_booking_url', $post_id) : ''));
$event_venue = function_exists('get_field') ? trim((string) get_field('event_venue', $post_id)) : '';
$event_time = function_exists('get_field') ? trim((string) get_field('event_time', $post_id)) : '';

$price_line = '';
if ($min_ticket_price !== null) {
  $price_line = 'от ' . number_format($min_ticket_price, 0, ',', ' ') . ' ₽';
} elseif ($tour_price_from !== '') {
  $price_line = $tour_price_from;
} else {
  $price_line = 'Запросить';
}

$title = get_the_title($post_id);

$hero_style = '';
if ($hero_url) {
  $hero_style = '--single-event-hero-bg:url(' . esc_url($hero_url) . ')';
}
?>

<section class="single-event__hero"<?= $hero_style !== '' ? ' style="' . esc_attr($hero_style) . '"' : ''; ?>>

  <div class="single-event__hero-bg" aria-hidden="true"></div>
  <div class="single-event__hero-overlay" aria-hidden="true"></div>

  <div class="single-event__hero-inner container">
    <?php
    if (function_exists('yoast_breadcrumb')) {
      yoast_breadcrumb(
        '<div class="single-event__hero-breadcrumbs breadcrumbs"><p>',
        '</p></div>'
      );
    }
    ?>

    <div class="single-event__hero-tags">
      <?php if ($hero_date_label !== ''): ?>
        <span class="single-event__hero-tag"><?= esc_html($hero_date_label); ?></span>
      <?php endif; ?>
      <?php foreach ($type_terms as $t): ?>
        <span class="single-event__hero-tag"><?= esc_html($t->name); ?></span>
      <?php endforeach; ?>
      <?php if ($hero_extra !== ''): ?>
        <span class="single-event__hero-tag"><?= esc_html($hero_extra); ?></span>
      <?php endif; ?>
    </div>

    <h1 class="h1 single-event__hero-title"><?= esc_html($title); ?></h1>

    <div class="single-event__hero-actions">
      <span class="single-event__hero-price numfont"><?= esc_html($price_line); ?></span>
      <?php if ($tour_booking_url): ?>
        <a href="<?= esc_url($tour_booking_url); ?>" class="btn btn-accent single-event__hero-btn"
          target="_blank" rel="nofollow noopener">Забронировать</a>
      <?php else: ?>
        <button type="button" class="btn btn-accent single-event__hero-btn js-event-booking-btn"
          data-event-id="<?= esc_attr($post_id); ?>"
          data-event-title="<?= esc_attr($title); ?>"
          data-event-venue="<?= esc_attr($event_venue); ?>"
          data-event-time="<?= esc_attr($event_time); ?>"
          data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">Забронировать</button>
      <?php endif; ?>
    </div>
  </div>
</section>
