<?php
/**
 * Crosstour (событийные туры) ↔ SamoTour.
 *
 * Авто-связь события на сайте с «переездным туром» в Само по slug:
 * SearchCrosstour_TOURS отдаёт url страницы события (/event-tours/{slug}/),
 * матчим по post_name. Живые данные (цена/отели/даты) — опциональный слой
 * поверх ручных ACF-полей; источник управляется полем event_data_source.
 *
 * Все вызовы кешируются (группа 'samotour' в CacheService / транзиенты samo_*).
 */

if (!defined('BSI_CROSSTOUR_TOWNFROM')) {
  define('BSI_CROSSTOUR_TOWNFROM', 1); // «Наземное обслуживание» (crosstour)
}

/**
 * Последний сегмент пути url → slug.
 */
function bsi_crosstour_slug_from_url(string $url): string
{
  if ($url === '') {
    return '';
  }
  $path = (string) wp_parse_url($url, PHP_URL_PATH);
  $path = trim($path, '/');
  if ($path === '') {
    return '';
  }
  $parts = explode('/', $path);
  return sanitize_title(end($parts));
}

/**
 * Карта slug => {TOWNFROMINC, STATEINC, TOURINC, name, type, state_name}.
 * Перебор стран Само (их немного). Кеш ~1ч.
 *
 * @return array<string,array>
 */
function bsi_crosstour_tour_map(bool $force = false): array
{
  $cache_key = 'crosstour_map';
  if (!$force) {
    $cached = CacheService::get($cache_key, 'samotour');
    if (is_array($cached)) {
      return $cached;
    }
  }

  $map = [];
  $townfrom = BSI_CROSSTOUR_TOWNFROM;
  $endpoints = SamoService::endpoints();

  $states_resp = $endpoints->searchCrosstourStates();
  $states = ($states_resp['ok'] ?? false) ? ($states_resp['data']['SearchCrosstour_STATES'] ?? []) : [];

  foreach ($states as $st) {
    $state_id = (int) ($st['id'] ?? 0);
    if (!$state_id) {
      continue;
    }

    $tours_resp = $endpoints->searchCrosstourTours([
      'TOWNFROMINC' => $townfrom,
      'STATEINC' => $state_id,
    ]);
    $tours = ($tours_resp['ok'] ?? false) ? ($tours_resp['data']['SearchCrosstour_TOURS'] ?? []) : [];

    foreach ($tours as $t) {
      $slug = bsi_crosstour_slug_from_url((string) ($t['url'] ?? ''));
      if ($slug === '') {
        continue;
      }
      $map[$slug] = [
        'TOWNFROMINC' => $townfrom,
        'STATEINC' => $state_id,
        'TOURINC' => (int) ($t['id'] ?? 0),
        'name' => (string) ($t['name'] ?? ''),
        'type' => (string) ($t['type'] ?? ''),
        'state_name' => (string) ($st['name'] ?? ''),
      ];
    }
  }

  CacheService::set($cache_key, $map, HOUR_IN_SECONDS, 'samotour');
  return $map;
}

/**
 * Резолв события → crosstour ref или null (не заведено в Само).
 *
 * @return array|null
 */
function bsi_crosstour_resolve_event(int $event_id): ?array
{
  if (!$event_id) {
    return null;
  }
  $slug = (string) get_post_field('post_name', $event_id);
  if ($slug === '') {
    return null;
  }
  $map = bsi_crosstour_tour_map();
  return $map[$slug] ?? null;
}

/**
 * Включён ли авто-режим Само для события (поле event_data_source != 'manual').
 */
function bsi_crosstour_event_enabled(int $event_id): bool
{
  if (!$event_id || !function_exists('get_field')) {
    return false;
  }
  $src = (string) get_field('event_data_source', $event_id);
  return $src !== 'manual';
}

/**
 * Имя тура по TOURINC (для фильтра отелей при ручной ссылке).
 */
function bsi_crosstour_tour_name(int $townfrom, int $state, int $tour): string
{
  if (!$state || !$tour) {
    return '';
  }
  $resp = SamoService::endpoints()->searchCrosstourTours([
    'TOWNFROMINC' => $townfrom,
    'STATEINC' => $state,
  ]);
  $tours = ($resp['ok'] ?? false) ? ($resp['data']['SearchCrosstour_TOURS'] ?? []) : [];
  foreach ($tours as $t) {
    if ((int) ($t['id'] ?? 0) === $tour) {
      return (string) ($t['name'] ?? '');
    }
  }
  return '';
}

