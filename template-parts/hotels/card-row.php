<?php
/**
 * Hotel Card Row
 * template-parts/hotels/card-row.php
 *
 * Использование:
 * get_template_part('template-parts/hotels/card-row', null, [
 *   'hotel_id' => $hotel_id,
 *   // опционально:
 *   'link' => '',
 * ]);
 */

$hotel_id = (int) ($args['hotel_id'] ?? get_the_ID());
if (!$hotel_id)
  return;

$link = $args['link'] ?? get_permalink($hotel_id);

// базовые
$title = get_the_title($hotel_id);
$thumb = get_the_post_thumbnail_url($hotel_id, 'medium') ?: '';
$excerpt = get_the_excerpt($hotel_id);

// ACF (под твои поля)
$rating = function_exists('get_field') ? get_field('rating', $hotel_id) : '';
$address = function_exists('get_field') ? get_field('address', $hotel_id) : '';
$phone = function_exists('get_field') ? get_field('phone', $hotel_id) : '';
$website = function_exists('get_field') ? get_field('website', $hotel_id) : '';
$price = function_exists('get_field') ? get_field('price', $hotel_id) : '';
$check_in = function_exists('get_field') ? get_field('check_in_time', $hotel_id) : '';
$check_out = function_exists('get_field') ? get_field('check_out_time', $hotel_id) : '';
$wifi = function_exists('get_field') ? get_field('wifi', $hotel_id) : '';
$breakfast = function_exists('get_field') ? get_field('breakfast', $hotel_id) : '';

// GEO (если нужно)
$country_id = function_exists('get_field') ? get_field('hotel_country', $hotel_id) : 0;
$region_id = function_exists('get_field') ? get_field('hotel_region', $hotel_id) : 0;
$resort_id = function_exists('get_field') ? get_field('hotel_resort', $hotel_id) : 0;

// нормализуем taxonomy id (если вдруг массив)
if (is_array($region_id))
  $region_id = reset($region_id);
if (is_array($resort_id))
  $resort_id = reset($resort_id);

$country_title = $country_id ? get_the_title($country_id) : '';
$region_title = $region_id ? (get_term((int) $region_id, 'region')->name ?? '') : '';
$resort_title = $resort_id ? (get_term((int) $resort_id, 'resort')->name ?? '') : '';
?>

<article class="hotel-card-row"
         data-hotel-id="<?= esc_attr($hotel_id); ?>">
  <div class="hotel-card-row__wrap">

    <!-- Poster -->
    <div class="hotel-card-row__media">
      <div class="hotel-card-row__poster">
        <?php if ($thumb): ?>
          <img class="hotel-card-row__img"
               src="<?= esc_url($thumb); ?>"
               alt="<?= esc_attr($title); ?>">
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
      <div class="hotel-card-row__title"><?= esc_html($title); ?></div>

      <div class="hotel-card-row__geo">
        <div class="hotel-card-row__country"><?= esc_html((string) $country_title); ?></div>
        <div class="hotel-card-row__region"><?= esc_html((string) $region_title); ?></div>
        <div class="hotel-card-row__resort"><?= esc_html((string) $resort_title); ?></div>
      </div>

      <div class="hotel-card-row__excerpt">
        <?= esc_html((string) $excerpt); ?>
      </div>

      <div class="hotel-card-row__meta">
        <div class="hotel-card-row__address"><?= esc_html((string) $address); ?></div>
        <div class="hotel-card-row__phone"><?= esc_html((string) $phone); ?></div>
        <div class="hotel-card-row__website"><?= esc_html((string) $website); ?></div>
        <div class="hotel-card-row__price"><?= esc_html((string) $price); ?></div>
      </div>

      <div class="hotel-card-row__details">
        <div class="hotel-card-row__checkin"><?= esc_html((string) $check_in); ?></div>
        <div class="hotel-card-row__checkout"><?= esc_html((string) $check_out); ?></div>
        <div class="hotel-card-row__wifi"><?= esc_html((string) $wifi); ?></div>
        <div class="hotel-card-row__breakfast"><?= esc_html((string) $breakfast); ?></div>
      </div>

    </div>

    <!-- CTA -->
    <div class="hotel-card-cta">

      <div class="hotel-card-cta__price numfont">от 125 000 руб</div>
      <div class="hotel-card__buttons">
        <a href="<?= esc_url($link); ?>"
           class="btn btn-gray sm hotel-card-button --more">
          Подробнее
        </a>

        <a href="<?= esc_url($link); ?>"
           class="btn btn-accent sm hotel-card-button --more">
          Забронировать
        </a>
      </div>
    </div>
  </div>
</article>