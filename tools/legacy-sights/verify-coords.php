<?php

/**
 * Проверка машинных координат: точка должна лежать рядом со своим городом.
 *
 * Геокодер ищет по латинскому названию, и Nominatim охотно отдаёт однофамильца
 * в другом конце страны («Центр детского досуга W5» из Белфаста → деревня W5
 * в Восточном Суссексе). Здесь координаты города берутся один раз на город,
 * а дальше считается расстояние до каждой точки.
 *
 *   php verify-coords.php --country=velikobritaniya --cc=gb [--max-km=60] [--fix]
 *
 * Без --fix только показывает; с --fix снимает координаты, которые дальше лимита
 * (только машинные — с метой bsi_sight_coords_source, ручные не трогает).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

const BSI_VERIFY_ENDPOINT = 'https://nominatim.openstreetmap.org/search';
const BSI_VERIFY_UA = 'BSI-sights-geocoder/1.0 (+https://bsigroup.ru)';
const BSI_VERIFY_DELAY_US = 1_100_000;

$options = getopt('', ['country:', 'cc:', 'wp:', 'max-km:', 'fix']);

$country_slug = (string) ($options['country'] ?? 'velikobritaniya');
$cc = strtolower((string) ($options['cc'] ?? 'gb'));
$max_km = (float) ($options['max-km'] ?? 60);
$fix = isset($options['fix']);

$wp_load = (string) ($options['wp'] ?? '');
if ($wp_load === '') {
  $wp_load = dirname(__DIR__, 5) . '/wp-load.php';
}
if (!is_readable($wp_load)) {
  exit("Не найден wp-load.php: $wp_load\n");
}

/**
 * Расстояние между точками по гаверсинусу, км.
 */
function bsi_verify_distance(float $lat1, float $lng1, float $lat2, float $lng2): float
{
  $r = 6371.0;
  $dLat = deg2rad($lat2 - $lat1);
  $dLng = deg2rad($lng2 - $lng1);

  $a = sin($dLat / 2) ** 2
    + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

  return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/**
 * Координаты города (кешируются на запуск: городов десятки, записей сотни).
 *
 * @return array{lat:float, lng:float}|null
 */
function bsi_verify_city(string $city, string $cc, array &$cache): ?array
{
  if (array_key_exists($city, $cache)) {
    return $cache[$city];
  }

  $url = BSI_VERIFY_ENDPOINT . '?' . http_build_query([
    'format' => 'json',
    'limit' => '1',
    'accept-language' => 'ru',
    'countrycodes' => $cc,
    'q' => $city,
  ]);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => BSI_VERIFY_UA,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
  ]);
  $body = curl_exec($ch);
  $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  usleep(BSI_VERIFY_DELAY_US);

  $hit = null;
  if ($body !== false && $status === 200) {
    $data = json_decode((string) $body, true);
    if (is_array($data) && !empty($data[0]['lat'])) {
      $hit = ['lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon']];
    }
  }

  $cache[$city] = $hit;

  return $hit;
}

define('WP_USE_THEMES', false);
require $wp_load;

if (!function_exists('get_field')) {
  exit("ACF не активен\n");
}

$country = get_page_by_path($country_slug, OBJECT, 'country');
if (!$country) {
  exit("Не найдена страна CPT country со слагом «$country_slug»\n");
}

$ids = get_posts([
  'post_type' => 'sight',
  'post_status' => 'any',
  'posts_per_page' => -1,
  'fields' => 'ids',
  'no_found_rows' => true,
  'meta_query' => [
    ['key' => 'sight_country', 'value' => (int) $country->ID, 'compare' => '='],
  ],
]);

$cache = [];
$ok = 0;
$far = 0;
$nocity = 0;
$manual = 0;

printf("Проверка %d записей, лимит %.0f км%s\n\n", count($ids), $max_km, $fix ? ' [--fix]' : '');

foreach ($ids as $post_id) {
  $post_id = (int) $post_id;

  $coords = trim((string) get_field('sight_map_coordinates', $post_id));
  if ($coords === '') {
    continue;
  }

  $source = (string) get_post_meta($post_id, 'bsi_sight_coords_source', true);
  if ($source === '') {
    $manual++;
    continue;
  }

  $parts = array_map('trim', explode(',', $coords));
  if (count($parts) !== 2) {
    continue;
  }

  $lat = (float) $parts[0];
  $lng = (float) $parts[1];

  $resorts = wp_get_post_terms($post_id, 'resort', ['fields' => 'names']);
  $city = (!is_wp_error($resorts) && !empty($resorts)) ? (string) $resorts[0] : '';

  if ($city === '') {
    $nocity++;
    continue;
  }

  $city_point = bsi_verify_city($city . ', ' . $country->post_title, $cc, $cache);
  if ($city_point === null) {
    $nocity++;
    continue;
  }

  $distance = bsi_verify_distance($lat, $lng, $city_point['lat'], $city_point['lng']);

  if ($distance <= $max_km) {
    $ok++;
    continue;
  }

  $far++;
  printf("  далеко (%.0f км): %s — %s\n", $distance, get_the_title($post_id), $city);

  if ($fix) {
    update_field('sight_map_coordinates', '', $post_id);
    delete_post_meta($post_id, 'bsi_sight_coords_source');
  }
}

printf(
  "\nВ пределах лимита: %d, далеко: %d, без города или города не нашлось: %d, ручные (не проверялись): %d\n",
  $ok,
  $far,
  $nocity,
  $manual
);
