<?php

/**
 * Номера и тарифы — главный блок страницы.
 *
 * Цены считает хаб: по календарю мы знаем стоимость ночи, а точную сумму
 * заезда и ссылку брони с датами отдаёт `/v1/hotels/{id}/quote` — до него
 * страница ходит через `bsi_hotels_api_quote` (inc/requests/ajax-hotels-api-quote.php).
 *
 * Без JS остаётся серверная выдача: варианты питания с ценой за ночь.
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

/* Три ночи — минимальная длительность, которую хаб считает своими ставками,
   без похода к поставщику. С неё и начинаем. */
$default_nights = 3;

/* Подтверждение и срок предложения чаще всего одинаковы у всех номеров отеля.
   Общее показываем один раз в шапке блока, на карточках — только отличия. */
$common = bsi_hotel_view_rooms_common($rooms);

$has_offers = (bool) $filters['dates'];
$hotel_booking = (string) ($view['booking_url'] ?? '');
$hotel_id = (int) ($view['id'] ?? 0);

/* Календарь подписывает каждый день ценой за ночь и числом свободных номеров.
   Дни, которых здесь нет, не заняты — про них хаб ещё не спрашивал, поэтому
   витрина оставляет их доступными и считает цену по клику. */
$calendar = [];
foreach ($rooms as $room) {
  foreach ($room['availability']['days'] as $day) {
    $date = $day['date'];
    $amount = (float) $day['price']['amount'];

    if (!isset($calendar[$date]) || $amount < $calendar[$date]['amount']) {
      $calendar[$date] = [
        'amount' => $amount,
        'price' => bsi_hotel_view_price($day['price']),
        'rooms' => $day['rooms'],
      ];
    }
  }
}
ksort($calendar);

$calendar_data = [];
foreach ($calendar as $date => $entry) {
  $calendar_data[$date] = ['price' => $entry['price'], 'rooms' => $entry['rooms']];
}
?>

