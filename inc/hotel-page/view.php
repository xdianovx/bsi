<?php

/**
 * View-model страницы отеля.
 *
 * Приводит два источника — карточку из хаба BSIHOTELS и пост WordPress типа
 * hotel — к одной структуре, чтобы шаблоны в template-parts/hotel-page/
 * ничего не знали про источник данных.
 *
 * Структура:
 *   name, stars, place[], address, price_from, booking_url,
 *   photos[], amenities[], facts[], rooms[], sections[], map, back
 */

/**
 * Пустой каркас view-модели. Все шаблоны рассчитывают на эти ключи.
 */
function bsi_hotel_view_defaults(): array
{
  return [
    'source' => '',
    'name' => '',
    'stars' => 0,
    'place' => [],          // [['label' => '', 'url' => ''], ...]
    'address' => '',
    'excerpt' => '',
    'price_from' => null,   // ['amount' => float, 'currency' => 'USD']
    'booking_url' => '',
    'booking' => [],        // [['label' => '', 'url' => ''], ...] — кнопки брони
    'contacts' => [],       // ['phone' => '', 'address' => '', 'website' => '']
    'photos' => [],         // [['url' => '', 'caption' => ''], ...]
    'amenities' => [],      // [['name' => '', 'icon' => '', 'group' => '', 'popular' => bool], ...]
    'facts' => [],          // [['label' => '', 'value' => ''], ...]
    'rooms' => [],          // см. bsi_hotel_view_room()
    'sections' => [],       // [['id' => '', 'title' => '', 'html' => ''], ...]
    'map' => null,          // ['lat' => float, 'lng' => float, 'zoom' => int]
    'location_note' => '',  // текст о расположении; выводится в секции с картой
    'back' => null,         // ['url' => '', 'label' => '']
    'pdf_modal' => '',      // id модалки печати, если она есть у источника
  ];
}

/**
 * View-модель по карточке отеля из хаба.
 *
 * @param array   $hotel   ответ /v1/hotels/{id}
 * @param WP_Post $country страна, в разделе которой открыта страница
 */
function bsi_hotel_view_from_api(array $hotel, WP_Post $country): array
{
  $view = bsi_hotel_view_defaults();
  $catalog_url = bsi_hotels_api_catalog_url($country);

  $view['source'] = 'api';
  $view['name'] = (string) ($hotel['name'] ?? '');
  $view['stars'] = (int) ($hotel['stars'] ?? 0);
  $view['address'] = (string) ($hotel['address'] ?? '');
  $view['booking_url'] = bsi_hotel_view_booking_url($hotel['booking'] ?? null);

  if ($view['booking_url'] !== '') {
    $view['booking'][] = ['label' => 'Бронирование отеля', 'url' => $view['booking_url']];
  }

  $view['place'][] = [
    'label' => get_the_title($country),
    'url' => get_permalink($country),
  ];

  $city = (string) ($hotel['city']['name'] ?? '');
  $city_slug = (string) ($hotel['city']['slug'] ?? '');
  if ($city !== '') {
    $view['place'][] = [
      'label' => $city,
      'url' => $city_slug !== '' ? bsi_hotels_api_resort_url($catalog_url, $city_slug) : '',
    ];
  }

  foreach ((array) ($hotel['photos'] ?? []) as $photo) {
    $url = (string) ($photo['url'] ?? '');
    if ($url === '') {
      continue;
    }
    $view['photos'][] = [
      'url' => $url,
      'caption' => (string) ($photo['caption'] ?? ''),
    ];
  }

  foreach ((array) ($hotel['amenities'] ?? []) as $amenity) {
    $name = (string) ($amenity['name'] ?? '');
    if ($name === '') {
      continue;
    }

    $view['amenities'][] = [
      'name' => $name,
      'icon' => (string) ($amenity['icon'] ?? ''),
      'group' => (string) ($amenity['group'] ?? ''),
      'popular' => !empty($amenity['is_popular']),
    ];
  }

  $view['facts'] = bsi_hotel_view_api_facts($hotel);

  if (!empty($hotel['description'])) {
    $view['sections'][] = [
      'id' => 'hotel-about',
      'title' => 'Об отеле',
      'html' => wpautop(esc_html((string) $hotel['description'])),
    ];
  }

  if (!empty($hotel['transfer_note'])) {
    $view['sections'][] = [
      'id' => 'hotel-transfer',
      'title' => 'Трансфер',
      'html' => wpautop(esc_html((string) $hotel['transfer_note'])),
    ];
  }

  $view['location_note'] = (string) ($hotel['location_note'] ?? '');

  $lat = $hotel['lat'] ?? null;
  $lng = $hotel['lng'] ?? null;
  if ($lat !== null && $lng !== null && (float) $lat !== 0.0) {
    // 16 — уровень улиц и зданий: видно сам отель и что вокруг него.
    $view['map'] = ['lat' => (float) $lat, 'lng' => (float) $lng, 'zoom' => 16];
  }

  $view['rooms'] = bsi_hotel_view_api_rooms($hotel);
  $view['price_from'] = bsi_hotel_view_price_from($view['rooms']);

  $view['back'] = [
    'url' => $catalog_url,
    'label' => 'Все отели: ' . get_the_title($country),
  ];

  return $view;
}

