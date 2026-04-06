<?php

add_action('wp_ajax_bsi_cbr_rates_history', 'handle_cbr_rates_history');
add_action('wp_ajax_nopriv_bsi_cbr_rates_history', 'handle_cbr_rates_history');

function handle_cbr_rates_history()
{
	$date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		wp_send_json_error([
			'message' => 'Некорректная дата. Используйте формат YYYY-MM-DD.',
		]);
		return;
	}

	$snapshot = bsi_currency_history_get_snapshot_by_date($date);
	if (!$snapshot) {
		wp_send_json_success([
			'snapshot' => null,
			'message' => 'Нет данных за выбранную дату.',
		]);
		return;
	}

	wp_send_json_success([
		'snapshot' => $snapshot,
	]);
}
