<?php
$event = get_query_var('event');
if (!$event || !is_array($event)) {
  return;
}

$event_id = 0;
$event_url = '';
$event_image = '';
$event_title = '';
$event_flag = '';
$price_value = '';
$nights = 0;
$checkin_dates = '';
$excursions_count = 0;
$booking_url = '';

if (!empty($event['id'])) {
  $event_id = (int) $event['id'];
} elseif (!empty($event['url'])) {
  $event_id = (int) url_to_postid($event['url']);
}

if (!$event_id) {
  return;
}

$event_url = !empty($event['url']) ? (string) $event['url'] : get_permalink($event_id);
$event_image = !empty($event['image']) ? (string) $event['image'] : (get_the_post_thumbnail_url($event_id, 'large') ?: '');
$event_title = !empty($event['title']) ? (string) $event['title'] : get_the_title($event_id);
$event_flag = !empty($event['flag']) ? (string) $event['flag'] : '';

$country_title = '';
$country_id = 0;
if (function_exists('get_field')) {
  $country_val = get_field('tour_country', $event_id);
  if ($country_val instanceof WP_Post) {
    $country_id = (int) $country_val->ID;
  } elseif (is_array($country_val)) {
    $country_id = (int) reset($country_val);
  } else {
    $country_id = (int) $country_val;
  }
  if ($country_id) {
    $country_title = get_the_title($country_id);
    if (!$event_flag && function_exists('get_field')) {
      $flag_field = get_field('flag', $country_id);
      if ($flag_field) {
        if (is_array($flag_field) && !empty($flag_field['url'])) {
          $event_flag = (string) $flag_field['url'];
        } elseif (is_string($flag_field)) {
          $event_flag = (string) $flag_field;
        }
      }
    }
  }
}

$resort_terms = wp_get_post_terms($event_id, 'resort', ['orderby' => 'name', 'order' => 'ASC']);
$resort_titles = [];
if (!is_wp_error($resort_terms) && !empty($resort_terms)) {
  $resort_titles = array_map(function ($term) {
    return $term->name;
  }, $resort_terms);
}

$event_includes = [];
$include_terms = wp_get_post_terms($event_id, 'tour_include', ['orderby' => 'name', 'order' => 'ASC']);
if (!is_wp_error($include_terms) && !empty($include_terms)) {
  $event_includes = array_slice($include_terms, 0, 6);
}

// Get tickets
$event_tickets = function_exists('get_field') ? get_field('event_tickets', $event_id) : [];
$min_ticket_price = 0;
if (!empty($event_tickets) && is_array($event_tickets)) {
  $prices = array_map(function ($ticket) {
    return isset($ticket['ticket_price']) ? (int) $ticket['ticket_price'] : 0;
  }, $event_tickets);
  $prices = array_filter($prices);
  $min_ticket_price = !empty($prices) ? min($prices) : 0;
}

$event_venue = '';
$event_time = '';
$checkin_dates = '';