/**
 * Ссылка на бронирование из хаба.
 *
 * Хаб отдаёт её объектом `{provider, url}` — у карточки отеля (без дат) и у номера.
 * Строку тоже принимаем: так поле выглядело в ранних ответах.
 */
function bsi_hotel_view_booking_url($booking): string
{
  if (is_string($booking)) {
    return trim($booking);
  }

  if (is_array($booking)) {
    return trim((string) ($booking['url'] ?? ''));
  }

  return '';
}

/**
 * Факты отеля из хаба: расстояния, линия пляжа, время заезда.
 */
function bsi_hotel_view_api_facts(array $hotel): array
{
  $facts = [];

  $labels = [
    'beach_m' => 'До пляжа',
    'center_m' => 'До центра',
    'airport_m' => 'До аэропорта',
  ];

  foreach ((array) ($hotel['distances'] ?? []) as $key => $meters) {
    if ((int) $meters <= 0) {
      continue;
    }
    $facts[] = [
      'label' => $labels[$key] ?? $key,
      'value' => number_format((int) $meters, 0, ',', ' ') . ' м',
    ];
  }

  if (!empty($hotel['beach_line'])) {
    $facts[] = ['label' => 'Линия пляжа', 'value' => (string) (int) $hotel['beach_line']];
  }

  if (!empty($hotel['check_in_time'])) {
    $facts[] = ['label' => 'Заезд', 'value' => (string) $hotel['check_in_time']];
  }

  if (!empty($hotel['check_out_time'])) {
    $facts[] = ['label' => 'Выезд', 'value' => (string) $hotel['check_out_time']];
  }

  if (!empty($hotel['adults_only'])) {
    $facts[] = ['label' => 'Только для взрослых', 'value' => 'да'];
  }

  return $facts;
}

/**
 * Номера отеля из хаба вместе с тарифами.
 */
function bsi_hotel_view_api_rooms(array $hotel): array
{
  $rooms = [];

  foreach ((array) ($hotel['room_types'] ?? []) as $room) {
    $offers = bsi_hotel_view_api_offers($room);

    $photos = [];
    foreach ((array) ($room['photos'] ?? []) as $photo) {
      $url = (string) ($photo['url'] ?? '');
      if ($url !== '') {
        $photos[] = ['url' => $url, 'caption' => (string) ($photo['caption'] ?? '')];
      }
    }

    $rooms[] = [
      'id' => (string) ($room['id'] ?? ''),
      'name' => (string) ($room['name'] ?? ''),
      'description' => (string) ($room['description'] ?? ''),
      'area' => ((float) ($room['area_m2'] ?? 0)) > 0 ? $room['area_m2'] : null,
      'max_adults' => (int) ($room['max_adults'] ?? 0),
      'max_children' => (int) ($room['max_children'] ?? 0),
      'max_total' => (int) ($room['max_total'] ?? 0),
      'photos' => $photos,
      'offers' => $offers,
      'price_from' => bsi_hotel_view_offers_min($offers),
      'booking_url' => bsi_hotel_view_booking_url($room['booking'] ?? null),
    ];
  }

  return $rooms;
}

