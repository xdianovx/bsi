<?php

/**
 * Парсит строку дат вида "21.07.2026, 30.08.2026" и возвращает массив в формате Y-m-d.
 * Поддерживает разделители: запятая, точка с запятой, перенос строки.
 * Поддерживает форматы: d.m.Y, d/m/Y, d-m-Y, Y-m-d.
 */
function parse_program_dates_string(string $dates_str): array
{
  if (!$dates_str) return [];
  $result = [];
  // Нормализуем разделители: ; и переносы строк заменяем на запятую
  $normalized = preg_replace('/[;\r\n]+/', ',', $dates_str);
  foreach (array_map('trim', explode(',', $normalized)) as $raw) {
    if (!$raw) continue;
    $obj = DateTime::createFromFormat('d.m.Y', $raw)
      ?: DateTime::createFromFormat('d/m/Y', $raw)
      ?: DateTime::createFromFormat('d-m-Y', $raw)
      ?: DateTime::createFromFormat('Y-m-d', $raw);
    if ($obj) {
      $result[] = $obj->format('Y-m-d');
    }
  }
  return $result;
}

function format_number($number, $decimals = 0)
{
  if (!is_numeric($number)) {
    return $number;
  }

  return number_format(
    (float) $number,
    $decimals,
    ',',
    ' '
  );
}

function format_date_russian($value)
{
  if (!$value) {
    return '';
  }

  $date_obj = null;
  $formats = ['Ymd', 'Y-m-d', 'd.m.Y', 'd/m/Y'];

  foreach ($formats as $format) {
    $date_obj = DateTime::createFromFormat($format, trim($value));
    if ($date_obj instanceof DateTime) {
      break;
    }
  }

  if (!$date_obj) {
    $timestamp = strtotime($value);
    if ($timestamp) {
      $date_obj = DateTime::createFromFormat('U', (string) $timestamp);
    }
  }

  if ($date_obj instanceof DateTime) {
    $day = $date_obj->format('j');
    $months = [
      1 => 'января',
      2 => 'февраля',
      3 => 'марта',
      4 => 'апреля',
      5 => 'мая',
      6 => 'июня',
      7 => 'июля',
      8 => 'августа',
      9 => 'сентября',
      10 => 'октября',
      11 => 'ноября',
      12 => 'декабря'
    ];
    $month_num = (int) $date_obj->format('n');
    $month_str = isset($months[$month_num]) ? $months[$month_num] : $date_obj->format('F');
    return $day . ' ' . $month_str;
  }

  return $value;
}

/**
 * Дата события без числа: «февраль 2025», «март 2012» (месяц в именительном падеже).
 */
function format_month_year_russian($value)
{
  if (!$value) {
    return '';
  }

  $date_obj = null;
  $formats = ['Ymd', 'Y-m-d', 'd.m.Y', 'd/m/Y'];

  foreach ($formats as $format) {
    $date_obj = DateTime::createFromFormat($format, trim((string) $value));
    if ($date_obj instanceof DateTime) {
      break;
    }
  }

  if (!$date_obj) {
    $timestamp = strtotime((string) $value);
    if ($timestamp) {
      $date_obj = DateTime::createFromFormat('U', (string) $timestamp);
    }
  }

  if (!$date_obj instanceof DateTime) {
    return '';
  }

  $months = [
    1 => 'январь',
    2 => 'февраль',
    3 => 'март',
    4 => 'апрель',
    5 => 'май',
    6 => 'июнь',
    7 => 'июль',
    8 => 'август',
    9 => 'сентябрь',
    10 => 'октябрь',
    11 => 'ноябрь',
    12 => 'декабрь',
  ];
  $month_num = (int) $date_obj->format('n');
  $month_str = $months[$month_num] ?? $date_obj->format('F');

  return $month_str . ' ' . $date_obj->format('Y');
}

function format_dates_string_russian($dates_string)
{
  if (!$dates_string || !is_string($dates_string)) {
    return $dates_string;
  }

  $dates = array_map('trim', explode(',', $dates_string));
  $formatted_dates = [];

  foreach ($dates as $date) {
    $formatted = format_date_russian($date);
    if ($formatted !== $date || preg_match('/\d{1,2}\.\d{1,2}\.\d{4}/', $date)) {
      $formatted_dates[] = $formatted;
    } else {
      $formatted_dates[] = $date;
    }
  }

  return implode(', ', $formatted_dates);
}

