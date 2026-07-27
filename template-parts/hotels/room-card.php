<?php
/**
 * Карточка номера отеля.
 * Ожидает query vars:
 *  - room  (array) строка repeater'а hotel_rooms
 *  - room_index (int) индекс для fancybox-группы
 *  - room_booking_url (string) ссылка бронирования (опционально)
 */
$room = get_query_var('room');
$room_index = (int) get_query_var('room_index');
$booking_url = (string) get_query_var('room_booking_url');

if (!is_array($room)) {
  return;
}

$gallery = isset($room['gallery']) && is_array($room['gallery']) ? $room['gallery'] : [];
$name = isset($room['name']) ? (string) $room['name'] : '';
$area = isset($room['area']) ? $room['area'] : '';
$guests = isset($room['guests']) ? (string) $room['guests'] : '';
$price_from = isset($room['price_from']) ? $room['price_from'] : '';
$price_currency = !empty($room['price_currency']) ? strtoupper((string) $room['price_currency']) : 'RUB';
$price_rub = ($price_from && function_exists('bsi_education_convert_price_to_rub'))
  ? bsi_education_convert_price_to_rub($price_from, $price_currency)
  : null;
$description = isset($room['description']) ? (string) $room['description'] : '';
$amenities = isset($room['amenities']) && is_array($room['amenities']) ? $room['amenities'] : [];

$fb_group = 'hotel-room-' . $room_index;
?>

<article class="room-card">
  <?php if ($gallery): ?>
    <div class="room-card__slider">
      <div class="room-card__slider-swiper swiper">
        <div class="swiper-wrapper">
          <?php foreach ($gallery as $img): ?>
            <div class="swiper-slide">
              <a href="<?= esc_url($img['url']); ?>"
                 class="room-card__photo"
                 data-fancybox="<?= esc_attr($fb_group); ?>"
                 aria-label="<?= esc_attr($name ?: 'Фото номера'); ?>">
                <img src="<?= esc_url($img['sizes']['medium_large'] ?? $img['url']); ?>"
                     alt="<?= esc_attr($img['alt'] ?: $name); ?>"
                     loading="lazy">
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if (count($gallery) > 1): ?>
        <div class="room-card__slider-nav">
          <button type="button" class="room-card__slider-prev" aria-label="Предыдущее фото">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
          </button>
          <button type="button" class="room-card__slider-next" aria-label="Следующее фото">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
          </button>
        </div>
        <div class="room-card__slider-pagination"></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="room-card__body">
    <?php if ($area || $guests): ?>
      <div class="room-card__meta">
        <?php if ($area): ?>
          <span class="room-card__meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>
            <?= esc_html($area); ?> м²
          </span>
        <?php endif; ?>
        <?php if ($guests): ?>
          <span class="room-card__meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <?= esc_html($guests); ?> гостей
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($name): ?>
      <h3 class="h3 room-card__title"><?= esc_html($name); ?></h3>
    <?php endif; ?>

    <?php if ($description): ?>
      <div class="room-card__description"><?= esc_html($description); ?></div>
    <?php endif; ?>

    <?php if ($amenities):
      $amenities_visible = array_slice($amenities, 0, 4);
      $amenities_hidden_count = count($amenities) - count($amenities_visible);
      ?>
      <ul class="room-card__amenities">
        <?php foreach ($amenities_visible as $amenity_id):
          $term = get_term((int) $amenity_id, 'amenity');
          if (!$term || is_wp_error($term)) {
            continue;
          }
          $icon = function_exists('get_field') ? get_field('amenity_icon', 'term_' . $term->term_id) : null;
          $icon_url = is_array($icon) && !empty($icon['url']) ? $icon['url'] : '';
          if (!$icon_url) {
            continue;
          }
          ?>
          <li class="room-card__amenity" aria-label="<?= esc_attr($term->name); ?>" title="<?= esc_attr($term->name); ?>">
            <img class="room-card__amenity-icon" src="<?= esc_url($icon_url); ?>" alt="" loading="lazy">
          </li>
        <?php endforeach; ?>
        <?php if ($amenities_hidden_count > 0): ?>
          <li class="room-card__amenity room-card__amenity--more">+<?= (int) $amenities_hidden_count; ?></li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>

    <?php if ($price_rub || $booking_url): ?>
      <div class="room-card__bottom">
        <?php if ($price_rub): ?>
          <div class="room-card__price">
            <span class="room-card__price-value numfont js-hotel-room-price"
                  data-price-rub="<?= esc_attr($price_rub); ?>"
                  <?php if ($price_currency !== 'RUB'): ?>
                  data-price-original="<?= esc_attr($price_from); ?>"
                  data-price-currency="<?= esc_attr($price_currency); ?>"
                  <?php endif; ?>
            ><?= esc_html(format_number($price_rub)); ?> ₽</span>
          </div>
        <?php endif; ?>
        <?php if ($booking_url): ?>
          <a href="<?= esc_url($booking_url); ?>"
             class="btn btn-accent room-card__btn"
             target="_blank"
             rel="noopener nofollow">Забронировать</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</article>