/**
 * Тарифы номера: только доступные, отсортированы по дате и цене.
 */
function bsi_hotel_view_api_offers(array $room): array
{
  $offers = [];

  foreach ((array) ($room['offers'] ?? []) as $offer) {
    $price = (float) ($offer['price'] ?? 0);
    $check_in = (string) ($offer['check_in'] ?? '');

    if ($price <= 0 || $check_in === '') {
      continue;
    }

    if (!bsi_hotel_view_offer_available($offer['available'] ?? null)) {
      continue;
    }

    $offers[] = [
      'check_in' => $check_in,
      'nights' => (int) ($offer['nights'] ?? 0),
      'meal' => (string) ($offer['meal'] ?? ''),
      'meal_label' => bsi_hotel_view_meal_label((string) ($offer['meal'] ?? '')),
      'placement' => (string) ($offer['placement'] ?? ''),
      'placement_label' => bsi_hotel_view_placement_label((string) ($offer['placement'] ?? '')),
      'price' => $price,
      'currency' => (string) ($offer['currency'] ?? ''),
      'status' => bsi_hotel_view_offer_status($offer['available'] ?? null),
      'booking_url' => bsi_hotel_view_booking_url($offer['booking'] ?? null),
    ];
  }

  usort($offers, static function (array $a, array $b): int {
    return [$a['check_in'], $a['nights'], $a['price']] <=> [$b['check_in'], $b['nights'], $b['price']];
  });

  return $offers;
}

/**
 * Наличие мест. Хаб отдаёт строку статусов по дням заезда в кодах Само:
 * Y — свободно, F — свободная продажа, R — по запросу, N — мест нет.
 * Хотя бы один день с N делает оффер непродаваемым.
 */
function bsi_hotel_view_offer_available($available): bool
{
  if (is_numeric($available)) {
    return (int) $available > 0;
  }

  if (!is_string($available) || $available === '') {
    return false;
  }

  return !str_contains(strtoupper($available), 'N');
}

/**
 * Статус оффера: 'free' — можно бронировать, 'request' — только по запросу.
 */
function bsi_hotel_view_offer_status($available): string
{
  return is_string($available) && str_contains(strtoupper($available), 'R')
    ? 'request'
    : 'free';
}

/**
 * @return array{amount: float, currency: string}|null
 */
function bsi_hotel_view_offers_min(array $offers): ?array
{
  $min = null;

  foreach ($offers as $offer) {
    $nights = max(1, (int) $offer['nights']);
    $per_night = $offer['price'] / $nights;

    if ($min === null || $per_night < $min['amount']) {
      $min = ['amount' => $per_night, 'currency' => $offer['currency']];
    }
  }

  return $min;
}

/**
 * @return array{amount: float, currency: string}|null
 */
function bsi_hotel_view_price_from(array $rooms): ?array
{
  $min = null;

  foreach ($rooms as $room) {
    $price = $room['price_from'] ?? null;
    if ($price && ($min === null || $price['amount'] < $min['amount'])) {
      $min = $price;
    }
  }

  return $min;
}

function bsi_hotel_view_meal_label(string $code): string
{
  $labels = [
    'RO' => 'Без питания',
    'OB' => 'Без питания',
    'AO' => 'Без питания',
    'BB' => 'Завтраки',
    'HB' => 'Завтрак и ужин',
    'HB+' => 'Завтрак и ужин +',
    'FB' => 'Полный пансион',
    'FB+' => 'Полный пансион +',
    'AI' => 'Всё включено',
    'UAI' => 'Ультра всё включено',
    'AI+' => 'Всё включено +',
  ];

  $original = trim($code);
  if ($original === '') {
    return 'Питание уточняется';
  }

  // Хаб отдаёт коды по-разному: «HB Plus», «hb+», «HB PLUS» — сводим к одному виду.
  $normalized = str_replace([' PLUS', 'PLUS', ' '], ['+', '+', ''], strtoupper($original));

  // Незнакомый код («AI Dine Around») показываем как есть — он читаемее склейки.
  return $labels[$normalized] ?? $original;
}

