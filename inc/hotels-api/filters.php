<?php

/**
 * Фильтры каталога отелей: разбор адреса, справочники и подписи.
 *
 * Фильтры живут в query-параметрах (`?stars=4,5&amenities=basseyn&sort=price`),
 * чтобы не мешать сегментам `/kurort/{slug}/page/2/` — те остаются за курортом
 * и страницей. Имена параметров совпадают с полями хаба, кроме `stars`:
 * у хаба это пара `min_stars`/`max_stars`, а на сайте — набор галочек.
 */

/** Сортировки, которые понимает и хаб, и каталог. */
function bsi_hotels_api_sort_options(): array
{
  return [
    'name-asc' => ['label' => 'По названию', 'sort' => 'name', 'order' => 'asc'],
    'price-asc' => ['label' => 'Сначала дешёвые', 'sort' => 'price', 'order' => 'asc'],
    'price-desc' => ['label' => 'Сначала дорогие', 'sort' => 'price', 'order' => 'desc'],
    'stars-desc' => ['label' => 'Сначала звёзднее', 'sort' => 'stars', 'order' => 'desc'],
    'stars-asc' => ['label' => 'Сначала проще', 'sort' => 'stars', 'order' => 'asc'],
    'updated_at-desc' => ['label' => 'Недавно обновлённые', 'sort' => 'updated_at', 'order' => 'desc'],
  ];
}

/** Переключатели «да/нет» каталога: ключ запроса => подпись. */
function bsi_hotels_api_flag_options(): array
{
  return [
    'has_photo' => 'С фотографиями',
    'has_prices' => 'С ценами',
    'adults_only' => 'Только для взрослых',
  ];
}

/** Варианты линии пляжа. */
function bsi_hotels_api_beach_options(): array
{
  return [
    1 => 'Первая линия',
    2 => 'Не дальше второй',
    3 => 'Не дальше третьей',
  ];
}

/**
 * Фильтры текущего запроса. Значения уже нормализованы: в шаблон и в хаб
 * уходит одно и то же представление.
 *
 * @param array|null $source обычно $_GET; в AJAX — разобранная строка запроса
 * @return array{stars: int[], amenities: string[], type: string, beach_line: int,
 *               flags: string[], q: string, sort: string}
 */
function bsi_hotels_api_catalog_filters(?array $source = null): array
{
  $source = $source ?? $_GET;

  /* Форма шлёт наборы массивами (stars[]=4&stars[]=5), адрес — строкой
     (?stars=4,5). Принимаем оба вида: ссылками делятся, форму отправляют. */
  $as_list = static function ($value): array {
    if (is_array($value)) {
      return $value;
    }

    return explode(',', (string) $value);
  };

  $stars = [];
  foreach ($as_list($source['stars'] ?? '') as $star) {
    $star = (int) trim($star);
    if ($star >= 1 && $star <= 5) {
      $stars[$star] = $star;
    }
  }
  sort($stars);

  $amenities = [];
  foreach ($as_list($source['amenities'] ?? '') as $slug) {
    $slug = sanitize_title(trim($slug));
    if ($slug !== '') {
      $amenities[$slug] = $slug;
    }
  }

  $flags = [];
  foreach (array_keys(bsi_hotels_api_flag_options()) as $flag) {
    if (!empty($source[$flag])) {
      $flags[] = $flag;
    }
  }

  $sort = (string) ($source['sort'] ?? '');
  if (!isset(bsi_hotels_api_sort_options()[$sort])) {
    $sort = 'name-asc';
  }

  $beach = (int) ($source['beach_line'] ?? 0);

  return [
    'stars' => array_values($stars),
    'amenities' => array_values($amenities),
    'type' => sanitize_title((string) ($source['type'] ?? '')),
    'beach_line' => isset(bsi_hotels_api_beach_options()[$beach]) ? $beach : 0,
    'flags' => $flags,
    'q' => trim(wp_strip_all_tags((string) ($source['q'] ?? ''))),
    'sort' => $sort,
  ];
}

/**
 * Фильтры в виде параметров запроса к хабу.
 */
