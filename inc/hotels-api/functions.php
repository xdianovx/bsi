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
 * @return array{list: array, resorts: array, error: string, per_page: int, resort: string}
 */
function bsi_hotels_api_catalog_query(WP_Post $country): array
{
  static $cache = [];

  $paged = max(1, (int) get_query_var('paged'));
  $resort = sanitize_title((string) get_query_var('country_hotel_resort'));
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

    $result['resorts'] = $client->cities($api_country);
  } catch (HotelsApiException $e) {
    $result['error'] = $e->getMessage();
  }

  return $cache[$key] = $result;
}