function bsi_hotel_view_placement_label(string $code): string
{
  $code = strtoupper(trim($code));

  if ($code === '') {
    return '';
  }

  $labels = [
    'SGL' => 'Одноместное',
    'DBL' => 'Двухместное',
    'TWIN' => 'Два раздельных места',
    'TRPL' => 'Трёхместное',
    'QDPL' => 'Четырёхместное',
    'EXB' => 'Дополнительное место',
    'CHD' => 'Место ребёнка',
    'ADL' => 'Место взрослого',
  ];

  if (isset($labels[$code])) {
    return $labels[$code];
  }

  // Коды состава вида 2ADL, 1ADL+1CHD, 3ADL+2CHD.
  $parts = [];
  foreach (explode('+', $code) as $chunk) {
    if (!preg_match('/^(\d+)(ADL|CHD|INF)$/', trim($chunk), $m)) {
      return $code;
    }

    $count = (int) $m[1];
    $parts[] = $count . ' ' . bsi_hotel_view_guest_word($count, $m[2]);
  }

  return implode(' + ', $parts);
}

/**
 * Склонение состава гостей: взрослый / ребёнок / младенец.
 */
function bsi_hotel_view_guest_word(int $count, string $type): string
{
  $forms = [
    'ADL' => ['взрослый', 'взрослых', 'взрослых'],
    'CHD' => ['ребёнок', 'ребёнка', 'детей'],
    'INF' => ['младенец', 'младенца', 'младенцев'],
  ][$type] ?? ['гость', 'гостя', 'гостей'];

  $n = abs($count) % 100;
  $n1 = $n % 10;

  if ($n > 10 && $n < 20) {
    return $forms[2];
  }
  if ($n1 > 1 && $n1 < 5) {
    return $forms[1];
  }
  if ($n1 === 1) {
    return $forms[0];
  }

  return $forms[2];
}

/**
 * Цена для вывода. У обоих источников валюта приходит кодом.
 */
function bsi_hotel_view_price(array $price): string
{
  return bsi_hotels_api_format_price([
    'amount' => $price['amount'],
    'currency' => $price['currency'],
  ]);
}

/**
 * Дата заезда по-русски: «12 сентября, сб».
 */
function bsi_hotel_view_date_label(string $date): string
{
  $ts = strtotime($date);
  if (!$ts) {
    return $date;
  }

  return wp_date('j F, D', $ts);
}

/**
 * Склонение «ночь / ночи / ночей».
 */
function bsi_hotel_view_nights_label(int $nights): string
{
  $forms = ['ночь', 'ночи', 'ночей'];
  $n = abs($nights) % 100;
  $n1 = $n % 10;

  if ($n > 10 && $n < 20) {
    $form = $forms[2];
  } elseif ($n1 > 1 && $n1 < 5) {
    $form = $forms[1];
  } elseif ($n1 === 1) {
    $form = $forms[0];
  } else {
    $form = $forms[2];
  }

  return $nights . ' ' . $form;
}

/**
 * Секции для тап-навигации: только те, у которых есть содержимое.
 */
function bsi_hotel_view_nav(array $view): array
{
  $nav = [];

  if ($view['rooms']) {
    $nav[] = ['id' => 'hotel-rooms', 'label' => 'Номера и цены'];
  }

  foreach ($view['sections'] as $section) {
    $nav[] = ['id' => $section['id'], 'label' => $section['title']];
  }

  if ($view['amenities']) {
    $nav[] = ['id' => 'hotel-amenities', 'label' => 'Удобства'];
  }

  if ($view['facts']) {
    $nav[] = ['id' => 'hotel-facts', 'label' => 'Важно знать'];
  }

  if ($view['map'] || trim((string) $view['location_note']) !== '') {
    $nav[] = ['id' => 'hotel-location', 'label' => 'Расположение'];
  }

  $nav[] = ['id' => 'hotel-request', 'label' => 'Заявка'];

  return $nav;
}

/**
 * Данные панели подбора: доступные даты заезда и длительности.
 */
