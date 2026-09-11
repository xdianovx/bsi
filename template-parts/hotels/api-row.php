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
$beach_line = (int) ($hotel['beach_line'] ?? 0);
$type = (string) ($hotel['type']['name'] ?? '');
$id = (string) ($hotel['id'] ?? '');

/* Короткие факты об отеле. Счётчика категорий номеров тут нет намеренно:
   он ничего не говорит о самом отеле и занимал место под удобства. */
$facts = [];
if ($beach_line) {
  $facts[] = $beach_line . '-я линия пляжа';
}
if (!empty($hotel['adults_only'])) {
  $facts[] = 'Только для взрослых';
}

/* Удобства: сначала популярные. Список даёт их не всегда — /v1/hotels отдаёт
   отель без удобств, поэтому строка появляется, только когда они пришли. */
$amenities = is_array($hotel['amenities'] ?? null) ? $hotel['amenities'] : [];
usort($amenities, static fn($a, $b) => (int) !empty($b['is_popular']) <=> (int) !empty($a['is_popular']));
$more = max(0, count($amenities) - 8);
$amenities = array_map(
  static fn(array $a) => [
    'name' => (string) ($a['name'] ?? ''),
    'icon' => (string) ($a['icon'] ?? ''),
  ],
  array_slice($amenities, 0, 8)
);
$amenities = array_values(array_filter($amenities, static fn($a) => $a['name'] !== ''));

$popular = !empty($hotel['is_popular']);

/* Список отелей мгновенного подтверждения не отмечает — такого поля в выдаче
   нет. Но когда включён фильтр «Мгновенное подтверждение», в выдаче только
   такие отели, и бейдж честен для всей страницы. */
$instant = !empty($args['instant']);

/* Цены отеля прямо сейчас обновляются в хабе: показываем это вместо пустого
   места, иначе «Цену уточним по запросу» выглядит как окончательный ответ. */
$prices_updating = ($hotel['prices_status'] ?? '') === 'updating';
?>

<article class="api-row" data-hotel="<?= esc_attr($id); ?>">
  <div class="api-row__media">
    <?php if ($popular): ?>
      <span class="api-row__badge">Популярный</span>
    <?php endif; ?>

    <?php if ($instant): ?>
      <span class="api-row__badge api-row__badge--instant">Мгновенное подтверждение</span>
    <?php endif; ?>

    <?php if ($photo): ?>
      <?php if ($url): ?><a href="<?= esc_url($url); ?>"><?php endif; ?>
        <img src="<?= esc_url($photo); ?>" alt="<?= esc_attr($hotel['name']); ?>" loading="lazy" decoding="async">
      <?php if ($url): ?></a><?php endif; ?>
    <?php else: ?>
      <span class="api-row__media-empty" title="Фото пока нет"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.564 14.558a3 3 0 1 1-4.122-4.121"/><path d="m2 2 20 20"/><path d="M20 20H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 .819-.175"/><path d="M9.695 4.024A2 2 0 0 1 10.004 4h3.993a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v7.344"/></svg></span>
    <?php endif; ?>
  </div>

  <div class="api-row__body">
    <?php /* Верхняя строка карточки: звёздность слева, место у правого края.
             Звёздность всегда пятью иконками — закрашены свои, остальные серые,
             так «3 из 5» читается без цифры. */ ?>
    <div class="api-row__meta">
      <?php if ($stars): ?>
        <span class="api-row__stars" title="<?= (int) $stars; ?> из 5">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <?= sprintf('<svg class="api-row__star%s" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>', $i <= $stars ? ' is-on' : ''); ?>
          <?php endfor; ?>
        </span>
      <?php endif; ?>

      <?php if ($city !== '' || $type !== ''): ?>
        <span class="api-row__place">
          <?= esc_html(implode(' · ', array_filter([$city, $type]))); ?>
        </span>
      <?php endif; ?>
    </div>

    <h3 class="api-row__title">
      <?php if ($url): ?>
        <a href="<?= esc_url($url); ?>"><?= esc_html($hotel['name']); ?></a>
      <?php else: ?>
        <?= esc_html($hotel['name']); ?>
      <?php endif; ?>
    </h3>

    <?php if ($facts || $amenities): ?>
      <ul class="api-row__facts">
        <?php foreach ($facts as $fact): ?>
          <li class="api-row__fact"><?= esc_html($fact); ?></li>
        <?php endforeach; ?>

        <?php /* Удобства — одними иконками: подписи занимали две строки, а
                 название нужно редко и приходит подсказкой при наведении. */ ?>
        <?php foreach ($amenities as $amenity): ?>
          <li class="api-row__amenity" title="<?= esc_attr($amenity['name']); ?>">
            <?php if ($amenity['icon']): ?>
              <img src="<?= esc_url($amenity['icon']); ?>" alt="<?= esc_attr($amenity['name']); ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <?= esc_html($amenity['name']); ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>

        <?php if ($more > 0): ?>
          <li class="api-row__amenity api-row__amenity--more">ещё <?= (int) $more; ?></li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>

    <div class="api-row__footer">
      <div class="api-row__price-wrap">
        <?php if ($price !== ''): ?>
          <span class="api-row__price">от <?= esc_html($price); ?></span>
          <span class="api-row__price-label">за ночь</span>
        <?php elseif ($prices_updating): ?>
          <span class="api-row__price-loading">
            <span class="api-row__price-spinner" aria-hidden="true"></span>
            Считаем цену
          </span>
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
