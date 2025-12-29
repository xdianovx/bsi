<?php
add_action('wp_ajax_bsi_cbr_rates', 'handle_cbr_rates');
add_action('wp_ajax_nopriv_bsi_cbr_rates', 'handle_cbr_rates');

function handle_cbr_rates()
{
  $cacheKey = 'bsi_cbr_rates';
  $cacheExpiration = HOUR_IN_SECONDS;

  $markup = floatval(get_field('currency_markup', 'option') ?: 0);

  $cached = get_transient($cacheKey);
  if ($cached !== false && is_array($cached) && isset($cached['rates'])) {
    $result = $cached;
    if ($markup > 0) {
      foreach ($result['rates'] as $code => &$rate) {
        if (!isset($rate['value']) || !isset($rate['nominal'])) {
          continue;
        }
        $rate['value'] = $rate['value'] * (1 + $markup / 100);
      }
      unset($rate);
    }
    wp_send_json_success($result);
    return;
  }

  $oldCached = $cached;
  $url = 'https://www.cbr-xml-daily.ru/daily_json.js';

  $response = wp_remote_get($url, [
    'timeout' => 10,
    'headers' => [
      'Accept' => 'application/json',
    ],
  ]);

  if (is_wp_error($response)) {
    if ($oldCached !== false && is_array($oldCached) && isset($oldCached['rates'])) {
      $result = $oldCached;
      if ($markup > 0) {
        foreach ($result['rates'] as $code => &$rate) {
          if (!isset($rate['value']) || !isset($rate['nominal'])) {
            continue;
          }
          $rate['value'] = $rate['value'] * (1 + $markup / 100);
        }
        unset($rate);
      }
      wp_send_json_success($result);
      return;
    }
    wp_send_json_error([
      'message' => 'Ошибка получения курсов валют',
      'error' => $response->get_error_message(),
    ]);
    return;
  }

  $code = wp_remote_retrieve_response_code($response);
  if ($code >= 400) {
    if ($oldCached !== false && is_array($oldCached) && isset($oldCached['rates'])) {
      $result = $oldCached;
      if ($markup > 0) {
        foreach ($result['rates'] as $code => &$rate) {
          if (!isset($rate['value']) || !isset($rate['nominal'])) {
            continue;
          }
          $rate['value'] = $rate['value'] * (1 + $markup / 100);
        }
        unset($rate);
      }
      wp_send_json_success($result);
      return;
    }
    wp_send_json_error([
      'message' => 'Ошибка получения курсов валют',
      'code' => $code,
    ]);
    return;
  }

  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);

  if (!is_array($data) || !isset($data['Valute'])) {
    if ($oldCached !== false && is_array($oldCached) && isset($oldCached['rates'])) {
      $result = $oldCached;
      if ($markup > 0) {
        foreach ($result['rates'] as $code => &$rate) {
          if (!isset($rate['value']) || !isset($rate['nominal'])) {
            continue;
          }
          $rate['value'] = $rate['value'] * (1 + $markup / 100);
        }
        unset($rate);
      }
      wp_send_json_success($result);
      return;
    }
    wp_send_json_error([
      'message' => 'Неверный формат данных',
    ]);
    return;
  }

  $result = [
    'date' => $data['Date'] ?? date('Y-m-d'),
    'rates' => [],
  ];

  foreach ($data['Valute'] as $currency) {
    if (!isset($currency['CharCode']) || !isset($currency['Value']) || !isset($currency['Nominal'])) {
      continue;
    }

    $code = $currency['CharCode'];
    $value = floatval($currency['Value']);
    $nominal = intval($currency['Nominal']);

    $result['rates'][$code] = [
      'value' => $value,
      'nominal' => $nominal,
    ];
  }

  set_transient($cacheKey, $result, $cacheExpiration);

  if ($markup > 0) {
    foreach ($result['rates'] as $code => &$rate) {
      $rate['value'] = $rate['value'] * (1 + $markup / 100);
    }
    unset($rate);
  }

  wp_send_json_success($result);
}