function bsi_hotels_api_filters_to_params(array $filters): array
{
  $params = [];

  if ($filters['stars']) {
    // Хаб принимает список: «5 и 3 звезды» — это две категории, а не диапазон.
    $params['stars'] = implode(',', $filters['stars']);
  }

  if ($filters['amenities']) {
    $params['amenities'] = implode(',', $filters['amenities']);
  }

  if ($filters['type'] !== '') {
    $params['type'] = $filters['type'];
  }

  if ($filters['beach_line'] > 0) {
    $params['beach_line'] = $filters['beach_line'];
  }

  foreach ($filters['flags'] as $flag) {
    $params[$flag] = 'true';
  }

  if ($filters['q'] !== '') {
    $params['q'] = $filters['q'];
  }

  $sort = bsi_hotels_api_sort_options()[$filters['sort']];
  $params['sort'] = $sort['sort'];
  $params['order'] = $sort['order'];

  return $params;
}

/** Фильтры в виде query-строки для адреса каталога. */
function bsi_hotels_api_filters_to_query(array $filters): array
{
  $query = [];

  if ($filters['stars']) {
    $query['stars'] = implode(',', $filters['stars']);
  }

  if ($filters['amenities']) {
    $query['amenities'] = implode(',', $filters['amenities']);
  }

  if ($filters['type'] !== '') {
    $query['type'] = $filters['type'];
  }

  if ($filters['beach_line'] > 0) {
    $query['beach_line'] = $filters['beach_line'];
  }

  foreach ($filters['flags'] as $flag) {
    $query[$flag] = '1';
  }

  if ($filters['q'] !== '') {
    $query['q'] = $filters['q'];
  }

  if ($filters['sort'] !== 'name-asc') {
    $query['sort'] = $filters['sort'];
  }

  return $query;
}

/** Выбран ли хоть один фильтр — от этого зависят подписи и мета-теги. */
function bsi_hotels_api_filters_active(array $filters): bool
{
  return (bool) bsi_hotels_api_filters_to_query(array_merge($filters, ['sort' => 'name-asc']));
}

/**
 * Удобства хаба для панели фильтров: только те, что относятся к отелю,
 * разложенные по группам.
 *
 * @return array<string, array> группа => удобства
 */
function bsi_hotels_api_amenity_groups(): array
{
  static $groups = null;
  if ($groups !== null) {
    return $groups;
  }

  $groups = [];
  $client = bsi_hotels_api();

  if (!$client) {
    return $groups;
  }

  try {
    foreach ($client->amenities() as $amenity) {
      // scope room — удобства номера, отель по ним не фильтруется.
      if (($amenity['scope'] ?? 'hotel') !== 'hotel' || empty($amenity['slug'])) {
        continue;
      }

      $group = (string) ($amenity['group'] ?? '');
      $group = $group !== '' ? $group : 'Прочее';

      $groups[$group][] = [
        'slug' => (string) $amenity['slug'],
        'name' => (string) ($amenity['name'] ?? $amenity['slug']),
        'icon' => (string) ($amenity['icon'] ?? ''),
        'popular' => !empty($amenity['is_popular']),
        'hotels' => (int) ($amenity['hotels'] ?? 0),
      ];
    }
  } catch (HotelsApiException $e) {
    return $groups = [];
  }

  return $groups;
}

/**
 * Виды объектов размещения из хаба: апарт-отель, вилла, курортный отель.
 */
function bsi_hotels_api_hotel_types(): array
{
  static $types = null;
  if ($types !== null) {
    return $types;
  }

  $types = [];
  $client = bsi_hotels_api();

  if (!$client) {
    return $types;
  }

  try {
    foreach ($client->hotelTypes() as $type) {
      if (empty($type['slug'])) {
        continue;
      }

      $types[] = [
        'slug' => (string) $type['slug'],
        'name' => (string) ($type['name'] ?? $type['slug']),
        'icon' => (string) ($type['icon'] ?? ''),
        'hotels' => (int) ($type['hotels'] ?? 0),
      ];
    }
  } catch (HotelsApiException $e) {
    return $types = [];
  }

  return $types;
}
