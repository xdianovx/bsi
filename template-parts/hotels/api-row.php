<?php
/**
 * Карточка отеля строкой — для каталога с картой: фото слева, описание в центре,
 * цена и кнопка справа.
 *
 * @var array  $args['hotel']       элемент /v1/hotels
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
$beach_line = (int) ($hotel['beach_line'] ?? 0);
$type = (string) ($hotel['type']['name'] ?? '');
$id = (string) ($hotel['id'] ?? '');
?>

<article class="api-row" data-hotel="<?= esc_attr($id); ?>">
  <div class="api-row__media">
    <?php if ($photo): ?>
      <?php if ($url): ?><a href="<?= esc_url($url); ?>"><?php endif; ?>
        <img src="<?= esc_url($photo); ?>" alt="<?= esc_attr($hotel['name']); ?>" loading="lazy" decoding="async">
      <?php if ($url): ?></a><?php endif; ?>
    <?php else: ?>
      <span class="api-row__media-empty">Фото скоро появится</span>
    <?php endif; ?>
  </div>

  <div class="api-row__body">
    <h3 class="api-row__title">
      <?php if ($url): ?>
        <a href="<?= esc_url($url); ?>"><?= esc_html($hotel['name']); ?></a>
      <?php else: ?>
        <?= esc_html($hotel['name']); ?>
      <?php endif; ?>

      <?php if ($stars): ?>
        <span class="api-row__stars">
          <?php for ($i = 0; $i < $stars; $i++): ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>
          <?php endfor; ?>
        </span>
      <?php endif; ?>
    </h3>

    <?php if ($city !== '' || $type !== ''): ?>
      <p class="api-row__place">
        <?= esc_html(implode(' · ', array_filter([$city, $type]))); ?>
      </p>
    <?php endif; ?>

    <ul class="api-row__facts">
      <?php if ($beach_line): ?>
        <li><?= (int) $beach_line; ?>-я линия пляжа</li>
      <?php endif; ?>
      <?php if (!empty($hotel['adults_only'])): ?>
        <li>Только для взрослых</li>
      <?php endif; ?>
      <?php if ($rooms): ?>
        <li><?= (int) $rooms; ?> <?= bsi_plural_ru($rooms, 'категория', 'категории', 'категорий'); ?> номеров</li>
      <?php endif; ?>
    </ul>

    <div class="api-row__footer">
      <div class="api-row__price-wrap">
        <?php if ($price !== ''): ?>
          <span class="api-row__price-label">Цена за ночь от</span>
          <span class="api-row__price"><?= esc_html($price); ?></span>
        <?php else: ?>
          <span class="api-row__price-empty">Цену уточним по запросу</span>
        <?php endif; ?>
      </div>

      <?php if ($url): ?>
        <a class="btn btn-accent sm api-row__cta" href="<?= esc_url($url); ?>">Забронировать</a>
      <?php endif; ?>
    </div>
  </div>
</article>
