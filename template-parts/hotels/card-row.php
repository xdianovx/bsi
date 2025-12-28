<?php

$hotel_id = (int) ($args['hotel_id'] ?? get_the_ID());
if (!$hotel_id)
  return;

$link = $args['link'] ?? get_permalink($hotel_id);

$booking_url = function_exists('get_field') ? get_field('booking_url', $hotel_id) : '';
$booking_url = trim((string) $booking_url);

$title = get_the_title($hotel_id);
$thumb = get_the_post_thumbnail_url($hotel_id, 'medium') ?: '';
$excerpt = get_the_excerpt($hotel_id);

$rating = function_exists('get_field') ? get_field('rating', $hotel_id) : '';
$address = function_exists('get_field') ? get_field('address', $hotel_id) : '';
$phone = function_exists('get_field') ? get_field('phone', $hotel_id) : '';
$website = function_exists('get_field') ? get_field('website', $hotel_id) : '';
$price = function_exists('get_field') ? get_field('price', $hotel_id) : '';
$show_from = function_exists('get_field') ? (bool) get_field('show_price_from', $hotel_id) : false;
$check_in = function_exists('get_field') ? get_field('check_in_time', $hotel_id) : '';
$check_out = function_exists('get_field') ? get_field('check_out_time', $hotel_id) : '';
$wifi = function_exists('get_field') ? get_field('wifi', $hotel_id) : '';
$breakfast = function_exists('get_field') ? get_field('breakfast', $hotel_id) : '';

$country_id = function_exists('get_field') ? get_field('hotel_country', $hotel_id) : 0;
$region_id = function_exists('get_field') ? get_field('hotel_region', $hotel_id) : 0;
$resort_id = function_exists('get_field') ? get_field('hotel_resort', $hotel_id) : 0;

if (is_array($region_id))
  $region_id = reset($region_id);
if (is_array($resort_id))
  $resort_id = reset($resort_id);

$country_title = $country_id ? get_the_title($country_id) : '';

$region_title = '';
if ($region_id) {
  $t = get_term((int) $region_id, 'region');
  $region_title = (!is_wp_error($t) && $t) ? ($t->name ?? '') : '';
}

$resort_title = '';
if ($resort_id) {
  $t = get_term((int) $resort_id, 'resort');
  $resort_title = (!is_wp_error($t) && $t) ? ($t->name ?? '') : '';
}

$phone = trim((string) $phone);
$tel_href = $phone ? preg_replace('/[^0-9+]/', '', $phone) : '';

$website = trim((string) $website);
if ($website && !preg_match('~^https?://~i', $website)) {
  $website = 'https://' . ltrim($website, '/');
}
$website_url = $website ? esc_url($website) : '';

$amenities = [];
if ($wifi)
  $amenities[] = 'Wi-Fi';
if ($breakfast)
  $amenities[] = 'Завтрак';
if ($check_in)
  $amenities[] = 'Заезд: ' . $check_in;
if ($check_out)
  $amenities[] = 'Выезд: ' . $check_out;

$price_text = trim((string) $price);
if (!$price_text)
  $price_text = '125 000 руб';
?>

<div class="hotel-card-row"
     data-hotel-id="<?= esc_attr($hotel_id); ?>">
  <div class="hotel-card-row__wrap">

    <!-- Poster -->
    <div class="hotel-card-row__media">
      <div class="hotel-card-row__poster">
        <?php if ($thumb): ?>
          <a href="<?= esc_url($link); ?>"
             class="hotel-card-row__poster-link">
            <img class="hotel-card-row__img"
                 src="<?= esc_url($thumb); ?>"
                 alt="<?= esc_attr($title); ?>">
          </a>
        <?php else: ?>
          <div class="hotel-card-row__img-placeholder"></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Info -->
    <div class="hotel-card-row__body">

      <div class="hotel-card-row__head">
        <?php if ($rating): ?>
          <div class="single-hotel__rating-stars hotel-card-row__rating rating-stars">
            <div class="stars-rating">
              <?php
              for ($i = 1; $i <= 5; $i++):
                if ($i <= $rating) {
                  echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star-icon lucide-star filled"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>';
                } else {
                  echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star-icon lucide-star"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>';
                }
              endfor;
              ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <a class="hotel-card-row__title"
         href="<?= esc_url($link); ?>">
        <?= esc_html($title); ?>
      </a>

      <div class="hotel-card-row__geo">
        <?php if ($address): ?>
          <div class="hotel-card-row__link hotel-card-row__link--address">
            <?= esc_html((string) $address); ?>
          </div>
        <?php endif; ?>

      </div>

      <?php if (!empty($excerpt)): ?>
        <div class="hotel-card-row__excerpt">
          <?= esc_html((string) $excerpt); ?>
        </div>
      <?php endif; ?>



      <?php if (!empty($amenities)): ?>
        <div class="hotel-card-row__amenities">
          <?php foreach ($amenities as $a): ?>
            <span class="hotel-card-row__amenity"><?= format_price_text(esc_html($a)); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>

    <!-- CTA -->
    <div class="hotel-card-cta">
      <div class="hotel-card-cta__price numfont"><?= format_price_text(format_price_with_from($price_text, $show_from)); ?></div>

      <div class="hotel-card__buttons">
        <a href="<?= esc_url($link); ?>"
           class="btn btn-gray sm hotel-card-button --more">
          Подробнее
        </a>

        <?php if (!empty($booking_url)): ?>
          <a href="<?= esc_url($booking_url); ?>"
             target="_blank"
             rel="nofollow noopener"
             class="btn btn-accent sm hotel-card-button --more">
            Забронировать
          </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>