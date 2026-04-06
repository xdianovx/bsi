<?php

function bsi_currency_history_table_name()
{
	global $wpdb;

	return $wpdb->prefix . 'bsi_currency_history';
}

function bsi_currency_history_schema_version()
{
	return '1.0.0';
}

function bsi_currency_history_install_table()
{
	global $wpdb;

	$table_name = bsi_currency_history_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		rate_date DATE NOT NULL,
		source_date DATETIME NULL,
		markup_percent DECIMAL(8,3) NOT NULL DEFAULT 0.000,
		rates_json LONGTEXT NOT NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY rate_date (rate_date)
	) {$charset_collate};";

	dbDelta($sql);
	update_option('bsi_currency_history_schema_version', bsi_currency_history_schema_version());
}

function bsi_currency_history_maybe_install_table()
{
	$installed_version = get_option('bsi_currency_history_schema_version');
	if ($installed_version === bsi_currency_history_schema_version()) {
		return;
	}

	bsi_currency_history_install_table();
}

add_action('after_switch_theme', 'bsi_currency_history_install_table');
add_action('init', 'bsi_currency_history_maybe_install_table');

function bsi_currency_get_markup_percent()
{
	return floatval(get_field('currency_markup', 'option') ?: 0);
}

function bsi_currency_apply_markup_to_rates(array $rates, float $markup_percent)
{
	if ($markup_percent <= 0) {
		return $rates;
	}

	$multiplier = 1 + ($markup_percent / 100);
	foreach ($rates as $code => $rate) {
		if (!is_array($rate) || !isset($rate['value']) || !isset($rate['nominal'])) {
			continue;
		}

		$rates[$code]['value'] = floatval($rate['value']) * $multiplier;
	}

	return $rates;
}

function bsi_currency_extract_rate_date($source_date)
{
	if (!is_string($source_date) || $source_date === '') {
		return current_time('Y-m-d');
	}

	$timestamp = strtotime($source_date);
	if (!$timestamp) {
		return current_time('Y-m-d');
	}

	return wp_date('Y-m-d', $timestamp, wp_timezone());
}

function bsi_currency_history_upsert_snapshot(array $payload)
{
	global $wpdb;

	if (empty($payload['rate_date']) || empty($payload['rates']) || !is_array($payload['rates'])) {
		return false;
	}

	$rate_date = sanitize_text_field($payload['rate_date']);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rate_date)) {
		return false;
	}

	$source_date = !empty($payload['source_date']) ? sanitize_text_field($payload['source_date']) : null;
	$markup_percent = isset($payload['markup_percent']) ? floatval($payload['markup_percent']) : 0.0;
	$rates_json = wp_json_encode($payload['rates'], JSON_UNESCAPED_UNICODE);

	if (!is_string($rates_json) || $rates_json === '') {
		return false;
	}

	$now = current_time('mysql');
	$table_name = bsi_currency_history_table_name();

	$result = $wpdb->replace(
		$table_name,
		[
			'rate_date' => $rate_date,
			'source_date' => $source_date,
			'markup_percent' => $markup_percent,
			'rates_json' => $rates_json,
			'created_at' => $now,
			'updated_at' => $now,
		],
		[
			'%s',
			'%s',
			'%f',
			'%s',
			'%s',
			'%s',
		]
	);

	return $result !== false;
}

function bsi_currency_history_get_snapshot_by_date($date)
{
	global $wpdb;

	$date = sanitize_text_field((string) $date);
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return null;
	}

	$table_name = bsi_currency_history_table_name();
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT rate_date, source_date, markup_percent, rates_json FROM {$table_name} WHERE rate_date = %s LIMIT 1",
			$date
		),
		ARRAY_A
	);

	if (!$row || empty($row['rates_json'])) {
		return null;
	}

	$rates = json_decode($row['rates_json'], true);
	if (!is_array($rates)) {
		return null;
	}

	return [
		'rate_date' => $row['rate_date'],
		'source_date' => $row['source_date'],
		'markup_percent' => floatval($row['markup_percent']),
		'rates' => $rates,
	];
}

function bsi_currency_history_get_latest_snapshot()
{
	global $wpdb;

	$table_name = bsi_currency_history_table_name();
	$row = $wpdb->get_row(
		"SELECT rate_date, source_date, markup_percent, rates_json FROM {$table_name} ORDER BY rate_date DESC LIMIT 1",
		ARRAY_A
	);

	if (!$row || empty($row['rates_json'])) {
		return null;
	}

	$rates = json_decode($row['rates_json'], true);
	if (!is_array($rates)) {
		return null;
	}

	return [
		'rate_date' => $row['rate_date'],
		'source_date' => $row['source_date'],
		'markup_percent' => floatval($row['markup_percent']),
		'rates' => $rates,
	];
}