function format_date_value($value)
{
  return format_date_russian($value);
}

function format_date_short($date_string, $date_to_string = '')
{
  if (!$date_string) {
    return '';
  }

  $date_obj = null;
  $formats = ['Ymd', 'Y-m-d', 'd.m.Y', 'd/m/Y'];

  foreach ($formats as $format) {
    $date_obj = DateTime::createFromFormat($format, trim($date_string));
    if ($date_obj instanceof DateTime) {
      break;
    }
  }

  if (!$date_obj) {
    $timestamp = strtotime($date_string);
    if ($timestamp) {
      $date_obj = DateTime::createFromFormat('U', (string) $timestamp);
    }
  }

  if (!$date_obj instanceof DateTime) {
    return $date_string;
  }

  $day = (int) $date_obj->format('j');
  $month = (int) $date_obj->format('n');
  $formatted = $day . '.' . str_pad($month, 2, '0', STR_PAD_LEFT);

  // Если есть вторая дата, форматируем её тоже
  if ($date_to_string) {
    $date_to_obj = null;
    foreach ($formats as $format) {
      $date_to_obj = DateTime::createFromFormat($format, trim($date_to_string));
      if ($date_to_obj instanceof DateTime) {
        break;
      }
    }

    if (!$date_to_obj) {
      $timestamp_to = strtotime($date_to_string);
      if ($timestamp_to) {
        $date_to_obj = DateTime::createFromFormat('U', (string) $timestamp_to);
      }
    }

    if ($date_to_obj instanceof DateTime) {
      $day_to = (int) $date_to_obj->format('j');
      $month_to = (int) $date_to_obj->format('n');
      $formatted_to = $day_to . '.' . str_pad($month_to, 2, '0', STR_PAD_LEFT);
      return $formatted . ' – ' . $formatted_to;
    }
  }

  return $formatted;
}

function format_price_text(?string $text): string
{
  $text = trim((string) $text);
  if ($text === '')
    return '';

  return preg_replace_callback('~\d{4,}~u', function ($m) {
    return format_number($m[0]);
  }, $text);
}

function format_price_with_from(?string $price, bool $show_from = true): string
{
  $price = trim((string) $price);
  if ($price === '')
    return '';

  $price_lower_original = mb_strtolower($price, 'UTF-8');
  $has_rub = mb_strpos($price_lower_original, 'руб') !== false || mb_strpos($price_lower_original, '₽') !== false;
  
  $price = str_replace('руб', '₽', $price);
  $price = str_replace('₽₽', '₽', $price);
  
  if (!$has_rub) {
    $price = $price . ' ₽';
  }

  $price_lower = mb_strtolower($price, 'UTF-8');
  
  if (!$show_from) {
    return $price;
  }

  if (mb_strpos($price_lower, 'от') !== false) {
    return $price;
  }

  return 'от ' . $price;
}

/**
 * Извлекает числовое значение цены из строки.
 * Возвращает null, если цена не распознана.
 */
function bsi_extract_price_number(?string $raw_price): ?int
{
  $raw_price = trim((string) $raw_price);
  if ($raw_price === '') {
    return null;
  }

  if (preg_match('/([\d\s.,]+)/u', $raw_price, $matches) !== 1) {
    return null;
  }

  $value = preg_replace('/[^\d.,]/u', '', (string) $matches[1]);
  if ($value === '') {
    return null;
  }

  $last_comma_pos = strrpos($value, ',');
  $last_dot_pos = strrpos($value, '.');
  $decimal_pos = false;

  if ($last_comma_pos !== false || $last_dot_pos !== false) {
    if ($last_comma_pos === false) {
      $decimal_pos = $last_dot_pos;
    } elseif ($last_dot_pos === false) {
      $decimal_pos = $last_comma_pos;
    } else {
      $decimal_pos = max($last_comma_pos, $last_dot_pos);
    }
  }

  if ($decimal_pos !== false) {
    $decimals_len = strlen($value) - $decimal_pos - 1;
    if ($decimals_len >= 1 && $decimals_len <= 2) {
      $value = substr($value, 0, $decimal_pos);
    }
  }

  $digits = preg_replace('/\D/u', '', $value);
  if ($digits === '') {
    return null;
  }

  $number = (int) $digits;
  return $number > 0 ? $number : null;
}

/**
 * Каноническая цена тура для сортировки:
 * 1) кешированная SAMO-цена,
 * 2) fallback на ACF price_from.
 */
