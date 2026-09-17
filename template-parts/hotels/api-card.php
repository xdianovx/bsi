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
      <div class="api-hotel__media-empty" title="Фото пока нет"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.564 14.558a3 3 0 1 1-4.122-4.121"/><path d="m2 2 20 20"/><path d="M20 20H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 .819-.175"/><path d="M9.695 4.024A2 2 0 0 1 10.004 4h3.993a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v7.344"/></svg></div>
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

    <?php
    /* Вид объекта — отдельная ось от звёзд: «Бутик-отель» и 5* не заменяют
       друг друга. Хаб проставил его не всем, поэтому строка условная. */
    $type = (string) ($hotel['type']['name'] ?? '');
    $place_line = trim(implode(' · ', array_filter([$city, $type])));
    ?>
    <?php if ($place_line !== ''): ?>
      <p class="api-hotel__city"><?= esc_html($place_line); ?></p>
    <?php endif; ?>

    <div class="api-hotel__footer">
      <?php if ($price): ?>
        <span class="api-hotel__price">от <?= esc_html($price); ?> за ночь</span>
      <?php elseif ($rooms): ?>
        <span class="api-hotel__price api-hotel__price--empty">По запросу</span>
      <?php endif; ?>
    </div>
  </div>
</article>
