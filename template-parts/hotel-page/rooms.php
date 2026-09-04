<?php

/**
 * Номера и тарифы — главный блок страницы.
 *
 * Панель подбора (дата заезда, ночи, питание) фильтрует тарифы на клиенте:
 * все офферы уже пришли вместе со страницей и лежат в JSON рядом со списком.
 * Без JS страница остаётся рабочей — сервер отдаёт тарифы ближайшего заезда.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
$rooms = $view['rooms'] ?? [];

if (!$rooms) {
  return;
}

$filters = bsi_hotel_view_offer_filters($rooms);
$default_date = $filters['dates'][0] ?? '';
$default_nights = 0;

foreach ($rooms as $room) {
  foreach ($room['offers'] as $offer) {
    if ($offer['check_in'] === $default_date && ($default_nights === 0 || $offer['nights'] < $default_nights)) {
      $default_nights = $offer['nights'];
    }
  }
}

$has_offers = (bool) $filters['dates'];

$all_request = true;
foreach ($rooms as $room) {
  foreach ($room['offers'] as $offer) {
    if ($offer['status'] !== 'request') {
      $all_request = false;
      break 2;
    }
  }
}

/** Тарифы номера под текущий фильтр, дешёвые сверху. */
$select_offers = static function (array $room) use ($default_date, $default_nights): array {
  $rows = array_filter(
    $room['offers'],
    static fn(array $offer) => $offer['check_in'] === $default_date && $offer['nights'] === $default_nights
  );

  usort($rows, static fn(array $a, array $b) => $a['price'] <=> $b['price']);

  return $rows;
};

$payload = array_map(static function (array $room): array {
  return [
    'id' => $room['id'],
    'offers' => array_map(static fn(array $offer) => [
      'date' => $offer['check_in'],
      'nights' => $offer['nights'],
      'meal' => $offer['meal'],
      'mealLabel' => $offer['meal_label'],
      'placementLabel' => $offer['placement_label'],
      'price' => bsi_hotel_view_price(['amount' => $offer['price'], 'currency' => $offer['currency']]),
      'priceValue' => $offer['price'],
      'status' => $offer['status'],
      'url' => $offer['booking_url'],
    ], $room['offers']),
  ];
}, $rooms);
?>