function bsi_get_tour_sort_price(int $tour_id): ?int
{
  if ($tour_id <= 0) {
    return null;
  }

  if (class_exists('PriceLoaderService') && method_exists('PriceLoaderService', 'getCachedTourPrice')) {
    $cached_price = PriceLoaderService::getCachedTourPrice($tour_id);
    if (is_array($cached_price) && isset($cached_price['price'])) {
      $price = (int) round((float) $cached_price['price']);
      if ($price > 0) {
        return $price;
      }
    }
  }

  if (function_exists('get_field')) {
    return bsi_extract_price_number((string) get_field('price_from', $tour_id));
  }

  return null;
}

/**
 * Сравнение двух цен для сортировки с отправкой null в конец.
 */
function bsi_compare_price_values(?int $price_a, ?int $price_b, string $sort): int
{
  $a_missing = $price_a === null || $price_a <= 0;
  $b_missing = $price_b === null || $price_b <= 0;

  if ($a_missing && $b_missing) {
    return 0;
  }
  if ($a_missing) {
    return 1;
  }
  if ($b_missing) {
    return -1;
  }

  if ($sort === 'price_desc') {
    return $price_b <=> $price_a;
  }

  return $price_a <=> $price_b;
}

function offer_get_country_flag_url($country_id): string
{
  if (!$country_id)
    return '';

  $flag = get_field('flag', $country_id);
  if (!$flag)
    return '';

  if (is_array($flag) && !empty($flag['url']))
    return (string) $flag['url'];
  if (is_numeric($flag))
    return (string) wp_get_attachment_image_url((int) $flag, 'thumbnail');
  if (is_string($flag))
    return $flag;

  return '';
}

function parse_excursion_url(string $url): array
{
  if (empty($url)) {
    return [];
  }

  $parsed = wp_parse_url($url);
  if (empty($parsed['query'])) {
    return [];
  }

  parse_str($parsed['query'], $params);

  $result = [];

  if (!empty($params['TOWNFROMINC'])) {
    $result['TOWNFROMINC'] = (int) $params['TOWNFROMINC'];
  }

  if (!empty($params['STATEINC'])) {
    $result['STATEINC'] = (int) $params['STATEINC'];
  }

  if (!empty($params['TOURINC'])) {
    $result['TOURS'] = (int) $params['TOURINC'];
  }

  if (!empty($params['NIGHTS_FROM'])) {
    $result['NIGHTS_FROM'] = (int) $params['NIGHTS_FROM'];
  }

  if (!empty($params['NIGHTS_TILL'])) {
    $result['NIGHTS_TILL'] = (int) $params['NIGHTS_TILL'];
  }

  return $result;
}

function get_tour_excursion_params(int $tour_id): array
{
  if (!$tour_id || !function_exists('get_field')) {
    return [];
  }

  // Используем tour_booking_url - это поле с URL Samotour
  $booking_url = get_field('tour_booking_url', $tour_id);
  if (empty($booking_url)) {
    return [];
  }

  return parse_excursion_url((string) $booking_url);
}

/**
 * Конвертирует цену из любой валюты в рубли, используя курсы ЦБР и наценку.
 *
 * @param float|int|null $amount Сумма в исходной валюте
 * @param string $currency Код валюты (USD, EUR, GBP, RUB и т.д.)
 * @return int|null Цена в рублях (целое число) или null если конвертация невозможна
 */
function bsi_education_convert_price_to_rub($amount, $currency)
{
  if (!$amount || $amount <= 0) {
    return null;
  }

  $currency = strtoupper(trim((string) $currency));
  if (empty($currency)) {
    return null;
  }

  // Если уже в рублях, просто вернуть целое число
  if ($currency === 'RUB') {
    return (int) round((float) $amount);
  }

  // Получить последний снимок курсов (уже с наценкой)
  if (!function_exists('bsi_currency_history_get_latest_snapshot')) {
    return null;
  }

  $snapshot = bsi_currency_history_get_latest_snapshot();
  if (!$snapshot || empty($snapshot['rates'][$currency])) {
    return null;
  }

  $rate_data = $snapshot['rates'][$currency];
  if (!is_array($rate_data) || !isset($rate_data['value']) || !isset($rate_data['nominal'])) {
    return null;
  }

  // Формула: цена_в_рублях = сумма * (курс_значение / номинал)
  $rate_value = floatval($rate_data['value']);
  $rate_nominal = (int) $rate_data['nominal'];

  if ($rate_nominal <= 0 || $rate_value <= 0) {
    return null;
  }

  $converted = ((float) $amount) * ($rate_value / $rate_nominal);
  return (int) round($converted);
}