function bsi_hotel_view_offer_filters(array $rooms): array
{
  $dates = [];
  $nights = [];
  $meals = [];

  foreach ($rooms as $room) {
    foreach ($room['offers'] as $offer) {
      $dates[$offer['check_in']] = true;
      $nights[$offer['nights']] = true;
      if ($offer['meal'] !== '') {
        $meals[$offer['meal']] = $offer['meal_label'];
      }
    }
  }

  $dates = array_keys($dates);
  sort($dates);

  $nights = array_keys($nights);
  sort($nights);

  asort($meals);

  return ['dates' => $dates, 'nights' => $nights, 'meals' => $meals];
}

/**
 * View-модель по посту WordPress типа hotel.
 *
 * Поля ACF раскладываются в ту же структуру, что и карточка хаба: текстовые
 * блоки становятся секциями, репитер hotel_rooms — номерами, таксономия
 * amenity — удобствами.
 */
function bsi_hotel_view_from_post(int $post_id): array
{
  $view = bsi_hotel_view_defaults();
  $has_acf = function_exists('get_field');

  $view['source'] = 'wp';
  $view['name'] = get_the_title($post_id);
  $view['stars'] = (int) ($has_acf ? get_field('rating', $post_id) : 0);
  $view['address'] = trim((string) ($has_acf ? get_field('address', $post_id) : ''));
  $view['excerpt'] = get_the_excerpt($post_id);
  $view['pdf_modal'] = 'modal-hotel-pdf';

  $view['place'] = bsi_hotel_view_unique_place(bsi_hotel_view_post_place($post_id));
  $view['photos'] = bsi_hotel_view_post_photos($post_id);
  $view['amenities'] = bsi_hotel_view_post_amenities($post_id);
  $view['facts'] = bsi_hotel_view_post_facts($post_id);
  $view['sections'] = bsi_hotel_view_post_sections($post_id);
  $view['rooms'] = bsi_hotel_view_post_rooms($post_id);

  $min = $has_acf && function_exists('bsi_hotel_min_room_price')
    ? bsi_hotel_min_room_price($post_id)
    : [];

  if ($min) {
    $view['price_from'] = ['amount' => (float) $min['rub'], 'currency' => 'RUB'];
  } elseif ($has_acf && trim((string) get_field('price', $post_id)) !== '') {
    $view['price_from'] = [
      'amount' => (float) preg_replace('/[^\d.]/', '', (string) get_field('price', $post_id)),
      'currency' => 'RUB',
    ];
  }

  $view['booking'] = [];
  foreach ([
    'booking_url' => 'Тур с перелётом',
    'booking_url_hotel_only' => 'Отель без перелёта',
  ] as $field => $label) {
    $url = $has_acf ? trim((string) get_field($field, $post_id)) : '';
    if ($url !== '') {
      $view['booking'][] = ['label' => $label, 'url' => $url];
    }
  }

  $view['booking_url'] = $view['booking'][0]['url'] ?? '';

  $view['contacts'] = array_filter([
    'phone' => $has_acf ? trim((string) get_field('phone', $post_id)) : '',
    'address' => $view['address'],
    'website' => $has_acf ? trim((string) get_field('website', $post_id)) : '',
  ]);

  $view['map'] = bsi_hotel_view_post_map($post_id);

  return $view;
}

/**
 * Убирает повторы в цепочке места: «Мальдивы, Мальдивы, Южный Ари атолл»
 * превращается в «Мальдивы, Южный Ари атолл».
 */
function bsi_hotel_view_unique_place(array $place): array
{
  $seen = [];
  $result = [];

  foreach ($place as $item) {
    $key = mb_strtolower(trim($item['label']));
    if ($key === '' || isset($seen[$key])) {
      continue;
    }

    $seen[$key] = true;
    $result[] = $item;
  }

  return $result;
}

/**
 * Цепочка «страна — регион — курорт — город» со ссылками, где они есть.
 */
function bsi_hotel_view_post_place(int $post_id): array
{
  $place = [];

  $country_id = function_exists('get_field') ? get_field('hotel_country', $post_id) : 0;
  $country_id = is_array($country_id) ? (int) reset($country_id) : (int) $country_id;

  if ($country_id) {
    $place[] = ['label' => get_the_title($country_id), 'url' => get_permalink($country_id)];
  }

  foreach (['region', 'resort'] as $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (empty($terms) || is_wp_error($terms)) {
      continue;
    }

    $link = get_term_link($terms[0]);
    $place[] = [
      'label' => $terms[0]->name,
      'url' => is_wp_error($link) ? '' : $link,
    ];
  }

  $city = function_exists('get_field') ? trim((string) get_field('hotel_city', $post_id)) : '';
  if ($city !== '') {
    $place[] = ['label' => $city, 'url' => ''];
  }

  return $place;
}

