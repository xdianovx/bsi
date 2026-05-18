<?php

declare(strict_types=1);

/**
 * Сервис форматирования и парсинга дат.
 *
 * Унифицирует:
 *  - парсинг дат в форматах Ymd, Y-m-d, d.m.Y, d/m/Y (+ fallback на strtotime)
 *  - русские названия месяцев (родительный и именительный падежи)
 *
 * Публичный API: см. функции-обёртки в inc/helpers.php
 * (format_date_russian, format_month_russian, format_month_year_russian, format_date_short).
 */
class BSI_Date_Formatter
{
  /**
   * Форматы парсинга в порядке приоритета.
   * Используется в format_date_russian / format_month_russian /
   * format_month_year_russian / format_date_short.
   */
  private const PARSE_FORMATS = ['Ymd', 'Y-m-d', 'd.m.Y', 'd/m/Y'];

  /**
   * Месяцы в родительном падеже: «1 января».
   */
  private const MONTHS_GENITIVE = [
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
    12 => 'декабря',
  ];

  /**
   * Месяцы в именительном падеже: «январь 2025».
   */
  private const MONTHS_NOMINATIVE = [
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

  /**
   * Парсит произвольное входное значение в DateTime по форматам PARSE_FORMATS.
   * При неудаче — fallback на strtotime(). Возвращает null, если ничего не сматчилось.
   */
  public static function parse($value): ?DateTime
  {
    $raw = trim((string) $value);
    if ($raw === '') {
      return null;
    }

    foreach (self::PARSE_FORMATS as $format) {
      $obj = DateTime::createFromFormat($format, $raw);
      if ($obj instanceof DateTime) {
        return $obj;
      }
    }

    $ts = strtotime($raw);
    if ($ts) {
      $obj = DateTime::createFromFormat('U', (string) $ts);
      if ($obj instanceof DateTime) {
        return $obj;
      }
    }

    return null;
  }

  /**
   * «1 января» или «1 января 2025» (родительный падеж).
   * При невалидной дате возвращает (string) $value (как старый format_date_russian).
   */
  public static function dayMonthRu($value, bool $with_year = false): string
  {
    if (!$value) {
      return '';
    }

    $obj = self::parse($value);
    if (!$obj instanceof DateTime) {
      return (string) $value;
    }

    $day = $obj->format('j');
    $month_num = (int) $obj->format('n');
    $month_str = self::MONTHS_GENITIVE[$month_num] ?? $obj->format('F');
    $out = $day . ' ' . $month_str;

    return $with_year ? $out . ' ' . $obj->format('Y') : $out;
  }

  /**
   * Только месяц: «январь», «февраль» (именительный падеж).
   * При невалидной дате возвращает ''.
   */
  public static function monthRu($value): string
  {
    if (!$value) {
      return '';
    }

    $obj = self::parse($value);
    if (!$obj instanceof DateTime) {
      return '';
    }

    return self::MONTHS_NOMINATIVE[(int) $obj->format('n')] ?? $obj->format('F');
  }

  /**
   * «январь 2025» (именительный падеж + год).
   * При невалидной дате возвращает ''.
   */
  public static function monthYearRu($value): string
  {
    if (!$value) {
      return '';
    }

    $obj = self::parse($value);
    if (!$obj instanceof DateTime) {
      return '';
    }

    $month_num = (int) $obj->format('n');
    $month_str = self::MONTHS_NOMINATIVE[$month_num] ?? $obj->format('F');

    return $month_str . ' ' . $obj->format('Y');
  }

  /**
   * «1.07» или «1.07 – 15.07» (короткий формат с опциональным диапазоном).
   * При невалидной first-дате возвращает оригинал first-строки.
   */
  public static function dayMonthShort(string $from, string $to = ''): string
  {
    if ($from === '') {
      return '';
    }

    $from_obj = self::parse($from);
    if (!$from_obj instanceof DateTime) {
      return $from;
    }

    $formatted = self::formatDayDotMonth($from_obj);

    if ($to !== '') {
      $to_obj = self::parse($to);
      if ($to_obj instanceof DateTime) {
        return $formatted . ' – ' . self::formatDayDotMonth($to_obj);
      }
    }

    return $formatted;
  }

  /**
   * Форматирует CSV-строку дат вида "21.07.2026, 30.08.2026" →
   * "21 июля, 30 августа". Элементы, которые не парсятся, остаются как есть.
   */
  public static function formatCsvRu($dates_string): string
  {
    if (!$dates_string || !is_string($dates_string)) {
      return (string) $dates_string;
    }

    $dates = array_map('trim', explode(',', $dates_string));
    $out = [];

    foreach ($dates as $date) {
      $formatted = self::dayMonthRu($date);
      // dayMonthRu возвращает оригинал, если парсинг не удался.
      // Сохраняем поведение: если оригинал = форматированному И не выглядит как d.m.Y — оставляем как есть.
      if ($formatted !== $date || preg_match('/\d{1,2}\.\d{1,2}\.\d{4}/', $date)) {
        $out[] = $formatted;
      } else {
        $out[] = $date;
      }
    }

    return implode(', ', $out);
  }

  private static function formatDayDotMonth(DateTime $obj): string
  {
    $day = (int) $obj->format('j');
    $month = (int) $obj->format('n');

    return $day . '.' . str_pad((string) $month, 2, '0', STR_PAD_LEFT);
  }
}
