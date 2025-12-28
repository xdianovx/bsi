<?php

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

function format_date_value($value)
{
  if (!$value) {
    return '';
  }

  $formats = ['Ymd', 'Y-m-d', 'd.m.Y', 'd/m/Y'];

  foreach ($formats as $format) {
    $dt = DateTime::createFromFormat($format, $value);
    if ($dt instanceof DateTime) {
      return $dt->format('d.m.Y');
    }
  }

  $timestamp = strtotime($value);
  if ($timestamp) {
    return date('d.m.Y', $timestamp);
  }

  return $value;
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
  
  $price = str_replace('₽', 'руб', $price);
  
  if (!$has_rub) {
    $price = $price . ' руб';
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

/**
 * Парсит URL экскурсии и извлекает параметры для API Самотура
 *
 * @param string $url URL вида: https://online.bsigroup.ru/search_excursion?TOWNFROMINC=1&STATEINC=32&TOURINC=635
 * @return array Массив с параметрами ['TOWNFROMINC' => 1, 'STATEINC' => 32, 'TOURS' => 635] или пустой массив
 */
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

  // TOURINC в URL соответствует TOURS в API
  if (!empty($params['TOURINC'])) {
    $result['TOURS'] = (int) $params['TOURINC'];
  }

  return $result;
}

/**
 * Получает параметры экскурсии для тура из ACF поля
 *
 * @param int $tour_id ID тура
 * @return array Массив с параметрами для API или пустой массив
 */
function get_tour_excursion_params(int $tour_id): array
{
  if (!$tour_id || !function_exists('get_field')) {
    return [];
  }

  $excursion_link = get_field('tour_excursion_link', $tour_id);
  if (empty($excursion_link)) {
    return [];
  }

  return parse_excursion_url((string) $excursion_link);
}