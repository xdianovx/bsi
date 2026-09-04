<?php
/**
 * Карточка отеля из хаба BSIHOTELS.
 *
 * @var array $args['hotel']        элемент /v1/hotels
 * @var string $args['country_url'] база каталога страны, /country/{slug}/hotel/
 */

$hotel = $args['hotel'] ?? [];
$country_url = (string) ($args['country_url'] ?? '');

if (!is_array($hotel) || empty($hotel['name'])) {
  return;
}

$url = $country_url !== '' ? bsi_hotels_api_hotel_url($country_url, $hotel) : '';
$photo = (string) ($hotel['photo'] ?? '');
$stars = (int) ($hotel['stars'] ?? 0);
$city = (string) ($hotel['city']['name'] ?? '');
$price = bsi_hotels_api_format_price($hotel['price_from'] ?? null);
$rooms = (int) ($hotel['room_types'] ?? 0);
?>

<article class="api-hotel">
  <div class="api-hotel__media">
    <?php if ($photo): ?>
      <?php if ($url): ?><a href="<?= esc_url($url); ?>" class="api-hotel__media-link"><?php endif; ?>
        <img src="<?= esc_url($photo); ?>"
             alt="<?= esc_attr($hotel['name']); ?>"
             loading="lazy"
             decoding="async">
      <?php if ($url): ?></a><?php endif; ?>
    <?php else: ?>
      <div class="api-hotel__media-empty">Фото скоро появится</div>
    <?php endif; ?>
  </div>

  <div class="api-hotel__body">
    <h3 class="api-hotel__title">
      <?php if ($url): ?>
        <a href="<?= esc_url($url); ?>"><?= esc_html($hotel['name']); ?></a>
      <?php else: ?>
        <?= esc_html($hotel['name']); ?>
      <?php endif; ?>
      <?php if ($stars): ?>
        <span class="hotel-rating api-hotel__stars"><?= (int) $stars; ?>*</span>
      <?php endif; ?>
    </h3>

    <?php if ($city): ?>
      <p class="api-hotel__city"><?= esc_html($city); ?></p>
    <?php endif; ?>

    <div class="api-hotel__footer">
      <?php if ($price): ?>
        <span class="api-hotel__price">от <?= esc_html($price); ?> за ночь</span>
      <?php elseif ($rooms): ?>
        <span class="api-hotel__price api-hotel__price--empty">Цену уточним по запросу</span>
      <?php endif; ?>
    </div>
  </div>
</article>
