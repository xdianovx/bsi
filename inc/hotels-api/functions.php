<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/HotelsApiClient.php';
require_once __DIR__ . '/hotel-page.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/sitemap.php';

/**
 * Общий экземпляр клиента. null, если API не настроен.
 */
function bsi_hotels_api(): ?HotelsApiClient
{
  static $client = null;
  static $tried = false;

  if ($tried) {
    return $client;
  }

  $tried = true;

  try {
    $client = new HotelsApiClient(bsi_hotels_api_config());
  } catch (Throwable $e) {
    $client = null;
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('BSIHOTELS: ' . $e->getMessage());
    }
  }

  return $client;
}

/**
 * Слаг страны в хабе отелей для страны из WordPress.
 *
 * Источник — поле hotels_api_country. Если оно пустое, пробуем совпадение
 * по названию: «Турция» в WordPress и «Турция» в хабе — одна страна.
 * Возвращает '' , если связи нет — тогда каталог берётся из CPT hotel.
 */
function bsi_hotels_api_country_slug(int $country_id): string
{
  if ($country_id <= 0) {
    return '';
  }

  static $cache = [];
  if (isset($cache[$country_id])) {
    return $cache[$country_id];
  }

  $slug = '';

  if (function_exists('get_field')) {
    $slug = trim((string) get_field('hotels_api_country', $country_id));
  }

  if ($slug === '') {
    $slug = bsi_hotels_api_match_country_by_title($country_id);
  }

  return $cache[$country_id] = $slug;
}

/**
 * Совпадение по названию — запасной путь, пока связь не проставлена руками.
 */
function bsi_hotels_api_match_country_by_title(int $country_id): string
{
  $title = trim((string) get_the_title($country_id));
  if ($title === '') {
    return '';
  }

  $client = bsi_hotels_api();
  if (!$client) {
    return '';
  }

  try {
    foreach ($client->countries() as $country) {
      $name = trim((string) ($country['name'] ?? ''));
      if ($name !== '' && mb_strtolower($name) === mb_strtolower($title)) {
        return (string) ($country['slug'] ?? '');
      }
    }
  } catch (Throwable $e) {
    return '';
  }

  return '';
}

/**
 * Есть ли у страны каталог в хабе отелей.
 */
function bsi_hotels_api_enabled_for_country(int $country_id): bool
{
  return bsi_hotels_api_country_slug($country_id) !== '';
}

/**
 * Цена за ночь из хаба. Валюта — контракта поставщика, без нашего курса
 * и наценки, поэтому показываем её как есть.
 */
function bsi_hotels_api_format_price($price): string
{
  if (!is_array($price) || !isset($price['amount'])) {
    return '';
  }

  $amount = (float) $price['amount'];
  if ($amount <= 0) {
    return '';
  }

  $symbols = ['USD' => '$', 'EUR' => '€', 'RUB' => '₽'];
  $currency = (string) ($price['currency'] ?? '');

  return number_format($amount, 0, ',', ' ') . ' ' . ($symbols[$currency] ?? $currency);
}

/**
 * Ссылка на каталог отелей страны: /country/{slug}/hotel/
 */
function bsi_hotels_api_catalog_url(WP_Post $country): string
{
  return home_url('/country/' . $country->post_name . '/hotel/');
}

/**
 * Адрес каталога по курорту: /country/{страна}/hotel/kurort/{курорт}/
 */
function bsi_hotels_api_resort_url(string $catalog_url, string $resort_slug): string
{
  return trailingslashit($catalog_url) . 'kurort/' . $resort_slug . '/';
}

/**
 * Курорт текущего запроса: слаг и название из хаба.
 * Название нужно заголовку и мета-тегам, поэтому берётся из справочника.
 *
 * @return array{slug: string, name: string}|null
 */
function bsi_hotels_api_current_resort(int $country_id): ?array
{
  $slug = sanitize_title((string) get_query_var('country_hotel_resort'));
  if ($slug === '') {
    return null;
  }

  $api_country = bsi_hotels_api_country_slug($country_id);
  $client = bsi_hotels_api();

  if ($api_country === '' || !$client) {
    return null;
  }

  try {
    foreach ($client->cities($api_country) as $city) {
      if (($city['slug'] ?? '') === $slug) {
        return ['slug' => $slug, 'name' => (string) ($city['name'] ?? $slug)];
      }
    }
  } catch (HotelsApiException $e) {
    return null;
  }

  return null;
}

/**
 * Список отелей для текущего запроса каталога. Результат мемоизируется:
 * его читают и шаблон, и мета-теги в wp_head, которые собираются раньше.
 *
 * Страницу и курорт можно передать явно — так их задаёт AJAX-подгрузка каталога,
 * где query vars текущего запроса не относятся к делу.
 *
 * @return array{list: array, resorts: array, error: string, per_page: int, resort: string}
 */
function bsi_hotels_api_catalog_query(WP_Post $country, ?int $paged = null, ?string $resort = null): array
{
  static $cache = [];

  $paged = max(1, $paged ?? (int) get_query_var('paged'));
  $resort = sanitize_title($resort ?? (string) get_query_var('country_hotel_resort'));
  $key = $country->ID . ':' . $paged . ':' . $resort;

  if (isset($cache[$key])) {
    return $cache[$key];
  }

  $per_page = 24;
  $result = [
    'list' => ['items' => [], 'total' => 0, 'page' => $paged, 'limit' => $per_page, 'pages' => 0],
    'resorts' => [],
    'error' => '',
    'per_page' => $per_page,
    'resort' => $resort,
  ];

  $api_country = bsi_hotels_api_country_slug((int) $country->ID);
  $client = bsi_hotels_api();

  if ($api_country === '' || !$client) {
    return $cache[$key] = $result;
  }

  try {
    $result['list'] = $client->hotels(array_filter([
      'country' => $api_country,
      'city' => $resort,
      'page' => $paged,
      'limit' => $per_page,
      'sort' => 'name',
      'order' => 'asc',
    ]));

    $result['list']['items'] = bsi_hotels_api_filter_items($result['list']['items']);
  } catch (HotelsApiException $e) {
    $result['error'] = $e->getMessage();
  }

  // Курорты просим отдельно: список отелей может отвалиться по таймауту,
  // но фильтр курортов должен остаться — иначе из пустой выдачи не выбраться.
  try {
    $result['resorts'] = $client->cities($api_country);
  } catch (HotelsApiException $e) {
    // Фильтра не будет, каталог из-за этого не ломается.
  }

  return $cache[$key] = $result;
}

