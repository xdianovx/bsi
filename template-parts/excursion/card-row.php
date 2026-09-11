<?php
/**
 * Карточка экскурсии (CPT excursion) — каталог / страна / AJAX.
 *
 *   get_template_part('template-parts/excursion/card-row', null, ['post_id' => $excursion_id]);
 *
 * Цена: data-атрибуты `.js-excursion-price` подхватываются обработчиком
 * EducationCurrencySwitcher (js/modules/education-currency-switcher.js).
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$country_id = function_exists('bsi_get_excursion_country_id') ? bsi_get_excursion_country_id($post_id) : 0;
$country_title = $country_id ? get_the_title($country_id) : '';
$flag_url = ($country_id && function_exists('bsi_get_country_flag_url'))
  ? bsi_get_country_flag_url($country_id)
  : '';

$resorts = get_the_terms($post_id, 'resort');
$resort_title = (!is_wp_error($resorts) && !empty($resorts)) ? $resorts[0]->name : '';

/* Fallback: если resort не выбран, используем регион в location-строке */
$region_title = '';
if ($resort_title === '') {
  $regions = get_the_terms($post_id, 'region');
  if (!is_wp_error($regions) && !empty($regions)) {
    $region_title = (string) $regions[0]->name;
  }
}

$duration_label = function_exists('bsi_get_excursion_duration_label') ? bsi_get_excursion_duration_label($post_id) : '';
$dates_text = function_exists('bsi_get_excursion_dates_text') ? bsi_get_excursion_dates_text($post_id) : '';
$booking_url = function_exists('bsi_get_excursion_booking_url') ? bsi_get_excursion_booking_url($post_id) : '';

$price_rub = function_exists('bsi_get_excursion_price_from_rub') ? bsi_get_excursion_price_from_rub($post_id) : null;
$price_original_data = function_exists('bsi_get_excursion_price_from_original')
  ? bsi_get_excursion_price_from_original($post_id)
  : ['amount' => null, 'currency' => null];

$gallery = function_exists('get_field') ? (array) get_field('excursion_gallery', $post_id) : [];
$img = get_the_post_thumbnail_url($post_id, 'large');
if (!$img && !empty($gallery)) {
  $first = $gallery[0] ?? null;
  if (is_array($first)) {
    if (!empty($first['sizes']['large'])) {
      $img = $first['sizes']['large'];
    } elseif (!empty($first['url'])) {
      $img = $first['url'];
    }
  }
}

$link = get_permalink($post_id);
$title = get_the_title($post_id);

$location_parts = [];
if ($country_title !== '') {
  $location_parts[] = $country_title;
}
if ($resort_title !== '') {
  $location_parts[] = $resort_title;
} elseif ($region_title !== '') {
  $location_parts[] = $region_title;
}
$location_line = implode(', ', $location_parts);

$excerpt_raw = (string) get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
if ($excerpt_raw === '') {
  $excerpt_raw = (string) get_post_meta($post_id, '_genesis_description', true);
}
if ($excerpt_raw === '' && has_excerpt($post_id)) {
  $excerpt_raw = wp_strip_all_tags(get_the_excerpt($post_id));
}
$excerpt_raw = preg_replace('/\s+/u', ' ', trim(wp_strip_all_tags($excerpt_raw)));

?>

<article class="catalog-card catalog-card--excursion">
  <a href="<?= esc_url($link); ?>" class="catalog-card__media">
    <?php if ($img): ?>
      <img class="catalog-card__img" src="<?= esc_url($img); ?>" alt="<?= esc_attr($title); ?>" loading="lazy">
    <?php else: ?>
      <span class="catalog-card__img-placeholder" role="img" aria-label=""></span>
    <?php endif; ?>
  </a>

  <div class="catalog-card__body">
    <?php if ($location_line !== ''): ?>
      <div class="catalog-card__location">
        <?php if ($flag_url !== ''): ?>
          <span class="catalog-card__flag">
            <img src="<?= esc_url($flag_url); ?>" alt="" loading="lazy">
          </span>
        <?php endif; ?>
        <div class="catalog-card__location-text">
          <?php if ($country_title !== ''): ?>
            <?= esc_html($country_title); ?><?php if ($resort_title !== '' || $region_title !== ''): ?>,<?php endif; ?>
          <?php endif; ?>
          <?php if ($resort_title !== ''): ?>
            <span><?= esc_html($resort_title); ?></span>
          <?php elseif ($region_title !== ''): ?>
            <span><?= esc_html($region_title); ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <h3 class="catalog-card__title">
      <a href="<?= esc_url($link); ?>"><?= esc_html($title); ?></a>
    </h3>

    <?php if ($excerpt_raw !== ''): ?>
      <p class="catalog-card__excerpt"><?= esc_html($excerpt_raw); ?></p>
    <?php endif; ?>

    <?php if ($duration_label !== '' || $dates_text !== ''): ?>
      <div class="catalog-card__info-row">
        <?php if ($duration_label !== ''): ?>
          <span class="catalog-card__duration">Длительность: <?= esc_html($duration_label); ?></span>
        <?php endif; ?>
        <?php if ($dates_text !== ''): ?>
          <span class="catalog-card__dates">Даты: <?= esc_html($dates_text); ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="catalog-card__actions">
      <a href="<?= esc_url($link); ?>" class="btn btn-gray sm catalog-card__btn">
        Подробнее
      </a>
      <?php if ($booking_url !== ''): ?>
        <?php $has_price = ($price_rub !== null && $price_rub > 0); ?>
        <a href="<?= esc_url($booking_url); ?>"
           class="btn btn-accent sm catalog-card__btn<?= $has_price ? ' js-excursion-price' : ''; ?>"
           target="_blank" rel="nofollow noopener"
           <?php if ($has_price): ?>
           data-price-rub="<?= esc_attr((string) (int) $price_rub); ?>"
           <?php if (!empty($price_original_data['amount']) && !empty($price_original_data['currency'])): ?>
           data-price-original="<?= esc_attr((string) $price_original_data['amount']); ?>"
           data-price-currency="<?= esc_attr((string) $price_original_data['currency']); ?>"
           <?php endif; ?>
           data-has-from="true"
           <?php endif; ?>><?= $has_price
             ? esc_html('от ' . number_format((int) $price_rub, 0, ',', ' ') . ' ₽')
             : 'Забронировать'; ?></a>
      <?php elseif ($price_rub !== null && $price_rub > 0): ?>
        <button type="button"
                class="btn btn-accent sm catalog-card__btn js-excursion-booking-btn js-excursion-price"
                data-excursion-id="<?= esc_attr((string) $post_id); ?>"
                data-excursion-title="<?= esc_attr($title); ?>"
                data-price-rub="<?= esc_attr((string) (int) $price_rub); ?>"
                <?php if (!empty($price_original_data['amount']) && !empty($price_original_data['currency'])): ?>
                data-price-original="<?= esc_attr((string) $price_original_data['amount']); ?>"
                data-price-currency="<?= esc_attr((string) $price_original_data['currency']); ?>"
                <?php endif; ?>
                data-has-from="true">от <?= esc_html(number_format((int) $price_rub, 0, ',', ' ')); ?> ₽</button>
      <?php else: ?>
        <button type="button"
                class="btn btn-accent sm catalog-card__btn js-excursion-booking-btn"
                data-excursion-id="<?= esc_attr((string) $post_id); ?>"
                data-excursion-title="<?= esc_attr($title); ?>">Забронировать</button>
      <?php endif; ?>
    </div>
  </div>
</article>
