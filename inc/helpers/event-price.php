<?php

/**
 * Цена событийного тура (CPT event) для карточки каталога и сортировки.
 *
 * Минимальная цена считается по строкам репитера `event_dates` (поле `date_row_price`
 * в валюте `date_row_price_currency`), конвертируется в рубли через
 * bsi_education_convert_price_to_rub(). Если строк с ценой нет — fallback на текстовое
 * поле `price_from` (bsi_extract_price_number, всегда трактуется как рубли).
 *
 * @return array{rub:?int,original:?float,currency:?string}
 */
function bsi_event_card_price(int $post_id): array
{
  $result = ['rub' => null, 'original' => null, 'currency' => null];
  if ($post_id <= 0 || !function_exists('get_field')) {
    return $result;
  }

  // Приоритет: живая crosstour-цена из ручной ссылки search_crosstour (как single-event.php /
  // single-tour.php). Серверно + кеш 3ч; на проде даёт цену даже когда JS-батч пуст.
  $booking_url = trim((string) get_field('tour_booking_url', $post_id));
  if ($booking_url !== ''
    && stripos($booking_url, 'search_crosstour') !== false
    && function_exists('bsi_crosstour_ref_from_url')
    && function_exists('bsi_crosstour_quick_price')
  ) {
    $ct_ref = bsi_crosstour_ref_from_url($booking_url);
    if ($ct_ref) {
      $ct_rub = bsi_crosstour_quick_price($ct_ref);
      if ($ct_rub !== null && (int) $ct_rub > 0) {
        $result['rub'] = (int) $ct_rub;
        return $result;
      }
    }
  }

  $rows = get_field('event_dates', $post_id);
  $candidates = [];

  if (!empty($rows) && is_array($rows)) {
    foreach ($rows as $row) {
      $amount_raw = $row['date_row_price'] ?? null;
      if ($amount_raw === null || $amount_raw === '') {
        continue;
      }
      $amount = (float) $amount_raw;
      if ($amount <= 0) {
        continue;
      }
      $currency = isset($row['date_row_price_currency'])
        ? strtoupper(trim((string) $row['date_row_price_currency']))
        : 'RUB';
      if ($currency === '') {
        $currency = 'RUB';
      }

      $rub = function_exists('bsi_education_convert_price_to_rub')
        ? bsi_education_convert_price_to_rub($amount, $currency)
        : null;
      if ($rub === null || $rub <= 0) {
        continue;
      }

      $candidates[] = [
        'rub' => (int) $rub,
        'original' => $currency !== 'RUB' ? $amount : null,
        'currency' => $currency !== 'RUB' ? $currency : null,
      ];
    }
  }

  if (!empty($candidates)) {
    usort($candidates, static function ($a, $b) {
      return $a['rub'] <=> $b['rub'];
    });
    return $candidates[0];
  }

  // Fallback: текстовое поле price_from (рубли).
  $price_from = trim((string) get_field('price_from', $post_id));
  if ($price_from !== '' && function_exists('bsi_extract_price_number')) {
    $num = bsi_extract_price_number($price_from);
    if ($num !== null && $num > 0) {
      $result['rub'] = (int) $num;
    }
  }

  return $result;
}