/**
 * Ref из ручной ссылки search_crosstour (нужны STATEINC + TOURINC/TOURS).
 *
 * @return array|null
 */
function bsi_crosstour_ref_from_url(string $url): ?array
{
  if ($url === '' || stripos($url, 'search_crosstour') === false) {
    return null;
  }
  $parsed = wp_parse_url($url);
  if (empty($parsed['query'])) {
    return null;
  }
  parse_str($parsed['query'], $q);

  $state = (int) ($q['STATEINC'] ?? 0);
  $tour = (int) ($q['TOURINC'] ?? ($q['TOURS'] ?? 0));
  $townfrom = (int) ($q['TOWNFROMINC'] ?? 0);
  if (!$townfrom) {
    $townfrom = BSI_CROSSTOUR_TOWNFROM;
  }
  // Достаточно STATEINC: тур (TOURINC) при отсутствии резолвится в offer по TOURS(state).
  if (!$state) {
    return null;
  }

  // Имя/тур резолвятся в offer (AJAX), не на рендере — рендер дешёвый.
  $ref = [
    'TOWNFROMINC' => $townfrom,
    'STATEINC' => $state,
    'TOURINC' => $tour,
    'name' => '',
  ];

  // Даты/ночи из ссылки (известно-валидные) — приоритетнее автоподбора.
  foreach (['CHECKIN_BEG', 'CHECKIN_END'] as $k) {
    if (!empty($q[$k])) {
      $ref[$k] = preg_replace('/\D/', '', (string) $q[$k]);
    }
  }
  foreach (['NIGHTS_FROM', 'NIGHTS_TILL'] as $k) {
    if (!empty($q[$k])) {
      $ref[$k] = (int) $q[$k];
    }
  }

  return $ref;
}

/**
 * Итоговый ref события: сперва ручная ссылка (search_crosstour со STATEINC+TOURINC),
 * иначе авто-связь по slug. null — Само недоступно / ручной режим.
 *
 * @return array|null
 */
function bsi_crosstour_event_ref(int $event_id): ?array
{
  if (!bsi_crosstour_event_enabled($event_id)) {
    return null;
  }
  $url = function_exists('get_field') ? trim((string) get_field('tour_booking_url', $event_id)) : '';
  if ($url !== '') {
    $ref = bsi_crosstour_ref_from_url($url);
    if ($ref) {
      return $ref;
    }
  }
  return bsi_crosstour_resolve_event($event_id);
}

/**
 * bitmask validDates (от startDate) → массив дат 'Ymd'.
 *
 * @return string[]
 */
function bsi_crosstour_valid_dates($checkin_node): array
{
  if (!is_array($checkin_node)) {
    return [];
  }
  $mask = (string) ($checkin_node['validDates'] ?? '');
  $start = (string) ($checkin_node['startDate'] ?? '');
  if ($mask === '' || $start === '') {
    return [];
  }
  $start_ts = strtotime($start);
  if (!$start_ts) {
    return [];
  }

  $dates = [];
  $len = strlen($mask);
  for ($i = 0; $i < $len; $i++) {
    if ($mask[$i] === '1') {
      $ts = strtotime("+{$i} day", $start_ts);
      if ($ts) {
        $dates[] = date('Ymd', $ts);
      }
    }
  }
  return $dates;
}

/**
 * Мин. цена из массива prices (per-person; ADULT=2 → /2, как у экскурсий).
 */
function bsi_crosstour_min_price(array $prices): ?int
{
  $min = null;
  foreach ($prices as $row) {
    if (!is_array($row)) {
      continue;
    }
    $val = $row['convertedPriceNumber'] ?? $row['convertedPrice'] ?? $row['price'] ?? null;
    if ($val === null || $val === '') {
      continue;
    }
    $num = (float) preg_replace('/[^\d.]/', '', (string) $val);
    if ($num <= 0) {
      continue;
    }
    if ($min === null || $num < $min) {
      $min = $num;
    }
  }
  if ($min === null) {
    return null;
  }
  return (int) round($min / 2);
}

/**
 * Из строк PRICES — мин. цена с оригинальной валютой (для переключателя валют).
 * Цены даны за 2 взрослых → делим на 2 (per-person), как у экскурсий.
 *
 * @return array{rub:?int,original:?float,currency:?string}
 */
