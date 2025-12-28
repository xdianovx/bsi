<?php
$tour = get_query_var('tour');
if (!$tour || !is_array($tour)) {
  return;
}

$tour_id = 0;
$tour_url = '';
$tour_image = '';
$tour_title = '';
$tour_flag = '';
$price_value = '';
$nights = 0;
$checkin_dates = '';
$excursions_count = 0;
$booking_url = '';

if (!empty($tour['id'])) {
  $tour_id = (int) $tour['id'];
} elseif (!empty($tour['url'])) {
  $tour_id = (int) url_to_postid($tour['url']);
}

if (!$tour_id) {
  return;
}

$tour_url = !empty($tour['url']) ? (string) $tour['url'] : get_permalink($tour_id);
$tour_image = !empty($tour['image']) ? (string) $tour['image'] : (get_the_post_thumbnail_url($tour_id, 'large') ?: '');
$tour_title = !empty($tour['title']) ? (string) $tour['title'] : get_the_title($tour_id);
$tour_flag = !empty($tour['flag']) ? (string) $tour['flag'] : '';

$country_title = '';
$country_id = 0;
if (function_exists('get_field')) {
  $country_val = get_field('tour_country', $tour_id);
  if ($country_val instanceof WP_Post) {
    $country_id = (int) $country_val->ID;
  } elseif (is_array($country_val)) {
    $country_id = (int) reset($country_val);
  } else {
    $country_id = (int) $country_val;
  }
  if ($country_id) {
    $country_title = get_the_title($country_id);
    if (!$tour_flag && function_exists('get_field')) {
      $flag_field = get_field('flag', $country_id);
      if ($flag_field) {
        if (is_array($flag_field) && !empty($flag_field['url'])) {
          $tour_flag = (string) $flag_field['url'];
        } elseif (is_string($flag_field)) {
          $tour_flag = (string) $flag_field;
        }
      }
    }
  }
}

$resort_terms = wp_get_post_terms($tour_id, 'resort', ['orderby' => 'name', 'order' => 'ASC']);
$resort_titles = [];
if (!is_wp_error($resort_terms) && !empty($resort_terms)) {
  $resort_titles = array_map(function ($term) {
    return $term->name;
  }, $resort_terms);
}

$tour_includes = [];
$include_terms = wp_get_post_terms($tour_id, 'tour_include', ['orderby' => 'name', 'order' => 'ASC']);
if (!is_wp_error($include_terms) && !empty($include_terms)) {
  $tour_includes = array_slice($include_terms, 0, 6);
}

if (function_exists('get_field')) {
  $price_val = get_field('price_from', $tour_id);
  if (is_numeric($price_val)) {
    $price_value = number_format((float) $price_val, 0, '.', ' ');
  } elseif (is_string($price_val) && $price_val !== '') {
    $price_value = (string) $price_val;
  }

  $nights_val = get_field('tour_nights', $tour_id);
  if (is_numeric($nights_val)) {
    $nights = (int) $nights_val;
  }

  $checkin_dates_val = get_field('tour_checkin_dates', $tour_id);
  if (is_string($checkin_dates_val) && $checkin_dates_val !== '') {
    $checkin_dates = (string) $checkin_dates_val;
  }

  $excursions_count_val = get_field('tour_excursions_count', $tour_id);
  if (is_numeric($excursions_count_val)) {
    $excursions_count = (int) $excursions_count_val;
  }

  $booking_url_val = get_field('tour_booking_url', $tour_id);
  if (is_string($booking_url_val) && $booking_url_val !== '') {
    $booking_url = (string) $booking_url_val;
  }
}
?>
<div class="hotel-card tour-card">
  <a href="<?php echo esc_url($tour_url); ?>" class="hotel-card__media">
    <img src="<?php echo esc_url($tour_image); ?>" alt="<?php echo esc_attr($tour_title); ?>" class="hotel-card__image">
  </a>

  <div class="hotel-card__body">
    <h3 class="hotel-card__title"><?php echo esc_html($tour_title); ?></h3>

    <?php if (!empty($tour_includes)): ?>
      <div class="hotel-card__includes tour-card__includes">
        <div class="tour-card__anemeties">
          <?php foreach ($tour_includes as $term): ?>
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

    <div class="hotel-card__location tour-card__location">
      <?php if ($tour_flag): ?>
        <div class="hotel-card__flag">
          <img src="<?php echo esc_url($tour_flag); ?>" alt="">
        </div>
      <?php endif; ?>
      <?php if ($country_title): ?>
        <div class="hotel-card__location-text">
          <?php echo esc_html($country_title); ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($nights > 0 || $checkin_dates || $excursions_count > 0): ?>
      <div class="tour-card__booking-info">
        <?php if ($nights > 0): ?>
          <span class="hotel-card__nights"><?php echo esc_html($nights); ?>
            <?php echo $nights === 1 ? 'ночь' : ($nights < 5 ? 'ночи' : 'ночей'); ?>,</span>
        <?php endif; ?>
        <?php if ($excursions_count > 0): ?>
          <?php if ($nights > 0): ?>     <?php endif; ?>
          <span class="tour-card__excursions">
            <?php echo esc_html($excursions_count); ?>
            <?php
            $excursions_text = '';
            $last_digit = $excursions_count % 10;
            $last_two_digits = $excursions_count % 100;
            if ($last_two_digits >= 11 && $last_two_digits <= 14) {
              $excursions_text = 'экскурсий';
            } elseif ($last_digit === 1) {
              $excursions_text = 'экскурсия';
            } elseif ($last_digit >= 2 && $last_digit <= 4) {
              $excursions_text = 'экскурсии';
            } else {
              $excursions_text = 'экскурсий';
            }
            echo esc_html($excursions_text);
            ?>,
          </span>
        <?php endif; ?>
        <?php if ($checkin_dates): ?>
          <?php if ($nights > 0 || $excursions_count > 0): ?>     <?php endif; ?>
          <span class="hotel-card__checkin-date"><?php echo esc_html($checkin_dates); ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="hotel-card__actions">
      <a href="<?php echo esc_url($tour_url); ?>" class="hotel-card__btn hotel-card__btn-details">
        Подробнее
      </a>
      <?php if ($booking_url && $price_value): ?>
        <a href="<?php echo esc_url($booking_url); ?>" class="btn btn-accent hotel-card__btn hotel-card__btn-book"
          target="_blank" rel="noopener nofollow">
          от <?php echo esc_html($price_value); ?> ₽
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>