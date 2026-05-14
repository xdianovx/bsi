<?php
/**
 * Table "Даты и места проведения".
 *
 * @var array $args {
 *   @type int   $post_id
 *   @type array $event_dates_rows from ACF event_dates
 *   @type string $fallback_venue event_venue
 *   @type array $event_tickets
 *   @type int|null $min_ticket_price
 * }
 */
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : 0;
$rows = isset($args['event_dates_rows']) ? $args['event_dates_rows'] : [];
$fallback_venue = isset($args['fallback_venue']) ? trim((string) $args['fallback_venue']) : '';
$event_tickets = isset($args['event_tickets']) && is_array($args['event_tickets']) ? $args['event_tickets'] : [];
$min_ticket_price = array_key_exists('min_ticket_price', $args) ? $args['min_ticket_price'] : null;

if (!$post_id || empty($rows) || !is_array($rows)) {
  return;
}

$title = get_the_title($post_id);
$event_venue_global = function_exists('get_field') ? trim((string) get_field('event_venue', $post_id)) : '';
$event_time = function_exists('get_field') ? trim((string) get_field('event_time', $post_id)) : '';
$tour_booking_url = trim((string) (function_exists('get_field') ? get_field('tour_booking_url', $post_id) : ''));

$body_rows = [];
foreach ($rows as $row) {
  if (empty($row['date_value'])) {
    continue;
  }
  $d = (string) $row['date_value'];
  $city = isset($row['date_city']) ? trim((string) $row['date_city']) : '';
  $venue = isset($row['date_venue']) ? trim((string) $row['date_venue']) : '';
  if ($venue === '') {
    $venue = $event_venue_global !== '' ? $event_venue_global : $fallback_venue;
  }
  $row_price = null;
  if (!empty($row['date_row_price'])) {
    $row_price = (int) $row['date_row_price'];
  }
  $display_price = $row_price !== null ? $row_price : $min_ticket_price;

  $ticket_idx = isset($row['date_ticket_index']) && $row['date_ticket_index'] !== '' ? (int) $row['date_ticket_index'] : -1;
  $ticket = ($ticket_idx >= 0 && !empty($event_tickets[$ticket_idx])) ? $event_tickets[$ticket_idx] : null;

  $body_rows[] = [
    'date' => $d,
    'city' => $city,
    'venue' => $venue,
    'price' => $display_price,
    'ticket' => $ticket,
  ];
}

if (empty($body_rows)) {
  return;
}
?>

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
        <?php foreach ($body_rows as $br): ?>
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
                <a href="<?= esc_url($tour_booking_url); ?>" class="single-event__dates-book link-arrow" target="_blank"
                  rel="nofollow noopener">Забронировать</a>
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
                  data-event-title="<?= esc_attr($title); ?>"
                  data-event-venue="<?= esc_attr($br['venue']); ?>"
                  data-event-time="<?= esc_attr($event_time); ?>">Забронировать</button>
              <?php else: ?>
                <button type="button" class="single-event__dates-book js-event-booking-btn"
                  data-event-id="<?= esc_attr($post_id); ?>"
                  data-event-title="<?= esc_attr($title); ?>"
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