function bsi_crosstour_price_from_rows(array $rows): array
{
  $best = null;
  foreach ($rows as $r) {
    if (!is_array($r)) {
      continue;
    }
    $rub_raw = (isset($r['convertedPriceNumber']) && $r['convertedPriceNumber'] !== '')
      ? (float) $r['convertedPriceNumber']
      : null;
    if ($rub_raw === null || $rub_raw <= 0) {
      continue;
    }
    if ($best === null || $rub_raw < $best['rub']) {
      $best = [
        'rub' => $rub_raw,
        'orig' => $r['price'] ?? null,
        'cur' => $r['currency'] ?? null,
      ];
    }
  }
  if ($best === null) {
    return ['rub' => null, 'original' => null, 'currency' => null];
  }

  $original = null;
  $currency = null;
  $o = ($best['orig'] !== null && $best['orig'] !== '')
    ? (float) preg_replace('/[^\d.]/', '', (string) $best['orig'])
    : 0.0;
  $c = strtoupper(trim((string) ($best['cur'] ?? '')));
  if ($o > 0 && $c !== '' && $c !== 'RUB') {
    $original = round($o / 2, 2);
    $currency = $c;
  }

  return [
    'rub' => (int) round($best['rub'] / 2),
    'original' => $original,
    'currency' => $currency,
  ];
}

/**
 * Отели Само, отфильтрованные к конкретному туру (имя обычно «<тур>: <отель>»).
 *
 * @return array<int,array{name:string,star:string,star_key:int}>
 */
function bsi_crosstour_filter_hotels(array $hotels, string $tour_name = ''): array
{
  $out = [];
  foreach ($hotels as $h) {
    if (!is_array($h)) {
      continue;
    }
    $name = trim((string) ($h['name'] ?? ''));
    if ($name === '') {
      continue;
    }
    $out[] = [
      'name' => bsi_crosstour_hotel_display_name($name),
      'star' => (string) ($h['star'] ?? ''),
      'star_key' => (int) ($h['starKey'] ?? 0),
      'hotel_key' => (int) ($h['id'] ?? 0),
      'room' => '',
      'meal' => '',
      'price_rub' => null,
      'price_original' => null,
      'price_currency' => null,
    ];
  }
  return $out;
}

/**
 * Имя отеля из «<тур> (<Отель>)» → «<Отель>». Без скобок — как есть.
 */
function bsi_crosstour_hotel_display_name(string $name): string
{
  if (preg_match('/\(([^)]+)\)\s*$/u', $name, $m)) {
    $inner = trim($m[1]);
    if ($inner !== '') {
      return $inner;
    }
  }
  return $name;
}

/**
 * Отели из строк PRICES. Для событийных туров НЕ объединяем по звёздности/отелю:
 * каждая комбинация отель × номер × питание = отдельная карточка (разные билеты/номера).
 * Дедуп только точных дублей (тот же отель+номер+питание) — мин. цена per-person.
 *
 * @return array<int,array{name:string,star:string,star_key:int,price_rub:?int}>
 */