/**
 * Получает и форматирует цену образовательной программы.
 * Сначала пытается использовать новую систему (исходная цена + валюта),
 * затем fallback на старое поле.
 *
 * @param int $post_id ID поста
 * @param bool $show_from Добавлять ли "от" в начало
 * @return string Отформатированная цена с символом рубля (например: "от 75 000 ₽")
 */
function bsi_education_get_price_in_rub(int $post_id, bool $show_from = true): string
{
  if ($post_id <= 0 || !function_exists('get_field')) {
    return '';
  }

  // Пытаемся получить цену из новой системы
  $price_original = get_field('education_price_original', $post_id);
  $price_currency = get_field('education_price_currency', $post_id);

  if ($price_original && $price_currency) {
    $price_rub = bsi_education_convert_price_to_rub($price_original, $price_currency);
    if ($price_rub && $price_rub > 0) {
      $formatted = number_format($price_rub, 0, ',', ' ') . ' ₽';
      return $show_from ? 'от ' . $formatted : $formatted;
    }
  }

  // Fallback на старое поле
  $old_price = get_field('education_price', $post_id);
  if ($old_price) {
    return format_price_with_from((string) $old_price, $show_from);
  }

  return '';
}

/**
 * Конвертирует цену программы в рубли (для программ с новой системой).
 * Используется в repeater education_programs.
 *
 * @param array $program Массив программы из repeater
 * @return string Отформатированная цена (например: "50 000 ₽") или пустая строка
 */
function bsi_education_get_program_price_in_rub(array $program): string
{
  // Используем ту же логику что и numeric версия для консистентности
  $price_numeric = bsi_education_get_program_price_numeric_rub($program);
  if ($price_numeric > 0) {
    return number_format($price_numeric, 0, ',', ' ') . ' ₽';
  }
  return '';
}

/**
 * Получает числовую цену программы в рублях (для сортировки и сравнений)
 *
 * @param array $program Данные программы
 * @return int Цена в рублях (целое число) или 0 если цена не найдена
 */
function bsi_education_get_program_price_numeric_rub(array $program): int
{
  // Пытаемся получить из новой системы (оригинальная валюта)
  if (!empty($program['program_price_per_week_original']) && !empty($program['program_price_per_week_currency'])) {
    $price_rub = bsi_education_convert_price_to_rub(
      $program['program_price_per_week_original'],
      $program['program_price_per_week_currency']
    );
    if ($price_rub && $price_rub > 0) {
      return (int) $price_rub;
    }
  }

  // Fallback на старое поле (извлекаем число)
  if (!empty($program['program_price_per_week'])) {
    $price_str = (string) $program['program_price_per_week'];
    $price_numeric = (int) preg_replace('/[^\d]/', '', $price_str);
    if ($price_numeric > 0) {
      return $price_numeric;
    }
  }

  return 0;
}

/**
 * Конвертирует цену из рублей в целевую валюту.
 * Используется для переключения валют на фронтенде.
 *
 * @param int|float $price_rub Цена в рублях
 * @param string $target_currency Целевая валюта (USD, EUR, GBP, RUB)
 * @return float|null Цена в целевой валюте или null если конвертация невозможна
 */
function bsi_education_convert_price_from_rub($price_rub, $target_currency)
{
  if (!$price_rub || $price_rub <= 0) {
    return null;
  }

  $target_currency = strtoupper(trim((string) $target_currency));
  if (empty($target_currency)) {
    return null;
  }

  // Если целевая валюта - рубли, просто вернуть число
  if ($target_currency === 'RUB') {
    return (float) $price_rub;
  }

  // Получить последний снимок курсов (уже с наценкой)
  if (!function_exists('bsi_currency_history_get_latest_snapshot')) {
    return null;
  }

  $snapshot = bsi_currency_history_get_latest_snapshot();
  if (!$snapshot || empty($snapshot['rates'][$target_currency])) {
    return null;
  }

  $rate_data = $snapshot['rates'][$target_currency];
  if (!is_array($rate_data) || !isset($rate_data['value']) || !isset($rate_data['nominal'])) {
    return null;
  }

  // Формула: цена_в_валюте = цена_в_рублях / (курс_значение / номинал)
  $rate_value = floatval($rate_data['value']);
  $rate_nominal = (int) $rate_data['nominal'];

  if ($rate_nominal <= 0 || $rate_value <= 0) {
    return null;
  }

  $converted = ((float) $price_rub) / ($rate_value / $rate_nominal);
  return round($converted, 2);
}

