<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/HotelsApiClient.php';

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