<section class="hp-rooms" id="hotel-rooms">
  <div class="container">
    <div class="hp-rooms__head">
      <h2 class="h2">Номера и цены</h2>
      <?php if ($has_offers && !$all_request): ?>
        <p class="hp-rooms__hint">Цены за размещение целиком, по данным туроператора</p>
      <?php endif; ?>
    </div>

    <?php if ($has_offers): ?>
      <form class="hp-search js-hotel-search"
            data-default-date="<?= esc_attr($default_date); ?>"
            data-default-nights="<?= esc_attr((string) $default_nights); ?>">
        <label class="hp-search__field">
          <span class="hp-search__label">Заезд</span>
          <select class="hp-search__select js-hotel-date" name="date">
            <?php foreach ($filters['dates'] as $date): ?>
              <option value="<?= esc_attr($date); ?>" <?php selected($date, $default_date); ?>>
                <?= esc_html(bsi_hotel_view_date_label($date)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="hp-search__field">
          <span class="hp-search__label">Длительность</span>
          <select class="hp-search__select js-hotel-nights" name="nights">
            <?php foreach ($filters['nights'] as $nights): ?>
              <option value="<?= esc_attr((string) $nights); ?>" <?php selected($nights, $default_nights); ?>>
                <?= esc_html(bsi_hotel_view_nights_label((int) $nights)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="hp-search__field">
          <span class="hp-search__label">Питание</span>
          <select class="hp-search__select js-hotel-meal" name="meal">
            <option value="">Любое</option>
            <?php foreach ($filters['meals'] as $code => $label): ?>
              <option value="<?= esc_attr($code); ?>"><?= esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <p class="hp-search__note js-hotel-search-note"></p>
      </form>
    <?php endif; ?>

    <div class="hp-rooms__list js-hotel-rooms" data-all-request="<?= $all_request ? '1' : '0'; ?>">
      <?php foreach ($rooms as $room):
        $offers = $select_offers($room);
        $photo = $room['photos'][0]['url'] ?? '';
        ?>
        <article class="hp-room" data-room="<?= esc_attr($room['id']); ?>">
          <div class="hp-room__info">
            <?php if ($photo): ?>
              <a class="hp-room__photo"
                 href="<?= esc_url($photo); ?>"
                 data-fancybox="room-<?= esc_attr($room['id']); ?>">
                <img src="<?= esc_url($photo); ?>" alt="<?= esc_attr($room['name']); ?>" loading="lazy" decoding="async">
              </a>
            <?php else: ?>
              <span class="hp-room__photo hp-room__photo--empty">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9V6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3"/><path d="M2 11h20v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z"/><path d="M6 11V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>
              </span>
            <?php endif; ?>

            <div class="hp-room__text">
              <h3 class="hp-room__title"><?= esc_html($room['name']); ?></h3>

              <ul class="hp-room__meta">
                <?php if (!empty($room['area'])): ?>
                  <li><?= esc_html((string) $room['area']); ?> м²</li>
                <?php endif; ?>
                <?php if ($room['max_total']): ?>
                  <li>до <?= (int) $room['max_total']; ?> гостей</li>
                <?php endif; ?>
                <?php if ($room['max_children']): ?>
                  <li>дети: до <?= (int) $room['max_children']; ?></li>
                <?php endif; ?>
              </ul>

              <?php if ($room['description'] !== ''): ?>
                <p class="hp-room__descr"><?= esc_html($room['description']); ?></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="hp-room__offers js-room-offers">
            <?php if ($offers): ?>
              <?php foreach ($offers as $offer): ?>
                <div class="hp-offer">
                  <div class="hp-offer__terms">
                    <span class="hp-offer__meal"><?= esc_html($offer['meal_label']); ?></span>
                    <?php if ($offer['placement_label'] !== ''): ?>
                      <span class="hp-offer__placement"><?= esc_html($offer['placement_label']); ?></span>
                    <?php endif; ?>
                    <?php if (!$all_request && $offer['status'] === 'request'): ?>
                      <span class="hp-offer__badge">под запрос</span>
                    <?php endif; ?>
                  </div>

                  <div class="hp-offer__price">
                    <b><?= esc_html(bsi_hotel_view_price(['amount' => $offer['price'], 'currency' => $offer['currency']])); ?></b>
                    <span><?= esc_html(bsi_hotel_view_nights_label((int) $offer['nights'])); ?></span>
                  </div>

                  <?php if ($offer['booking_url'] !== ''): ?>
                    <a class="btn btn-accent sm hp-offer__cta" href="<?= esc_url($offer['booking_url']); ?>" target="_blank" rel="nofollow noopener">Забронировать</a>
                  <?php else: ?>
                    <a class="btn btn-white sm hp-offer__cta" href="#hotel-request">Оставить заявку</a>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php elseif ($room['price_from'] || $room['booking_url']): ?>
              <div class="hp-offer">
                <div class="hp-offer__terms">
                  <span class="hp-offer__meal">Размещение в номере</span>
                </div>

                <?php if ($room['price_from']): ?>
                  <div class="hp-offer__price">
                    <b><?= esc_html(bsi_hotel_view_price($room['price_from'])); ?></b>
                    <span>за ночь</span>
                  </div>
                <?php endif; ?>

                <?php if ($room['booking_url'] !== ''): ?>
                  <a class="btn btn-accent sm hp-offer__cta" href="<?= esc_url($room['booking_url']); ?>" target="_blank" rel="nofollow noopener">Забронировать</a>
                <?php else: ?>
                  <a class="btn btn-white sm hp-offer__cta" href="#hotel-request">Оставить заявку</a>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <p class="hp-room__empty">На выбранные даты мест нет</p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <script type="application/json" class="js-hotel-offers"><?= wp_json_encode($payload); ?></script>
  </div>
</section>