function bsi_hotel_view_post_photos(int $post_id): array
{
  $gallery = function_exists('get_field') ? get_field('gallery', $post_id) : [];
  $photos = [];

  foreach ((array) $gallery as $image) {
    $url = is_array($image) ? ($image['url'] ?? '') : (string) $image;
    if ($url === '') {
      continue;
    }

    $photos[] = [
      'url' => $url,
      'caption' => is_array($image) ? (string) ($image['caption'] ?: $image['alt'] ?? '') : '',
    ];
  }

  return $photos;
}

function bsi_hotel_view_post_amenities(int $post_id): array
{
  $terms = get_the_terms($post_id, 'amenity');
  if (empty($terms) || is_wp_error($terms)) {
    return [];
  }

  $amenities = [];
  foreach ($terms as $term) {
    $icon = function_exists('get_field') ? get_field('amenity_icon', 'term_' . $term->term_id) : null;

    $amenities[] = [
      'name' => $term->name,
      'icon' => is_array($icon) && !empty($icon['url']) ? $icon['url'] : '',
      'group' => '',
      'popular' => false,
    ];
  }

  return $amenities;
}

/**
 * Удобства по группам хаба («Пляж и бассейн», «Для детей»…). Без группы — в конец
 * под общим заголовком, чтобы список не рассыпался.
 *
 * @return array<string, array> группа => удобства
 */
function bsi_hotel_view_amenity_groups(array $amenities): array
{
  $groups = [];

  foreach ($amenities as $amenity) {
    $group = trim((string) ($amenity['group'] ?? ''));
    $groups[$group][] = $amenity;
  }

  // Безымянная группа всегда последняя.
  if (isset($groups[''])) {
    $rest = $groups[''];
    unset($groups['']);
    $groups[''] = $rest;
  }

  return $groups;
}

/**
 * Удобства для шапки: сначала популярные, потом остальные.
 */
function bsi_hotel_view_popular_amenities(array $amenities, int $limit): array
{
  usort($amenities, static fn(array $a, array $b) => (int) !empty($b['popular']) <=> (int) !empty($a['popular']));

  return array_slice($amenities, 0, $limit);
}

/**
 * Факты: время заезда, год постройки и реновации, репитер расстояний.
 */
function bsi_hotel_view_post_facts(int $post_id): array
{
  if (!function_exists('get_field')) {
    return [];
  }

  $facts = [];

  foreach ([
    'check_in_time' => 'Заезд',
    'check_out_time' => 'Выезд',
  ] as $field => $label) {
    $value = trim((string) get_field($field, $post_id));
    if ($value !== '') {
      $facts[] = ['label' => $label, 'value' => $value];
    }
  }

  foreach ([
    'hotel_opened_at' => 'Построен',
    'hotel_renovated_at' => 'Реновация',
  ] as $field => $label) {
    $value = bsi_hotel_view_month_year(trim((string) get_field($field, $post_id)));
    if ($value !== '') {
      $facts[] = ['label' => $label, 'value' => $value];
    }
  }

  if (function_exists('have_rows') && have_rows('hotel_distances', $post_id)) {
    while (have_rows('hotel_distances', $post_id)) {
      the_row();
      $key = trim((string) get_sub_field('key'));
      $value = trim((string) get_sub_field('value'));

      if ($key !== '' || $value !== '') {
        $facts[] = ['label' => $key, 'value' => $value];
      }
    }
  }

  return $facts;
}

/**
 * «2019-07» → «07/2019».
 */
function bsi_hotel_view_month_year(string $value): string
{
  if ($value === '') {
    return '';
  }

  $date = DateTime::createFromFormat('Y-m', $value);
  if ($date instanceof DateTime) {
    return $date->format('m/Y');
  }

  if (preg_match('/^\d{4}-\d{2}$/', $value)) {
    return substr($value, 5, 2) . '/' . substr($value, 0, 4);
  }

  return $value;
}