function bsi_crosstour_hotels_from_prices(array $rows, array $ref = []): array
{
  $by = [];
  foreach ($rows as $r) {
    if (!is_array($r)) {
      continue;
    }
    $name = trim((string) ($r['hotel'] ?? ''));
    if ($name === '') {
      continue;
    }

    $room = trim((string) ($r['room'] ?? ''));
    $meal = trim((string) ($r['mealGroup'] ?? ($r['meal'] ?? '')));

    // Ключ — конкретная комбинация (отель + номер + питание), не просто отель.
    $key = (int) ($r['hotelKey'] ?? 0) . '_' . (int) ($r['roomKey'] ?? 0) . '_' . (int) ($r['mealKey'] ?? 0);
    if ($key === '0_0_0') {
      $key = md5($name . '|' . $room . '|' . $meal);
    }

    $rub = (isset($r['convertedPriceNumber']) && $r['convertedPriceNumber'] !== '')
      ? (int) $r['convertedPriceNumber']
      : null;
    $pp = ($rub !== null && $rub > 0) ? (int) round($rub / 2) : null;

    $orig = null;
    $cur = null;
    $o = (isset($r['price']) && $r['price'] !== '')
      ? (float) preg_replace('/[^\d.]/', '', (string) $r['price'])
      : 0.0;
    $c = strtoupper(trim((string) ($r['currency'] ?? '')));
    if ($o > 0 && $c !== '' && $c !== 'RUB') {
      $orig = round($o / 2, 2);
      $cur = $c;
    }

    if (!isset($by[$key])) {
      $by[$key] = [
        'name' => bsi_crosstour_hotel_display_name($name),
        'star' => (string) ($r['star'] ?? ''),
        'star_key' => (int) ($r['starKey'] ?? 0),
        'hotel_key' => (int) ($r['hotelKey'] ?? 0),
        'room_key' => (int) ($r['roomKey'] ?? 0),
        'room' => $room,
        'meal' => $meal,
        'price_rub' => $pp,
        'price_original' => $pp !== null ? $orig : null,
        'price_currency' => $pp !== null ? $cur : null,
        'booking_url' => bsi_crosstour_row_booking_url($ref, $r),
      ];
    } elseif ($pp !== null && ($by[$key]['price_rub'] === null || $pp < $by[$key]['price_rub'])) {
      $by[$key]['price_rub'] = $pp;
      $by[$key]['price_original'] = $orig;
      $by[$key]['price_currency'] = $cur;
      $by[$key]['booking_url'] = bsi_crosstour_row_booking_url($ref, $r);
    }
  }

  $list = array_values($by);
  usort($list, static function ($a, $b) {
    $an = $a['price_rub'] === null;
    $bn = $b['price_rub'] === null;
    if ($an !== $bn) {
      return $an ? 1 : -1;
    }
    return (int) $a['price_rub'] <=> (int) $b['price_rub'];
  });
  return $list;
}

/**
 * Ссылка на онлайн-бронирование Само (search_crosstour) из ref.
 */
function bsi_crosstour_booking_url(array $ref): string
{
  $state = (int) ($ref['STATEINC'] ?? 0);
  $tour = (int) ($ref['TOURINC'] ?? 0);
  $townfrom = (int) ($ref['TOWNFROMINC'] ?? BSI_CROSSTOUR_TOWNFROM);
  if (!$state || !$tour) {
    return '';
  }

  $params = [
    'TOWNFROMINC' => $townfrom,
    'STATEINC' => $state,
    'TOURINC' => $tour,
  ];

  // Даты/ночи из ref (если заданы в ссылке) — чтобы Само открылся на нужных датах.
  foreach (['CHECKIN_BEG', 'CHECKIN_END'] as $k) {
    if (!empty($ref[$k])) {
      $params[$k] = preg_replace('/\D/', '', (string) $ref[$k]);
    }
  }
  foreach (['NIGHTS_FROM', 'NIGHTS_TILL'] as $k) {
    if (!empty($ref[$k])) {
      $params[$k] = (int) $ref[$k];
    }
  }

  $params += [
    'ADULT' => 2,
    'CURRENCY' => 1,
    'CHILD' => 0,
    'TOWNS_ANY' => 1,
    'STARS_ANY' => 1,
    'HOTELS_ANY' => 1,
    'MEALS_ANY' => 1,
    'ROOMS_ANY' => 1,
    'FREIGHT' => 1,
    'PRICEPAGE' => 1,
    'DOLOAD' => 1,
  ];

  return 'https://online.bsigroup.ru/search_crosstour?' . http_build_query($params);
}

/**
 * Ссылка брони на конкретный номер (строка PRICES): своя дата/ночи/отель/номер + DOLOAD.
 */
function bsi_crosstour_row_booking_url(array $ref, array $row): string
{
  $state = (int) ($ref['STATEINC'] ?? 0);
  $tour = (int) ($ref['TOURINC'] ?? 0);
  $townfrom = (int) ($ref['TOWNFROMINC'] ?? BSI_CROSSTOUR_TOWNFROM);
  if (!$state || !$tour) {
    return '';
  }

  $checkin = preg_replace('/\D/', '', (string) ($row['checkIn'] ?? ''));
  $nights = (int) ($row['nights'] ?? 0);
  $hk = (int) ($row['hotelKey'] ?? 0);

  // Порядок/набор как у рабочей ссылки Само: *_ANY=1 остаются вместе с HOTELS=<key>.
  $params = [
    'TOWNFROMINC' => $townfrom,
    'STATEINC' => $state,
    'TOURINC' => $tour,
  ];
  if ($checkin !== '') {
    $params['CHECKIN_BEG'] = $checkin;
    $params['NIGHTS_FROM'] = $nights > 0 ? $nights : 1;
    $params['CHECKIN_END'] = $checkin;
    $params['NIGHTS_TILL'] = $nights > 0 ? $nights : 30;
  }
  $params['ADULT'] = 2;
  $params['CURRENCY'] = 1;
  $params['CHILD'] = 0;
  $params['HOTELS_ANY'] = 1;
  if ($hk) {
    $params['HOTELS'] = $hk;
  }
  $params['MEALS_ANY'] = 1;
  $params['ROOMS_ANY'] = 1;
  $params['FREIGHT'] = 1;
  $params['PRICEPAGE'] = 1;
  $params['DOLOAD'] = 1;

  return 'https://online.bsigroup.ru/search_crosstour?' . http_build_query($params);
}

