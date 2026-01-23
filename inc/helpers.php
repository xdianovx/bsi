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

  return $result;
}

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

function bsi_is_page_empty(?int $post_id = null): bool
{
  if (!$post_id) {
    $post_id = get_the_ID();
  }

  if (!$post_id) {
    return true;
  }

  $content = get_post_field('post_content', $post_id);
  
  if (empty($content)) {
    return true;
  }

  $content = trim(strip_tags($content));
  
  return empty($content);
}