<section class="hp-rooms" id="hotel-rooms">
  <div class="container">
    <div class="hp-rooms__head">
      <h2 class="h2">Номера и цены</h2>
      <?php if ($has_offers): ?>
        <p class="hp-rooms__hint">Цены за размещение целиком, по данным туроператора</p>
      <?php endif; ?>

      <?php
      $common_confirmation = bsi_hotel_view_confirmation_label($common['confirmation']);
      $common_until = $common['offer_until'] !== ''
        ? bsi_hotel_view_offer_until_label($common['offer_until'])
        : '';
      ?>
      <?php if ($common_confirmation !== '' || $common_until !== '' || $common['early_booking']): ?>
        <ul class="hp-rooms__terms">
          <?php if ($common_confirmation !== ''): ?>
            <li><?= bsi_hotel_view_badge($common['confirmation'], $common_confirmation, 'circle-check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая плашка ?></li>
          <?php endif; ?>
          <?php if ($common['early_booking']): ?>
            <li class="hp-badge hp-badge--deal">Раннее бронирование</li>
          <?php endif; ?>
          <?php if ($common_until !== ''): ?>
            <li class="hp-badge hp-badge--until"><?= esc_html($common_until); ?></li>
          <?php endif; ?>
        </ul>
      <?php endif; ?>
    </div>

    <?php if ($has_offers): ?>
      <form class="hp-search js-hotel-search"
            data-hotel="<?= esc_attr((string) $hotel_id); ?>"
            data-default-date="<?= esc_attr($default_date); ?>"
            data-default-nights="<?= esc_attr((string) $default_nights); ?>"
            data-prices="<?= esc_attr(wp_json_encode($calendar_data)); ?>">
        <?php /* Календарь подписывает известные дни ценой за ночь. Остальные
                 дни тоже выбираются: они не заняты — про них просто ещё не
                 спрашивали, цену посчитает хаб по клику. */ ?>
        <div class="hp-search__field js-hotel-range-field" hidden>
          <span class="hp-search__label">Заезд</span>
          <input class="hp-search__date js-hotel-range" type="text" readonly placeholder="Выберите дату">
        </div>

        <div class="hp-search__pair">
          <label class="hp-search__field js-hotel-date-field">
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
        </div>

        <label class="hp-search__field">
          <span class="hp-search__label">Питание</span>
          <select class="hp-search__select js-hotel-meal" name="meal">
            <option value="">Любое</option>
            <?php foreach ($filters['meals'] as $code => $label): ?>
              <option value="<?= esc_attr($code); ?>"><?= esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <div class="hp-search__foot">
          <p class="hp-search__note js-hotel-search-note"></p>

          <?php /* Сброс возвращает ближайший заезд и любое питание — то, с чем
                   страница открывается. Без JS его не показываем: там отбор и
                   так уходит на сервер. */ ?>
          <button class="hp-search__reset js-hotel-reset" type="button" hidden>Сбросить</button>
        </div>
      </form>
    <?php endif; ?>

    <div class="hp-rooms__list js-hotel-rooms">
      <?php foreach ($rooms as $room):
        $photo = $room['photos'][0]['url'] ?? '';
        ?>
        <article class="hp-room" data-room="<?= esc_attr($room['id']); ?>">
          <?php /* Слева треть карточки под фото: крупный снимок и до трёх
                   миниатюр под ним. Остальные снимки открываются в галерее
                   с любой миниатюры. */ ?>
          <div class="hp-room__media">
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

            <?php $thumbs = array_slice($room['photos'], 1); ?>
            <?php if ($thumbs): ?>
              <ul class="hp-room__thumbs">
                <?php foreach (array_slice($thumbs, 0, 3) as $index => $thumb): ?>
                  <?php $rest = ($index === 2) ? count($thumbs) - 3 : 0; ?>
                  <li class="hp-room__thumb">
                    <a href="<?= esc_url($thumb['url']); ?>" data-fancybox="room-<?= esc_attr($room['id']); ?>">
                      <img src="<?= esc_url($thumb['url']); ?>" alt="<?= esc_attr($room['name']); ?>" loading="lazy" decoding="async">
                      <?php if ($rest > 0): ?>
                        <span class="hp-room__thumb-rest">+<?= (int) $rest; ?></span>
                      <?php endif; ?>
                    </a>
                  </li>
                <?php endforeach; ?>

                <?php /* Снимки сверх четвёртого в галерее есть, но плитки им не нужно. */ ?>
                <?php foreach (array_slice($thumbs, 3) as $hidden): ?>
                  <a class="hp-room__thumb-hidden" href="<?= esc_url($hidden['url']); ?>" data-fancybox="room-<?= esc_attr($room['id']); ?>"></a>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>

          <div class="hp-room__body">
            <?php
            /* Подтверждение — второе, что важно гостю после цены: бронь
               подтверждается сразу или оператор сначала спрашивает отель. */
            $confirmation = bsi_hotel_view_room_confirmation($room);
            $confirmation_label = bsi_hotel_view_confirmation_label($confirmation);
            $deals = bsi_hotel_view_room_deals($room);
            ?>

            <div class="hp-room__head">
              <h3 class="hp-room__title"><?= esc_html($room['name']); ?></h3>

              <?php if ($confirmation_label !== '' && $confirmation !== $common['confirmation']): ?>
                <?= bsi_hotel_view_badge($confirmation, $confirmation_label, 'circle-check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая плашка ?>
              <?php endif; ?>

              <?php if (!empty($room['limited'])): ?>
                <?= bsi_hotel_view_badge('limited', 'Мало мест', 'zap', 'На ближайшие даты мест нет. Выберите даты — проверим наличие у оператора'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- готовая плашка ?>
              <?php endif; ?>
            </div>

            <?php
            $show_early = $deals['early_booking'] && !$common['early_booking'];
            $show_until = $deals['offer_until'] !== '' && $deals['offer_until'] !== $common['offer_until'];
            ?>
            <?php if ($show_early || $show_until): ?>
              <ul class="hp-room__deals">
                <?php if ($show_early): ?>
                  <li class="hp-badge hp-badge--deal">Раннее бронирование</li>
                <?php endif; ?>
                <?php if ($show_until): ?>
                  <li class="hp-badge hp-badge--until"><?= esc_html(bsi_hotel_view_offer_until_label($deals['offer_until'])); ?></li>
                <?php endif; ?>
              </ul>
            <?php endif; ?>

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
              <?php if (!empty($room['view'])): ?>
                <li><?= esc_html($room['view']); ?></li>
              <?php endif; ?>
            </ul>

            <?php if ($room['description'] !== ''): ?>
              <p class="hp-room__descr"><?= esc_html($room['description']); ?></p>
            <?php endif; ?>

            <?php /* Удобства номера — иконкой с подписью: их немного, и они
                     отличают один тип номера от другого. */ ?>
            <?php if (!empty($room['amenities'])): ?>
              <ul class="hp-room__amenities">
                <?php foreach (array_slice($room['amenities'], 0, 8) as $amenity): ?>
                  <li class="hp-room__amenity">
                    <?php if ($amenity['icon']): ?>
                      <img src="<?= esc_url($amenity['icon']); ?>" alt="" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <?= esc_html($amenity['name']); ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php /* Календарь цен из `availability`: на какие числа номер открыт
                     и где ночь дешевле. */ ?>
            <?php $days = $room['availability']['days'] ?? []; ?>
            <?php if ($days): ?>
              <div class="hp-room__dates">
                <p class="hp-room__dates-title">Свободные даты:</p>

                <ul class="hp-room__days">
                  <?php foreach ($days as $day): ?>
                    <?php
                    /* Дата, на которую есть гарантированный вариант, помечается:
                       по такой можно бронировать без ожидания ответа отеля. */
                    $day_instant = !empty($day['instant_price']) || (int) $day['instant_rooms'] > 0;
                    $day_title = $day['label'];
                    if ($day_instant) {
                      $day_title .= ' — мгновенное подтверждение';
                      if (!empty($day['instant_price'])) {
                        $day_title .= ', от ' . bsi_hotel_view_price($day['instant_price']);
                      }
                    }
                    ?>
                    <li class="hp-room__day<?= $day_instant ? ' hp-room__day--instant' : ''; ?>" title="<?= esc_attr($day_title); ?>">
                      <span class="hp-room__day-date"><?= esc_html($day['day']); ?></span>
                      <span class="hp-room__day-price"><?= esc_html(bsi_hotel_view_price($day['price'])); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

          <div class="hp-room__offers js-room-offers">
            <?php if ($room['meals']): ?>
              <?php foreach ($room['meals'] as $meal): ?>
                <div class="hp-offer">
                  <div class="hp-offer__terms">
                    <span class="hp-offer__meal"
                          <?php if ($meal['description'] !== ''): ?>title="<?= esc_attr($meal['description']); ?>"<?php endif; ?>><?= esc_html($meal['label']); ?></span>
                    <?php if ($meal['placement_label'] !== ''): ?>
                      <span class="hp-offer__placement"><?= esc_html($meal['placement_label']); ?></span>
                    <?php endif; ?>
                  </div>

                  <?php if (!empty($meal['instant_nights'])): ?>
                    <span class="hp-offer__instant">
                      мгновенное подтверждение: <?= (int) $meal['instant_nights']; ?>
                      <?php if (!empty($meal['nights'])): ?>из <?= (int) $meal['nights']; ?><?php endif; ?>
                    </span>
                  <?php endif; ?>

                  <?php if ($meal['price_from']): ?>
                    <div class="hp-offer__price">
                      <b><?= esc_html(bsi_hotel_view_price($meal['price_from'])); ?></b>
                      <span>за ночь</span>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php elseif ($room['price_from']): ?>
              <div class="hp-offer">
                <div class="hp-offer__terms">
                  <span class="hp-offer__meal">Размещение в номере</span>
                </div>

                <div class="hp-offer__price">
                  <b><?= esc_html(bsi_hotel_view_price($room['price_from'])); ?></b>
                  <span>за ночь</span>
                </div>
              </div>
            <?php else: ?>
              <p class="hp-room__empty">Цены уточняются</p>
            <?php endif; ?>
            </div>

            <?php /* Одна кнопка на номер: у тарифов своей ссылки хаб не даёт,
                     а ссылка отеля ведёт в Само на тот же отель. */ ?>
            <?php if (!empty($room['instant_price_from'])): ?>
              <p class="hp-room__instant-price">
                Мгновенное подтверждение — от <b><?= esc_html(bsi_hotel_view_price($room['instant_price_from'])); ?></b> за ночь
              </p>
            <?php endif; ?>

            <?php $room_booking = $room['booking_url'] !== '' ? $room['booking_url'] : $hotel_booking; ?>
            <?php if ($room_booking !== ''): ?>
              <a class="btn btn-accent sm hp-room__cta"
                 href="<?= esc_url($room_booking); ?>"
                 target="_blank"
                 rel="nofollow noopener">Забронировать</a>
            <?php else: ?>
              <a class="btn btn-gray sm hp-room__cta" href="#hotel-request">Забронировать</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php
    /* Календарь и питание каждого номера: по ним витрина рисует цены сразу,
       не дожидаясь расчёта заезда. */
    $payload = array_map(static fn(array $room): array => [
      'id' => $room['id'],
      'meals' => array_map(static fn(array $meal): array => [
        'code' => $meal['code'],
        'label' => $meal['label'],
        'placement_label' => $meal['placement_label'],
        'price' => $meal['price_from'] ? bsi_hotel_view_price($meal['price_from']) : '',
        'instant_nights' => (int) ($meal['instant_nights'] ?? 0),
        'nights' => (int) ($meal['nights'] ?? 0),
      ], $room['meals']),
    ], $rooms);
    ?>
    <script type="application/json" class="js-hotel-offers"><?= wp_json_encode($payload); ?></script>
  </div>
</section>
