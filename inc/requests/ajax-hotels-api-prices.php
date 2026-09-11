<?php

/**
 * Догрузка цен каталога отелей.
 *
 * Хаб отдаёт в списке `prices_status`: `updating` значит «цены этого отеля
 * обновляются прямо сейчас». Замеры 2026-09-11 — такой отель становится `fresh`
 * с ценой примерно за двадцать секунд, но страница к тому моменту уже
 * отрисована, и карточка навсегда остаётся с «Считаем».
 *
 * Эндпоинт повторяет тот же запрос каталога и отдаёт только цены — фронт
 * (js/modules/ajax/hotels-prices.js) опрашивает его, пока есть что ждать.
 *
 * Фильтра по списку id у хаба нет, поэтому переспрашиваем страницу целиком:
 * один запрос вместо одного на карточку.
 */

add_action('wp_ajax_bsi_hotels_api_prices', 'bsi_hotels_api_prices_ajax');
add_action('wp_ajax_nopriv_bsi_hotels_api_prices', 'bsi_hotels_api_prices_ajax');

function bsi_hotels_api_prices_ajax(): void
{
  $country_id = isset($_POST['country']) ? absint($_POST['country']) : 0;
  $paged = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;
  $resort = sanitize_title((string) ($_POST['resort'] ?? ''));
  $query = (string) ($_POST['filters'] ?? '');

  $country = $country_id ? get_post($country_id) : null;
  if (!$country instanceof WP_Post || $country->post_type !== 'country') {
    wp_send_json_error(['message' => 'Страна не найдена'], 400);
  }

  /* Фильтры приходят query-строкой каталога: отбор влияет на состав страницы,
     а значит и на то, у каких отелей мы ждём цену. */
  $source = [];
  if ($query !== '') {
    parse_str($query, $source);
  }

  $catalog = bsi_hotels_api_catalog_query(
    $country,
    $paged,
    $resort,
    bsi_hotels_api_catalog_filters(is_array($source) ? $source : [])
  );

  if ($catalog['error'] !== '') {
    wp_send_json_error(['message' => 'Не удалось обновить цены'], 502);
  }

  $prices = [];
  $pending = 0;

  foreach ($catalog['list']['items'] as $hotel) {
    $id = (string) ($hotel['id'] ?? '');
    if ($id === '') {
      continue;
    }

    $price = bsi_hotels_api_format_price($hotel['price_from'] ?? null);
    $updating = ($hotel['prices_status'] ?? '') === 'updating';

    if ($price === '' && $updating) {
      $pending++;
    }

    $prices[$id] = [
      'price' => $price,
      'updating' => $updating,
    ];
  }

  wp_send_json_success([
    'prices' => $prices,
    /* Сколько карточек всё ещё без цены: ноль — фронту больше не о чем спрашивать. */
    'pending' => $pending,
  ]);
}
