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