function bsi_currency_fetch_cbr_rates_remote()
{
	$response = wp_remote_get('https://www.cbr-xml-daily.ru/daily_json.js', [
		'timeout' => 10,
		'headers' => [
			'Accept' => 'application/json',
		],
	]);

	if (is_wp_error($response)) {
		return null;
	}

	$code = wp_remote_retrieve_response_code($response);
	if ($code >= 400) {
		return null;
	}

	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);
	if (!is_array($data) || empty($data['Valute']) || !is_array($data['Valute'])) {
		return null;
	}

	$result = [
		'date' => $data['Date'] ?? current_time('mysql', true),
		'rates' => [],
	];

	foreach ($data['Valute'] as $currency) {
		if (!isset($currency['CharCode']) || !isset($currency['Value']) || !isset($currency['Nominal'])) {
			continue;
		}

		$code = $currency['CharCode'];
		$result['rates'][$code] = [
			'value' => floatval($currency['Value']),
			'nominal' => intval($currency['Nominal']),
		];
	}

	if (empty($result['rates'])) {
		return null;
	}

	return $result;
}

function bsi_currency_refresh_daily_snapshot($force_remote = false)
{
	$cache_key = 'bsi_cbr_rates';
	$cache_expiration = HOUR_IN_SECONDS;
	$today = current_time('Y-m-d');

	$raw_rates = null;
	if (!$force_remote) {
		$cached = get_transient($cache_key);
		if (is_array($cached) && !empty($cached['rates'])) {
			$raw_rates = $cached;
		}
	}

	if (!$raw_rates) {
		$raw_rates = bsi_currency_fetch_cbr_rates_remote();
		if ($raw_rates) {
			set_transient($cache_key, $raw_rates, $cache_expiration);
		}
	}

	$source_date = null;
	if (!$raw_rates || empty($raw_rates['rates']) || !is_array($raw_rates['rates'])) {
		$latest_snapshot = bsi_currency_history_get_latest_snapshot();
		if (!$latest_snapshot || empty($latest_snapshot['rates']) || !is_array($latest_snapshot['rates'])) {
			return false;
		}

		$raw_rates = [
			'date' => $latest_snapshot['source_date'] ?: current_time('mysql'),
			'rates' => $latest_snapshot['rates'],
		];

		$previous_markup = floatval($latest_snapshot['markup_percent']);
		if ($previous_markup > 0) {
			$divider = 1 + ($previous_markup / 100);
			foreach ($raw_rates['rates'] as $code => $rate) {
				if (!is_array($rate) || !isset($rate['value'])) {
					continue;
				}
				$raw_rates['rates'][$code]['value'] = floatval($rate['value']) / $divider;
			}
		}
	} else {
		$source_date = $raw_rates['date'] ?? null;
	}

	$markup = bsi_currency_get_markup_percent();
	$prepared_rates = bsi_currency_apply_markup_to_rates($raw_rates['rates'], $markup);

	return bsi_currency_history_upsert_snapshot([
		'rate_date' => $today,
		'source_date' => $source_date,
		'markup_percent' => $markup,
		'rates' => $prepared_rates,
	]);
}

function bsi_currency_ensure_today_snapshot_exists()
{
	$today = current_time('Y-m-d');
	if (bsi_currency_history_get_snapshot_by_date($today)) {
		return;
	}

	bsi_currency_refresh_daily_snapshot(false);
}

function bsi_currency_maybe_run_daily_snapshot_fallback()
{
	$lock_key = 'bsi_currency_snapshot_fallback_lock';
	if (get_transient($lock_key)) {
		return;
	}

	set_transient($lock_key, 1, 15 * MINUTE_IN_SECONDS);
	bsi_currency_ensure_today_snapshot_exists();
}

function bsi_currency_next_midnight_timestamp()
{
	$timezone = wp_timezone();
	$now = new DateTimeImmutable('now', $timezone);
	$next_midnight = $now->setTime(0, 0)->modify('+1 day');

	return $next_midnight->getTimestamp();
}

function bsi_currency_schedule_daily_snapshot_event()
{
	if (wp_next_scheduled('bsi_currency_daily_snapshot_event')) {
		return;
	}

	wp_schedule_event(bsi_currency_next_midnight_timestamp(), 'daily', 'bsi_currency_daily_snapshot_event');
}

add_action('init', 'bsi_currency_schedule_daily_snapshot_event');
add_action('bsi_currency_daily_snapshot_event', function () {
	bsi_currency_refresh_daily_snapshot(true);
});
add_action('init', 'bsi_currency_maybe_run_daily_snapshot_fallback', 20);