/**
 * Получает цену поста education в запрашиваемой валюте.
 *
 * @param int $post_id ID поста education
 * @param string $currency Целевая валюта (RUB, USD, EUR, GBP)
 * @return array ['value' => number, 'currency' => 'USD', 'formatted' => '1 000 USD']
 *               или null если цена не найдена
 */
function bsi_education_get_price_with_currency(int $post_id, string $currency)
{
  if ($post_id <= 0 || !function_exists('get_field')) {
    return null;
  }

  $currency = strtoupper(trim((string) $currency));
  if (empty($currency)) {
    return null;
  }

  // Сначала получаем цену в рублях
  $price_rub = null;
  $price_original = get_field('education_price_original', $post_id);
  $price_currency = get_field('education_price_currency', $post_id);

  if ($price_original && $price_currency) {
    $price_rub = bsi_education_convert_price_to_rub($price_original, $price_currency);
  }

  // Fallback на старое поле
  if (!$price_rub || $price_rub <= 0) {
    $old_price = get_field('education_price', $post_id);
    if ($old_price) {
      $price_rub = bsi_extract_price_number((string) $old_price);
    }
  }

  if (!$price_rub || $price_rub <= 0) {
    return null;
  }

  // Если целевая валюта - рубли, возвращаем сразу
  if ($currency === 'RUB') {
    return [
      'value' => $price_rub,
      'currency' => 'RUB',
      'formatted' => number_format($price_rub, 0, ',', ' ') . ' ₽',
    ];
  }

  // Конвертируем в целевую валюту
  $price_in_currency = bsi_education_convert_price_from_rub($price_rub, $currency);
  if (!$price_in_currency || $price_in_currency <= 0) {
    return null;
  }

  $currency_symbols = [
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
  ];

  $symbol = $currency_symbols[$currency] ?? $currency;
  $formatted = number_format($price_in_currency, 2, '.', ' ') . ' ' . $symbol;

  return [
    'value' => $price_in_currency,
    'currency' => $currency,
    'formatted' => $formatted,
  ];
}

/**
 * Получает цену программы в запрашиваемой валюте.
 *
 * @param array $program Массив программы из repeater
 * @param string $currency Целевая валюта (RUB, USD, EUR, GBP)
 * @return array ['value' => number, 'currency' => 'USD', 'formatted' => '1 000 USD']
 *               или null если цена не найдена
 */
function bsi_education_get_program_price_with_currency(array $program, string $currency)
{
  if (empty($program)) {
    return null;
  }

  $currency = strtoupper(trim((string) $currency));
  if (empty($currency)) {
    return null;
  }

  // Сначала получаем цену в рублях
  $price_rub = null;

  if (!empty($program['program_price_per_week_original']) && !empty($program['program_price_per_week_currency'])) {
    $price_rub = bsi_education_convert_price_to_rub(
      $program['program_price_per_week_original'],
      $program['program_price_per_week_currency']
    );
  }

  // Fallback на старое поле
  if (!$price_rub || $price_rub <= 0) {
    if (!empty($program['program_price_per_week'])) {
      $price_rub = bsi_extract_price_number((string) $program['program_price_per_week']);
    }
  }

  if (!$price_rub || $price_rub <= 0) {
    return null;
  }

  // Если целевая валюта - рубли, возвращаем сразу
  if ($currency === 'RUB') {
    return [
      'value' => $price_rub,
      'currency' => 'RUB',
      'formatted' => number_format($price_rub, 0, ',', ' ') . ' ₽',
    ];
  }

  // Конвертируем в целевую валюту
  $price_in_currency = bsi_education_convert_price_from_rub($price_rub, $currency);
  if (!$price_in_currency || $price_in_currency <= 0) {
    return null;
  }

  $currency_symbols = [
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
  ];

  $symbol = $currency_symbols[$currency] ?? $currency;
  $formatted = number_format($price_in_currency, 2, '.', ' ') . ' ' . $symbol;

  return [
    'value' => $price_in_currency,
    'currency' => $currency,
    'formatted' => $formatted,
  ];
}