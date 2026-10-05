<?php
/**
 * Трансферы страны — режим пункта меню и ссылка на Само.
 * Поля: custom-fields/country-transfers.php.
 */

/** Пары «дата начала / дата конца» в ссылках Само (формат Ymd). */
const BSI_SAMO_DATE_PAIRS = [
  ['DATEBEG', 'DATEEND'],         // /services, /transfers
  ['CHECKIN_BEG', 'CHECKIN_END'], // /search_tour, /search_hotel
];

/** Через сколько дней от сегодня ставить дату, если в ссылке прошедшая. */
const BSI_SAMO_DATE_SHIFT_DAYS = 7;

/**
 * Ссылка менеджера с актуальными датами: прошедшие даты сдвигаются вперёд
 * с сохранением длины периода. Будущие даты не трогаем — их выбрал менеджер.
 */
function bsi_samo_refresh_url_dates(string $url): string
{
  $parts = wp_parse_url($url);
  if (empty($parts['query'])) {
    return $url;
  }

  // Само иногда отдаёт «services??…» — лишний «?» попадает в начало query.
  parse_str(ltrim($parts['query'], '?'), $query);

  $tz = wp_timezone();
  $today = new DateTimeImmutable('today', $tz);
  $changed = false;

  foreach (BSI_SAMO_DATE_PAIRS as [$beg_key, $end_key]) {
    $beg = isset($query[$beg_key]) ? DateTimeImmutable::createFromFormat('!Ymd', (string) $query[$beg_key], $tz) : false;
    if (!$beg || $beg >= $today) {
      continue;
    }

    $end = isset($query[$end_key]) ? DateTimeImmutable::createFromFormat('!Ymd', (string) $query[$end_key], $tz) : false;
    $span = $end && $end > $beg ? $beg->diff($end)->days : 0;

    $new_beg = $today->modify('+' . BSI_SAMO_DATE_SHIFT_DAYS . ' days');
    $query[$beg_key] = $new_beg->format('Ymd');
    if ($end) {
      $query[$end_key] = $new_beg->modify('+' . $span . ' days')->format('Ymd');
    }
    $changed = true;
  }

  if (!$changed) {
    return $url;
  }

  $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . ($parts['path'] ?? '');

  return $base . '?' . http_build_query($query);
}

function bsi_country_transfers_samo_url(int $country_id): string
{
  $url = function_exists('get_field') ? trim((string) get_field('transfers_samo_url', $country_id)) : '';

  return $url !== '' ? bsi_samo_refresh_url_dates($url) : '';
}

/**
 * Режим пункта «Трансферы»: '' (не показывать), 'link' или 'page'.
 * Режим без нужного контента считается выключенным, чтобы в меню
 * не появилась пустая ссылка.
 */
function bsi_country_transfers_mode(int $country_id): string
{
  if (!$country_id || !function_exists('get_field')) {
    return '';
  }

  $mode = (string) get_field('transfers_mode', $country_id);
  $has_url = trim((string) get_field('transfers_samo_url', $country_id)) !== '';

  if ($mode === 'link') {
    return $has_url ? 'link' : '';
  }

  if ($mode === 'page') {
    $has_content = trim(wp_strip_all_tags((string) get_field('transfers_content', $country_id))) !== '';
    return ($has_content || $has_url) ? 'page' : '';
  }

  return '';
}
