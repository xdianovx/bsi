<?php
add_action('wp_ajax_bsi_cbr_rates', 'handle_cbr_rates');
add_action('wp_ajax_nopriv_bsi_cbr_rates', 'handle_cbr_rates');

function bsi_send_cbr_rates_success(array $result, float $markup)
{
	if (empty($result['rates']) || !is_array($result['rates'])) {
		wp_send_json_success($result);
		return;
	}

	$result['rates'] = bsi_currency_apply_markup_to_rates($result['rates'], $markup);
	$rate_date = current_time('Y-m-d');

	bsi_currency_history_upsert_snapshot([
		'rate_date' => $rate_date,
		'source_date' => !empty($result['date']) ? $result['date'] : null,
		'markup_percent' => $markup,
		'rates' => $result['rates'],
	]);

	wp_send_json_success($result);
}

function handle_cbr_rates()
{
  $cacheKey = 'bsi_cbr_rates';
  $cacheExpiration = HOUR_IN_SECONDS;

  $markup = floatval(get_field('currency_markup', 'option') ?: 0);

  $cached = get_transient($cacheKey);
  if ($cached !== false && is_array($cached) && isset($cached['rates'])) {
    bsi_send_cbr_rates_success($cached, $markup);
    return;
  }

  $url = 'https://www.cbr-xml-daily.ru/daily_json.js';

  $response = wp_remote_get($url, [
    'timeout' => 10,
    'headers' => [
      'Accept' => 'application/json',
    ],
  ]);

  if (is_wp_error($response)) {
    wp_send_json_error([
      'message' => 'Ошибка получения курсов валют',
      'error' => $response->get_error_message(),
    ]);
    return;
  }

  $code = wp_remote_retrieve_response_code($response);
  if ($code >= 400) {
    wp_send_json_error([
      'message' => 'Ошибка получения курсов валют',
      'code' => $code,
    ]);
    return;
  }

  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);

  if (!is_array($data) || !isset($data['Valute'])) {
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
  bsi_send_cbr_rates_success($result, $markup);
}