/**
 * Оффер: мин. цена + отели + даты + ночи + ссылка брони. Кеш ~3ч.
 *
 * @return array{price_rub:?int,currency:string,hotels:array,dates:array,nights:array,booking_url:string}
 */
function bsi_crosstour_event_offer(array $ref, bool $force = false): array
{
  $townfrom = (int) ($ref['TOWNFROMINC'] ?? BSI_CROSSTOUR_TOWNFROM);
  $state = (int) ($ref['STATEINC'] ?? 0);
  $tour = (int) ($ref['TOURINC'] ?? 0);

  // Ссылка без TOURINC → берём тур из TOURS(state): один — используем; несколько — первый.
  if ($state && !$tour) {
    $tours_resp = SamoService::endpoints()->searchCrosstourTours([
      'TOWNFROMINC' => $townfrom,
      'STATEINC' => $state,
    ]);
    $tours = ($tours_resp['ok'] ?? false) ? ($tours_resp['data']['SearchCrosstour_TOURS'] ?? []) : [];
    if (!empty($tours)) {
      $tour = (int) ($tours[0]['id'] ?? 0);
      $ref['TOURINC'] = $tour; // иначе per-row booking_url строится с tour=0 → нет кнопки
      if (empty($ref['name']) && isset($tours[0]['name'])) {
        $ref['name'] = (string) $tours[0]['name'];
      }
    }
  }

  $empty = [
    'price_rub' => null,
    'price_original' => null,
    'price_currency' => null,
    'currency' => 'RUB',
    'hotels' => [],
    'dates' => [],
    'nights' => ['from' => 0, 'till' => 0],
    'booking_url' => bsi_crosstour_booking_url($ref),
  ];
  if (!$state || !$tour) {
    return $empty;
  }

  // Кэш на уровне тура — показываем ВСЕ доступные комбинации (даты/ночи/номера),
  // а не только узкий слот из ссылки. Узкие даты ссылки идут лишь в booking_url.
  $cache_key = 'crosstour_offer_v7_' . $townfrom . '_' . $state . '_' . $tour;
  if (!$force) {
    $cached = CacheService::get($cache_key, 'samotour');
    if (is_array($cached)) {
      return $cached;
    }
  }

  $endpoints = SamoService::endpoints();
  $base = ['TOWNFROMINC' => $townfrom, 'STATEINC' => $state];
  $flags = [
    'TOWNS_ANY' => 1,
    'STARS_ANY' => 1,
    'HOTELS_ANY' => 1,
    'MEALS_ANY' => 1,
    'ROOMS_ANY' => 1,
    'FREIGHT' => 1,
  ];

  // Ночи — весь набор тура (min..max из NIGHTS.nights), НЕ default (может быть вне диапазона).
  $nights_resp = $endpoints->searchCrosstourNights($base);
  $nights_node = ($nights_resp['ok'] ?? false) ? ($nights_resp['data']['SearchCrosstour_NIGHTS'] ?? []) : [];
  $nights_list = (isset($nights_node['nights']) && is_array($nights_node['nights']))
    ? array_values(array_filter(array_map('intval', $nights_node['nights'])))
    : [];
  if (!empty($nights_list)) {
    $n_from = min($nights_list);
    $n_till = max($nights_list);
  } else {
    $n_from = (int) ($nights_node['default']['from'] ?? 1);
    $n_till = (int) ($nights_node['default']['till'] ?? max($n_from, 30));
  }

  // Даты — все валидные из ALL (полный диапазон), не узкий слот ссылки.
  $all_resp = $endpoints->searchCrosstourAll(array_merge($base, $flags, [
    'TOURS' => $tour,
    'ADULT' => 2,
    'CHILD' => 0,
    'CURRENCY' => 1,
    'NIGHTS_FROM' => $n_from,
    'NIGHTS_TILL' => $n_till,
  ]));
  $all = ($all_resp['ok'] ?? false) ? ($all_resp['data']['SearchCrosstour_ALL'] ?? []) : [];
  $dates = bsi_crosstour_valid_dates($all['CHECKIN_BEG'] ?? []);
  $checkin_beg = $dates[0] ?? '';
  $checkin_end = !empty($dates) ? (string) end($dates) : $checkin_beg;

  // PRICES → мин. цена + ВСЕ номера. Нюанс API: запрос по всем отелям отдаёт
  // 1 строку/отель (самый дешёвый номер); чтобы получить все номера/трибуны —
  // запрашиваем PRICES по каждому отелю отдельно (HOTELS=<key>).
  $price_rub = null;
  $price_original = null;
  $price_currency = null;
  $hotels = [];
  if ($checkin_beg !== '') {
    $price_params = array_merge($base, $flags, [
      'TOURS' => $tour,
      'ADULT' => 2,
      'CHILD' => 0,
      'CURRENCY' => 1,
      'CHECKIN_BEG' => $checkin_beg,
      'CHECKIN_END' => $checkin_end,
      'NIGHTS_FROM' => $n_from,
      'NIGHTS_TILL' => $n_till,
    ]);

    // 1) Список отелей тура (по строке на отель).
    $base_resp = $endpoints->searchCrosstourPrices($price_params);
    $base_node = ($base_resp['ok'] ?? false) ? ($base_resp['data']['SearchCrosstour_PRICES'] ?? []) : [];
    $base_rows = $base_node['prices'] ?? [];

    $hotel_keys = [];
    foreach ($base_rows as $r) {
      $hk = (int) ($r['hotelKey'] ?? 0);
      if ($hk && !in_array($hk, $hotel_keys, true)) {
        $hotel_keys[] = $hk;
      }
    }
    $hotel_keys = array_slice($hotel_keys, 0, 20);

    // 2) По каждому отелю — все номера/трибуны.
    $rows = [];
    foreach ($hotel_keys as $hk) {
      $h_resp = $endpoints->searchCrosstourPrices(array_merge($price_params, ['HOTELS' => $hk]));
      $h_node = ($h_resp['ok'] ?? false) ? ($h_resp['data']['SearchCrosstour_PRICES'] ?? []) : [];
      foreach (($h_node['prices'] ?? []) as $hr) {
        $rows[] = $hr;
      }
    }
    if (empty($rows)) {
      $rows = $base_rows;
    }

    $price = bsi_crosstour_price_from_rows($rows);
    $price_rub = $price['rub'];
    $price_original = $price['original'];
    $price_currency = $price['currency'];
    $hotels = bsi_crosstour_hotels_from_prices($rows, $ref);
  }

  // Фолбэк отелей из HOTELS, если PRICES пуст.
  if (empty($hotels)) {
    $hotels_resp = $endpoints->searchCrosstourHotels($base);
    $hotels_raw = ($hotels_resp['ok'] ?? false) ? ($hotels_resp['data']['SearchCrosstour_HOTELS'] ?? []) : [];
    $hotels = bsi_crosstour_filter_hotels($hotels_raw);
  }

  $offer = [
    'price_rub' => $price_rub,
    'price_original' => $price_original,
    'price_currency' => $price_currency,
    'currency' => 'RUB',
    'hotels' => $hotels,
    'dates' => $dates,
    'nights' => ['from' => $n_from, 'till' => $n_till],
    'booking_url' => bsi_crosstour_booking_url($ref),
  ];

  CacheService::set($cache_key, $offer, 3 * HOUR_IN_SECONDS, 'samotour');
  return $offer;
}

/**
 * Высокоуровневое: данные события (ref + offer) или null (нет Само / ручной режим).
 *
 * @return array{ref:array,offer:array}|null
 */
function bsi_crosstour_event_data(int $event_id, bool $force = false): ?array
{
  $ref = bsi_crosstour_event_ref($event_id);
  if (!$ref) {
    return null;
  }
  return [
    'ref' => $ref,
    'offer' => bsi_crosstour_event_offer($ref, $force),
  ];
}