if (function_exists('get_field')) {
  if ($min_ticket_price > 0) {
    $price_value = 'от ' . number_format($min_ticket_price, 0, '.', ' ') . ' ₽';
  } else {
    $price_val = get_field('price_from', $event_id);
    if (is_numeric($price_val)) {
      $price_value = 'от ' . number_format((float) $price_val, 0, '.', ' ') . ' ₽';
    } elseif (is_string($price_val) && $price_val !== '') {
      $price_value = (string) $price_val;
    }
  }

  $checkin_dates_val = get_field('tour_checkin_dates', $event_id);
  if (is_string($checkin_dates_val) && $checkin_dates_val !== '') {
    $checkin_dates = function_exists('format_dates_string_russian')
      ? format_dates_string_russian($checkin_dates_val)
      : $checkin_dates_val;
  }

  $event_venue = trim((string) get_field('event_venue', $event_id));
  $event_time = trim((string) get_field('event_time', $event_id));

  $booking_url_val = get_field('tour_booking_url', $event_id);
  if (is_string($booking_url_val) && $booking_url_val !== '') {
    $booking_url = (string) $booking_url_val;
  }
}
?>
<div class="hotel-card tour-card">
  <a href="<?php echo esc_url($event_url); ?>" class="hotel-card__media">
    <img src="<?php echo esc_url($event_image); ?>" alt="<?php echo esc_attr($event_title); ?>" class="hotel-card__image">
  </a>

  <div class="hotel-card__body">
    <div class="hotel-card__location tour-card__location">
      <?php if ($event_flag): ?>
        <div class="hotel-card__flag">
          <img src="<?php echo esc_url($event_flag); ?>" alt="">
        </div>
      <?php endif; ?>
      <?php if ($country_title): ?>
        <div class="hotel-card__location-text">
          <?php echo esc_html($country_title); ?>
        </div>
      <?php endif; ?>
    </div>
    <h3 class="hotel-card__title"><?php echo esc_html($event_title); ?></h3>

    <?php if (!empty($event_includes)): ?>
      <div class="hotel-card__includes tour-card__includes">
        <div class="tour-card__anemeties">
          <?php foreach ($event_includes as $term): ?>
            <?php
            $icon_url = '';
            if (function_exists('get_field')) {
              $icon = get_field('tour_include_icon', 'term_' . $term->term_id);
              if (is_array($icon) && !empty($icon['url'])) {
                $icon_url = (string) $icon['url'];
              } elseif (is_string($icon) && $icon !== '') {
                $icon_url = (string) $icon;
              } elseif (is_numeric($icon)) {
                $tmp = wp_get_attachment_image_url((int) $icon, 'thumbnail');
                if ($tmp) {
                  $icon_url = (string) $tmp;
                }
              }
            }

            if (!$icon_url) {
              $meta = get_term_meta($term->term_id, 'tour_include_icon', true);
              if (is_array($meta) && !empty($meta['url'])) {
                $icon_url = (string) $meta['url'];
              } elseif (is_string($meta) && $meta !== '') {
                $icon_url = (string) $meta;
              } elseif (is_numeric($meta)) {
                $tmp = wp_get_attachment_image_url((int) $meta, 'thumbnail');
                if ($tmp) {
                  $icon_url = (string) $tmp;
                }
              }
            }
            ?>

            <?php if ($icon_url): ?>
              <span class="hotel-card__anemetie" title="<?php echo esc_attr($term->name); ?>">
                <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
              </span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>



    <?php if ($event_venue || $event_time || $checkin_dates): ?>
      <div class="tour-card__booking-info">
        <?php if ($checkin_dates): ?>
          <span class="hotel-card__checkin-date">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:-2px;margin-right:4px"><rect x="3" y="4" width="18" height="18" rx="2" stroke="#999" stroke-width="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke="#999" stroke-width="2" stroke-linecap="round"/></svg>
            <?php echo esc_html($checkin_dates); ?>
          </span>
        <?php endif; ?>
        <?php if ($event_venue): ?>
          <span class="tour-card__venue">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:-2px;margin-right:4px"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="#999" stroke-width="2"/><circle cx="12" cy="9" r="2.5" stroke="#999" stroke-width="2"/></svg>
            <?php echo esc_html($event_venue); ?>
          </span>
        <?php endif; ?>
        <?php if ($event_time): ?>
          <span class="tour-card__time">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:-2px;margin-right:4px"><path d="M12 6v6l4 2" stroke="#999" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="10" stroke="#999" stroke-width="2"/></svg>
            <?php echo esc_html($event_time); ?>
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="hotel-card__actions">
      <a href="<?php echo esc_url($event_url); ?>" class="hotel-card__btn hotel-card__btn-details">
        Подробнее
      </a>
      <a href="<?php echo esc_url($booking_url ?: $event_url); ?>"
         class="btn btn-accent hotel-card__btn hotel-card__btn-book"
         <?php echo $booking_url ? 'target="_blank" rel="noopener nofollow"' : ''; ?>>
         <?php echo $price_value ? esc_html($price_value) : 'Забронировать'; ?>
      </a>
    </div>
  </div>
</div>
