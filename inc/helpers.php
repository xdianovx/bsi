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