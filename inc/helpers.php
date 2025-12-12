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