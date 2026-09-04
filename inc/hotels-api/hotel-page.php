<?php

/**
 * Страница отеля из хаба BSIHOTELS: /country/{country}/hotel/{slug}/
 *
 * Тот же URL, что у отелей, заведённых в WordPress. Правило переписи адресов
 * ведёт на CPT hotel; если поста с таким слагом нет, WordPress отдаёт 404 —
 * здесь мы этот 404 перехватываем и пробуем отель из хаба.
 */

add_action('template_redirect', 'bsi_hotels_api_hotel_page', 5);
function bsi_hotels_api_hotel_page(): void
{
  if (!is_404()) {
    return;
  }

  $request = bsi_hotels_api_parse_hotel_request();
  if (!$request) {
    return;
  }

  [$country, $hotel_slug] = $request;

  $api_country = bsi_hotels_api_country_slug((int) $country->ID);
  $client = bsi_hotels_api();

  if ($api_country === '' || !$client) {
    return;
  }

  try {
    $hotel = $client->hotel($hotel_slug);
  } catch (HotelsApiException $e) {
    // 404 хаба оставляем как 404 сайта; сбой связи тоже не должен
    // превращаться в пустую страницу с кодом 200.
    return;
  }

  // Отель другой страны по этому адресу — не наша страница.
  if (($hotel['country']['slug'] ?? '') !== $api_country) {
    return;
  }

  global $wp_query, $bsi_hotels_api_hotel;

  $wp_query->is_404 = false;
  status_header(200);

  $bsi_hotels_api_hotel = [
    'hotel' => $hotel,
    'slug' => $hotel_slug,
    'country' => $country,
  ];

  $template = locate_template('country-hotel-api.php');
  if ($template) {
    include $template;
    exit;
  }
}

/**
 * Разбирает адрес вида /country/{country}/hotel/{slug}/.
 *
 * @return array{0: WP_Post, 1: string}|null
 */
function bsi_hotels_api_parse_hotel_request(): ?array
{
  $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
  $home = trim((string) parse_url(home_url(), PHP_URL_PATH), '/');

  if ($home !== '' && str_starts_with($path, $home . '/')) {
    $path = substr($path, strlen($home) + 1);
  }

  $parts = explode('/', $path);

  if (count($parts) !== 4 || $parts[0] !== 'country' || $parts[2] !== 'hotel') {
    return null;
  }

  $country = get_page_by_path($parts[1], OBJECT, 'country');
  if (!$country instanceof WP_Post) {
    return null;
  }

  $slug = sanitize_title($parts[3]);
  if ($slug === '') {
    return null;
  }

  return [$country, $slug];
}

/**
 * Минимальная ставка за ночь по всем номерам отеля.
 *
 * @return array{amount: float, currency: string}|null
 */
function bsi_hotels_api_min_rate(array $hotel): ?array
{
  $min = null;

  foreach ($hotel['room_types'] ?? [] as $room) {
    foreach ($room['rates'] ?? [] as $rate) {
      if ((int) ($rate['available'] ?? 0) <= 0) {
        continue;
      }

      $amount = (float) ($rate['price'] ?? $rate['base_price'] ?? 0);
      if ($amount <= 0) {
        continue;
      }

      $currency = (string) ($rate['base_currency'] ?? '');

      if ($min === null || $amount < $min['amount']) {
        $min = ['amount' => $amount, 'currency' => $currency];
      }
    }
  }

  return $min;
}

/**
 * Есть ли у карточки содержимое, ради которого её стоит отдавать поисковику.
 * Отель без описания, фото и номеров — пустая страница.
 */
function bsi_hotels_api_hotel_is_thin(array $hotel): bool
{
  if (trim((string) ($hotel['description'] ?? '')) !== '') {
    return false;
  }

  if (!empty($hotel['photos'])) {
    return false;
  }

  if (!empty($hotel['amenities'])) {
    return false;
  }

  // Номера — уже содержание: цены к ним подтянутся при первом обращении,
  // хаб держит их pull-through кэшем и наполняет по спросу.
  if (!empty($hotel['room_types'])) {
    return false;
  }

  return bsi_hotels_api_min_rate($hotel) === null;
}

/**
 * Курорт, которого нет в хабе, не должен отдавать пустую страницу с кодом 200 —
 * иначе в индекс попадают адреса вида /hotel/kurort/чтоугодно/.
 */
add_action('template_redirect', 'bsi_hotels_api_validate_resort', 6);
function bsi_hotels_api_validate_resort(): void
{
  $slug = sanitize_title((string) get_query_var('country_hotel_resort'));
  if ($slug === '') {
    return;
  }

  $country_slug = (string) get_query_var('country_hotels');
  $country = $country_slug !== '' ? get_page_by_path($country_slug, OBJECT, 'country') : null;

  if (!$country instanceof WP_Post) {
    return;
  }

  if (bsi_hotels_api_current_resort((int) $country->ID) !== null) {
    return;
  }

  global $wp_query;
  $wp_query->set_404();
  status_header(404);
  nocache_headers();

  include get_404_template();
  exit;
}