/**
 * Служебные записи поставщика, приезжающие в хаб под видом отелей:
 * «По программе тура: …», «Отели по программе рекламного тура».
 * Это не объекты размещения, показывать их в каталоге нечем.
 */
function bsi_hotels_api_is_service_record(array $hotel): bool
{
  $name = trim((string) ($hotel['name'] ?? ''));
  if ($name === '') {
    return true;
  }

  $patterns = apply_filters('bsi_hotels_api_service_name_patterns', [
    '~^по\s+программе\s+тура~iu',
    '~^отели\s+по\s+программе~iu',
  ]);

  foreach ($patterns as $pattern) {
    if (preg_match($pattern, $name)) {
      return true;
    }
  }

  return false;
}

/**
 * Отсеивает служебные записи из выборки отелей.
 */
function bsi_hotels_api_filter_items(array $items): array
{
  return array_values(array_filter(
    $items,
    static fn($hotel) => is_array($hotel) && !bsi_hotels_api_is_service_record($hotel)
  ));
}

/**
 * Адрес карточки отеля. Слаг есть не у всех записей хаба, поэтому запасной
 * ключ — числовой идентификатор: по нему API отдаёт ту же карточку.
 */
function bsi_hotels_api_hotel_key(array $hotel): string
{
  $slug = trim((string) ($hotel['slug'] ?? ''));
  if ($slug !== '') {
    return $slug;
  }

  $id = (int) ($hotel['id'] ?? 0);

  return $id > 0 ? (string) $id : '';
}

function bsi_hotels_api_hotel_url(string $catalog_url, array $hotel): string
{
  $key = bsi_hotels_api_hotel_key($hotel);

  return $key !== '' ? trailingslashit($catalog_url) . $key . '/' : '';
}

/**
 * Курорт хаба, привязанный к терму `resort`.
 *
 * @return array{slug: string, name: string, country: array}|null
 */
function bsi_hotels_api_resort_city(int $term_id): ?array
{
  if ($term_id <= 0 || !function_exists('get_field')) {
    return null;
  }

  static $cache = [];
  if (array_key_exists($term_id, $cache)) {
    return $cache[$term_id];
  }

  $slug = trim((string) get_field('hotels_api_city', 'term_' . $term_id));
  if ($slug === '') {
    return $cache[$term_id] = null;
  }

  $client = bsi_hotels_api();
  if (!$client) {
    return $cache[$term_id] = null;
  }

  try {
    foreach ($client->cities() as $city) {
      if (($city['slug'] ?? '') === $slug) {
        return $cache[$term_id] = [
          'slug' => $slug,
          'name' => (string) ($city['name'] ?? $slug),
          'country' => is_array($city['country'] ?? null) ? $city['country'] : [],
        ];
      }
    }
  } catch (HotelsApiException $e) {
    return $cache[$term_id] = null;
  }

  return $cache[$term_id] = null;
}

/**
 * Страна WordPress по слагу страны в хабе — нужна, чтобы построить адреса
 * каталога и карточек от курорта.
 */
function bsi_hotels_api_country_post(string $api_country_slug): ?WP_Post
{
  if ($api_country_slug === '') {
    return null;
  }

  static $cache = [];
  if (array_key_exists($api_country_slug, $cache)) {
    return $cache[$api_country_slug];
  }

  $countries = get_posts([
    'post_type' => 'country',
    'post_status' => 'publish',
    'post_parent' => 0,
    'posts_per_page' => -1,
    'no_found_rows' => true,
  ]);

  foreach ($countries as $country) {
    if (bsi_hotels_api_country_slug((int) $country->ID) === $api_country_slug) {
      return $cache[$api_country_slug] = $country;
    }
  }

  return $cache[$api_country_slug] = null;
}

/**
 * Отели курорта из хаба для страницы терма.
 *
 * @return array{items: array, total: int, catalog_url: string, city: array}|null
 */
function bsi_hotels_api_resort_hotels(int $term_id, int $limit = 12): ?array
{
  $city = bsi_hotels_api_resort_city($term_id);
  if (!$city) {
    return null;
  }

  $country = bsi_hotels_api_country_post((string) ($city['country']['slug'] ?? ''));
  if (!$country) {
    return null;
  }

  $client = bsi_hotels_api();
  if (!$client) {
    return null;
  }

  try {
    $list = $client->hotels([
      'city' => $city['slug'],
      'limit' => $limit,
      'sort' => 'name',
      'order' => 'asc',
    ]);
  } catch (HotelsApiException $e) {
    return null;
  }

  $catalog_url = bsi_hotels_api_catalog_url($country);

  return [
    'items' => bsi_hotels_api_filter_items($list['items']),
    'total' => (int) $list['total'],
    'catalog_url' => $catalog_url,
    'resort_url' => bsi_hotels_api_resort_url($catalog_url, $city['slug']),
    'city' => $city,
  ];
}