/**
 * Контент поста и текстовые блоки ACF как секции страницы.
 */
function bsi_hotel_view_post_sections(int $post_id): array
{
  $sections = [];

  $content = trim((string) apply_filters('the_content', get_post_field('post_content', $post_id)));
  if ($content !== '') {
    $sections[] = ['id' => 'hotel-about', 'title' => 'Об отеле', 'html' => $content];
  }

  if (!function_exists('get_field')) {
    return $sections;
  }

  $blocks = [
    'sec_infrastructure' => 'Инфраструктура',
    'sec_meals' => 'Питание',
    'sec_restaurants' => 'Рестораны и бары',
    'sec_spa' => 'Spa и оздоровление',
    'sec_sport' => 'Спорт и развлечения',
    'sec_kids' => 'Для детей',
    'sec_mice' => 'MICE',
    'sec_beach' => 'Пляж',
  ];

  foreach ($blocks as $field => $title) {
    $html = trim((string) get_field($field, $post_id));
    if ($html === '') {
      continue;
    }

    $sections[] = [
      'id' => 'hotel-' . str_replace('sec_', '', $field),
      'title' => $title,
      'html' => $html,
    ];
  }

  return $sections;
}

/**
 * Номера из репитера hotel_rooms. Тарифов по датам у них нет — цена одна,
 * поэтому строка предложения собирается из цены и ссылки на бронирование.
 */
function bsi_hotel_view_post_rooms(int $post_id): array
{
  if (!function_exists('get_field')) {
    return [];
  }

  $rooms = get_field('hotel_rooms', $post_id);
  $rooms = is_array($rooms) ? $rooms : [];

  $booking = trim((string) get_field('booking_url_hotel_only', $post_id));
  if ($booking === '') {
    $booking = trim((string) get_field('booking_url', $post_id));
  }

  $result = [];

  foreach ($rooms as $index => $room) {
    $photos = [];
    foreach ((array) ($room['gallery'] ?? []) as $image) {
      $url = is_array($image) ? ($image['url'] ?? '') : (string) $image;
      if ($url !== '') {
        $photos[] = ['url' => $url, 'caption' => ''];
      }
    }

    $price = null;
    if (!empty($room['price_from'])) {
      $currency = !empty($room['price_currency']) ? strtoupper((string) $room['price_currency']) : 'RUB';
      $rub = function_exists('bsi_education_convert_price_to_rub')
        ? bsi_education_convert_price_to_rub($room['price_from'], $currency)
        : null;

      $price = $rub !== null
        ? ['amount' => (float) $rub, 'currency' => 'RUB']
        : ['amount' => (float) $room['price_from'], 'currency' => $currency];
    }

    $result[] = [
      'id' => 'wp-' . $index,
      'name' => (string) ($room['name'] ?? ''),
      'description' => (string) ($room['description'] ?? ''),
      'area' => ((float) ($room['area'] ?? 0)) > 0 ? $room['area'] : null,
      'max_adults' => 0,
      'max_children' => 0,
      'max_total' => (int) ($room['guests'] ?? 0),
      'photos' => $photos,
      'offers' => [],
      'price_from' => $price,
      'booking_url' => $booking,
    ];
  }

  return $result;
}

/**
 * @return array{lat: float, lng: float, zoom: int}|null
 */
function bsi_hotel_view_post_map(int $post_id): ?array
{
  if (!function_exists('get_field')) {
    return null;
  }

  $coords = function_exists('bsi_parse_map_coordinates')
    ? bsi_parse_map_coordinates(get_field('map_coordinates', $post_id))
    : null;

  $lat = $coords['lat'] ?? get_field('map_lat', $post_id);
  $lng = $coords['lng'] ?? get_field('map_lng', $post_id);

  if (!$lat || !$lng) {
    return null;
  }

  return [
    'lat' => (float) $lat,
    'lng' => (float) $lng,
    'zoom' => max(1, min(17, (int) (get_field('map_zoom', $post_id) ?: 16))),
  ];
}
