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

function format_date_russian($value, bool $with_year = false)
{
  return BSI_Date_Formatter::dayMonthRu($value, $with_year);
}

/**
 * Только месяц: «февраль», «март» (именительный падеж, без числа и года).
 */
function format_month_russian($value): string
{
  return BSI_Date_Formatter::monthRu($value);
}

/**
 * Дата события без числа: «февраль 2025», «март 2012» (месяц в именительном падеже).
 */
function format_month_year_russian($value)
{
  return BSI_Date_Formatter::monthYearRu($value);
}

function format_date_value($value)
{
  return BSI_Date_Formatter::dayMonthRu($value, true);
}

function format_date_short($date_string, $date_to_string = '')
{
  return BSI_Date_Formatter::dayMonthShort((string) $date_string, (string) $date_to_string);
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
 * Значение цены в админке задано как целое × 1 000 — на сайте делится на 1 000 (например 236000 → 236).
 */
function bsi_price_divide_thousand(?int $amount): ?float
{
  if ($amount === null || $amount <= 0) {
    return null;
  }

  return $amount / 1000;
}

/**
 * Данные для set_query_var( 'tour', … ) в template-parts/tour/card — тот же контракт, что страница «Туры» и tours-filter AJAX.
 * Цена на карточке: PriceLoaderService::getCachedTourPrice + ACF + пакет get_batch_tour_prices / priceLoader (как в каталоге).
 *
 * country_id / country_title / flag — только первая страна (primary). countries — все страны тура для ряда флагов.
 *
 * @return array<string, mixed>
 */
function bsi_get_tour_card_query_var(int $tour_id): array
{
  $tour_id = (int) $tour_id;
  if ($tour_id <= 0) {
    return [];
  }

  $country_id_tour = function_exists('bsi_get_tour_primary_country_id')
    ? bsi_get_tour_primary_country_id($tour_id)
    : 0;

  $country_title = $country_id_tour ? (string) get_the_title($country_id_tour) : '';
  $flag_url = ($country_id_tour > 0 && function_exists('bsi_get_country_flag_url'))
    ? bsi_get_country_flag_url($country_id_tour)
    : '';

  $countries = function_exists('bsi_get_tour_country_entries')
    ? bsi_get_tour_country_entries($tour_id)
    : [];

  $country_slug = '';
  if ($country_id_tour > 0) {
    $country_slug = (string) get_post_field('post_name', $country_id_tour);
  }

  return [
    'id' => $tour_id,
    'url' => (string) get_permalink($tour_id),
    'title' => (string) get_the_title($tour_id),
    'flag' => $flag_url,
    'country_title' => $country_title,
    'country_id' => (int) $country_id_tour,
    'country_slug' => $country_slug,
    'countries' => $countries,
  ];
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

/**
 * Locale-aware сравнение строк для русской сортировки с fallback.
 */
function bsi_compare_titles_ru(string $title_a, string $title_b): int
{
  $title_a = trim($title_a);
  $title_b = trim($title_b);

  if ($title_a === $title_b) {
    return 0;
  }

  if (class_exists('Collator')) {
    static $collator = null;

    if ($collator === null) {
      $collator = new Collator('ru_RU');
      $collator->setStrength(Collator::PRIMARY);
      $collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);
    }

    $result = $collator->compare($title_a, $title_b);
    if ($result !== false) {
      return (int) $result;
    }
  }

  return strnatcmp(
    mb_strtolower($title_a, 'UTF-8'),
    mb_strtolower($title_b, 'UTF-8')
  );
}

/**
 * Возвращает полный список стран, у которых есть туры, отсортированный по RU locale.
 *
 * @return array<int, WP_Post>
 */
function bsi_get_tour_countries_sorted(): array
{
  $all_tours = get_posts([
    'post_type' => 'tour',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ]);

  if (empty($all_tours) || !function_exists('get_field')) {
    return [];
  }

  $country_ids = [];
  foreach ($all_tours as $tour_id) {
    $ids = function_exists('bsi_get_tour_country_ids')
      ? bsi_get_tour_country_ids((int) $tour_id)
      : [];

    foreach ($ids as $country_id) {
      $country_id = (int) $country_id;
      if ($country_id > 0) {
        $country_ids[] = $country_id;
      }
    }
  }

  $country_ids = array_values(array_unique(array_filter($country_ids)));
  if (empty($country_ids)) {
    return [];
  }

  $countries = get_posts([
    'post_type' => 'country',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'post_parent' => 0,
    'post__in' => $country_ids,
    'orderby' => 'post__in',
  ]);

  if (empty($countries)) {
    return [];
  }

  usort($countries, static function ($a, $b): int {
    $title_a = isset($a->post_title) ? (string) $a->post_title : '';
    $title_b = isset($b->post_title) ? (string) $b->post_title : '';
    return bsi_compare_titles_ru($title_a, $title_b);
  });

  return $countries;
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

  if (!empty($params['CHECKIN_BEG'])) {
    $result['CHECKIN_BEG'] = preg_replace('/\D/', '', (string) $params['CHECKIN_BEG']);
  }
  if (!empty($params['CHECKIN_END'])) {
    $result['CHECKIN_END'] = preg_replace('/\D/', '', (string) $params['CHECKIN_END']);
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
 * Находит минимальную цену среди номеров отеля (repeater hotel_rooms),
 * конвертируя каждую цену в рубли по её собственной валюте (price_currency).
 * Используется как приоритетный источник цены отеля (в карточке и сайдбаре):
 * если у отеля заполнены номера — цена отеля берётся отсюда, а не из общего поля.
 *
 * @param int $hotel_id ID поста hotel
 * @return array{rub:int,original:float,currency:string}|array{} Пусто, если цену найти не удалось
 */
function bsi_hotel_min_room_price(int $hotel_id): array
{
  if (!$hotel_id || !function_exists('get_field')) {
    return [];
  }

  $rooms = get_field('hotel_rooms', $hotel_id);
  $rooms = is_array($rooms) ? $rooms : [];

  $best = [];
  foreach ($rooms as $room) {
    if (empty($room['price_from'])) {
      continue;
    }
    $currency = !empty($room['price_currency']) ? strtoupper((string) $room['price_currency']) : 'RUB';
    $rub = bsi_education_convert_price_to_rub($room['price_from'], $currency);
    if ($rub === null) {
      continue;
    }
    if (empty($best) || $rub < $best['rub']) {
      $best = [
        'rub' => $rub,
        'original' => (float) $room['price_from'],
        'currency' => $currency,
      ];
    }
  }

  return $best;
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
 * Формирует массив data-attributes для переключателя валют.
 * Используется везде, где нужны data-price-rub / data-price-original / data-price-currency.
 *
 * @param array $program ACF-программа с полями program_price_per_week_original и т.д.
 * @return array ['price-rub' => int, 'price-original' => float, 'price-currency' => string]
 */
function bsi_education_build_price_data_attrs(array $program): array {
  if (empty($program)) {
    return [];
  }

  $price_rub = bsi_education_get_program_price_numeric_rub($program);
  if ($price_rub <= 0) {
    return [];
  }

  $attrs = ['price-rub' => $price_rub];

  if (!empty($program['program_price_per_week_original']) && !empty($program['program_price_per_week_currency'])) {
    $attrs['price-original'] = (float) $program['program_price_per_week_original'];
    $attrs['price-currency'] = strtoupper((string) $program['program_price_per_week_currency']);
  }

  return $attrs;
}

/**
 * Минимальная цена школы в рублях так, как её видит посетитель.
 *
 * Карточки и страница школы считают цену по программам, а legacy-поле
 * education_price берут только когда программ нет: в нём попадаются
 * значения за весь курс, а не за неделю. Для мета-описаний и schema.org
 * цена должна совпадать с той, что показана на странице.
 *
 * @param int $education_id ID школы (post ID)
 * @return int Цена в рублях или 0
 */
function bsi_education_display_price_rub(int $education_id): int {
  if (!function_exists('get_field')) {
    return 0;
  }

  // Цены ниже этого порога — опечатка в поле (встречались значения вроде
  // «91»). В описании и в schema.org такая цена становится обещанием,
  // которого никто не выполнит, поэтому лучше не показывать ничего.
  $min_sane_price = 1000;

  $programs = get_field('education_programs', $education_id);
  if (is_array($programs)) {
    $min_price = 0;
    foreach ($programs as $program) {
      if (!is_array($program)) {
        continue;
      }
      $price = bsi_education_get_program_price_numeric_rub($program);
      if ($price >= $min_sane_price && ($min_price === 0 || $price < $min_price)) {
        $min_price = $price;
      }
    }
    if ($min_price > 0) {
      return $min_price;
    }
  }

  $price_val = get_field('education_price', $education_id);
  if (!empty($price_val)) {
    $legacy_price = (int) preg_replace('/[^\d]/', '', (string) $price_val);
    if ($legacy_price >= $min_sane_price) {
      return $legacy_price;
    }
  }

  return 0;
}

/**
 * Получает минимальную цену школы в рублях для сортировки.
 * Учитывает и старое поле education_price и новую систему программ.
 *
 * @param int $education_id ID школы (post ID)
 * @return int Цена в рублях или 0 если цена не найдена
 */
function bsi_education_get_program_price_numeric_rub_from_post(int $education_id): int {
  if (!function_exists('get_field')) {
    return 0;
  }

  $price_val = get_field('education_price', $education_id);
  if (!empty($price_val)) {
    $num = (int) preg_replace('/[^\d]/', '', (string) $price_val);
    if ($num > 0) {
      return $num;
    }
  }

  $programs = get_field('education_programs', $education_id);
  if (!is_array($programs) || empty($programs)) {
    return 0;
  }

  $min_price = 0;
  foreach ($programs as $program) {
    $price = bsi_education_get_program_price_numeric_rub($program);
    if ($price > 0 && ($min_price === 0 || $price < $min_price)) {
      $min_price = $price;
    }
  }

  return $min_price;
}

/**
 * Публичный URL страницы политики обработки персональных данных.
 */
function bsi_get_privacy_policy_url(): string
{
  $page_id = (int) apply_filters('bsi_privacy_policy_page_id', 47);
  if ($page_id > 0) {
    $permalink = get_permalink($page_id);
    if (is_string($permalink) && $permalink !== '') {
      return $permalink;
    }
  }

  return home_url('/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/');
}

/**
 * Чекбокс согласия с политикой (по умолчанию не отмечен).
 *
 * @param array $opt {
 *   @type string $variant         'program-booking' | 'visa-page' | 'input-item' | 'event-booking-cta'
 *   @type string $checkbox_id     атрибут id у input
 *   @type string $wrapper_class   доп. классы корневой обёртки (например 'white')
 *   @type bool   $html_required   атрибут HTML required
 * }
 */
function bsi_render_privacy_consent_checkbox(array $opt = []): void
{
  $variant = isset($opt['variant']) ? (string) $opt['variant'] : 'visa-page';
  $checkbox_id = isset($opt['checkbox_id']) && $opt['checkbox_id'] !== ''
    ? (string) $opt['checkbox_id']
    : 'privacy-consent-' . wp_unique_id('');
  $wrapper_class = isset($opt['wrapper_class']) ? (string) $opt['wrapper_class'] : '';
  $html_required = !empty($opt['html_required']);

  $privacy_url = bsi_get_privacy_policy_url();

  require get_template_directory() . '/template-parts/form-privacy-consent.php';
}
/**
 * Русское склонение существительного по числу.
 *
 * @param int    $n    Число.
 * @param string $one  Форма для 1 (день).
 * @param string $few  Форма для 2–4 (дня).
 * @param string $many Форма для 0, 5–20 (дней).
 */
function bsi_plural_ru(int $n, string $one, string $few, string $many): string
{
  $n = abs($n) % 100;
  $n1 = $n % 10;
  if ($n > 10 && $n < 20) {
    return $many;
  }
  if ($n1 > 1 && $n1 < 5) {
    return $few;
  }
  if ($n1 === 1) {
    return $one;
  }
  return $many;
}

/**
 * Диапазон дат заезд–выезд + дни/ночи для карточки размещения.
 *
 * @param string $from ISO-дата заезда (Y-m-d).
 * @param string $to   ISO-дата выезда (Y-m-d), может быть пустой.
 * @return array{label:string,duration:string,nights:int,days:int,from_ts:int,to_ts:int}
 */
function bsi_format_stay_range(string $from, string $to): array
{
  $out = ['label' => '', 'duration' => '', 'nights' => 0, 'days' => 0, 'from_ts' => 0, 'to_ts' => 0];
  $from = trim($from);
  if ($from === '') {
    return $out;
  }
  $from_ts = strtotime($from);
  if (!$from_ts) {
    return $out;
  }
  $out['from_ts'] = $from_ts;

  $to = trim($to);
  $to_ts = $to !== '' ? strtotime($to) : 0;

  if ($to_ts && $to_ts > $from_ts) {
    $out['to_ts'] = $to_ts;
    $nights = (int) floor(($to_ts - $from_ts) / DAY_IN_SECONDS);
    $days = $nights + 1;
    $out['nights'] = $nights;
    $out['days'] = $days;

    $same_year = date('Y', $from_ts) === date('Y', $to_ts);
    $same_month = $same_year && date('m', $from_ts) === date('m', $to_ts);
    if ($same_month) {
      $out['label'] = date_i18n('j', $from_ts) . '–' . date_i18n('j F Y', $to_ts);
    } elseif ($same_year) {
      $out['label'] = date_i18n('j F', $from_ts) . ' – ' . date_i18n('j F Y', $to_ts);
    } else {
      $out['label'] = date_i18n('j F Y', $from_ts) . ' – ' . date_i18n('j F Y', $to_ts);
    }

    $out['duration'] = $days . ' ' . bsi_plural_ru($days, 'день', 'дня', 'дней')
      . ' / ' . $nights . ' ' . bsi_plural_ru($nights, 'ночь', 'ночи', 'ночей');
  } else {
    $out['label'] = date_i18n('j F Y', $from_ts);
  }

  return $out;
}

/**
 * Лейбл даты строки события (event_dates): один день или диапазон.
 *
 * @param string $from ISO-дата начала (Y-m-d).
 * @param string $to   ISO-дата окончания (Y-m-d), может быть пустой.
 * @return string '08.06.2026' (один день) или '6.06 – 8.06.2026' (диапазон). '' если from невалиден.
 */
function bsi_format_event_date_range(string $from, string $to = ''): string
{
  $from = trim($from);
  if ($from === '') {
    return '';
  }
  $from_ts = strtotime($from);
  if (!$from_ts) {
    return '';
  }

  $to = trim($to);
  $to_ts = $to !== '' ? strtotime($to) : 0;

  // end пусто / невалидно / совпадает → один день.
  if (!$to_ts || $to_ts === $from_ts) {
    return date_i18n('d.m.Y', $from_ts);
  }

  // Порядок ввода не важен: меньшая дата — начало, большая — окончание.
  $start_ts = min($from_ts, $to_ts);
  $end_ts = max($from_ts, $to_ts);

  return date_i18n('j.m', $start_ts) . ' – ' . date_i18n('j.m.Y', $end_ts);
}

/**
 * Текст цены для карточки тура: живая цена Само (transient PriceLoaderService),
 * иначе статичная цена из ACF `price_from`.
 *
 * Префикс «от » добавляется по флагу `show_price_from` и только если его ещё нет
 * в самом значении поля (иначе получалось «от от 99 000 руб»).
 *
 * @return string Пустая строка, если цены нет ни в кеше, ни в ACF.
 */
function bsi_tour_card_price_text(int $tour_id): string
{
  if ($tour_id <= 0) {
    return '';
  }

  $cached_price = class_exists('PriceLoaderService') ? PriceLoaderService::getCachedTourPrice($tour_id) : null;
  if (is_array($cached_price) && !empty($cached_price['price_formatted'])) {
    return $cached_price['price_formatted'] . ' ₽ / чел';
  }

  if (!function_exists('get_field')) {
    return '';
  }

  $price_val = get_field('price_from', $tour_id);
  if (is_numeric($price_val)) {
    $price_value = number_format((float) $price_val, 0, '.', ' ');
  } elseif (is_string($price_val) && trim($price_val) !== '') {
    $price_value = trim($price_val);
  } else {
    return '';
  }

  // Значение уже может содержать «от» и/или «руб» — не дублируем.
  $has_from = (bool) preg_match('/^от\b/ui', $price_value);
  $has_currency = (bool) preg_match('/(₽|руб)/ui', $price_value);

  $show_from = get_field('show_price_from', $tour_id) !== false;
  $prefix = ($show_from && !$has_from) ? 'от ' : '';
  $suffix = $has_currency ? ' / чел' : ' ₽ / чел';

  return $prefix . $price_value . $suffix;
}

/**
 * Однократный вывод модалки заявки на тур (template-parts/tour/booking-modal.php) в футере.
 *
 * Вызывается из карточек тура — модалка нужна там, где есть кнопка `.js-tour-booking-btn`
 * (тур без ссылки на Самотур). Повторные вызовы игнорируются.
 */
function bsi_enqueue_tour_booking_modal(): void
{
  static $queued = false;
  if ($queued) {
    return;
  }
  $queued = true;

  add_action('wp_footer', static function () {
    get_template_part('template-parts/tour/booking-modal');
  }, 20);
}

/**
 * Сколько карточек показывать в слайдерах главной страницы.
 *
 * Слайдеры показывают 2 карточки за раз, но подборки «избранного»
 * рендерились целиком: 59 туров, 28 программ обучения, 22 события —
 * 712 тегов <img> и 843 КБ HTML на одну страницу. Ограничиваем разумным
 * числом; фильтр bsi_homepage_slider_limit позволяет поменять.
 *
 * У экскурсионных туров лимит выше: над слайдером есть фильтр по странам,
 * и его список строится только из отрендеренных карточек — при лимите 12
 * страны из хвоста подборки (например Филиппины) пропадали из фильтра.
 * Картинки карточек вне первых слайдов грузятся лениво (см. tour/card.php).
 *
 * @param string $context Слайдер: 'tour', 'education', 'hotel', 'event' или ''.
 */
function bsi_homepage_slider_limit(string $context = ''): int
{
	$defaults = [
		'tour' => 60,
	];

	$default = $defaults[$context] ?? 12;
	$limit = (int) apply_filters('bsi_homepage_slider_limit', $default, $context);

	return $limit > 0 ? $limit : $default;
}

/**
 * Внешняя ссылка типа визы (визовые проекты на поддоменах).
 *
 * Возвращает базовый URL без UTM-меток или '' для обычных типов.
 * См. wiki/docs/visa-external-projects.md
 */
function bsi_visa_type_external_url($term): string
{
	if (!function_exists('get_field')) {
		return '';
	}

	$term_id = $term instanceof WP_Term ? (int) $term->term_id : (int) $term;
	if ($term_id <= 0) {
		return '';
	}

	$url = get_field('visa_type_external_url', 'visa_type_' . $term_id);

	return is_string($url) ? trim($url) : '';
}

/**
 * Название типа визы в левом меню страны.
 *
 * Для внешних проектов бренд отличается от названия типа:
 * тип «Вид на жительство» показывается как «Золотая виза».
 */
function bsi_visa_type_menu_label(WP_Term $term): string
{
	$label = function_exists('get_field')
		? get_field('visa_type_menu_label', 'visa_type_' . (int) $term->term_id)
		: '';

	$label = is_string($label) ? trim($label) : '';

	return $label !== '' ? $label : (string) $term->name;
}

/**
 * Ссылка на тип визы: внутренняя страница или внешний проект с UTM.
 *
 * $placement попадает в utm_content — по нему в аналитике видно,
 * откуда пришёл клик: 'vizy-page', 'country-menu', 'country-menu-mobile',
 * 'redirect'.
 *
 * @return array{url: string, external: bool}
 */
function bsi_visa_type_link(string $country_slug, WP_Term $term, string $placement): array
{
	$external = bsi_visa_type_external_url($term);

	if ($external === '') {
		return [
			'url' => home_url("/country/{$country_slug}/visa/{$term->slug}/"),
			'external' => false,
		];
	}

	$campaign = function_exists('get_field')
		? get_field('visa_type_utm_campaign', 'visa_type_' . (int) $term->term_id)
		: '';
	$campaign = is_string($campaign) ? trim($campaign) : '';
	if ($campaign === '') {
		$campaign = (string) $term->slug;
	}

	$url = add_query_arg([
		'utm_source' => 'bsigroup.ru',
		'utm_medium' => 'referral',
		'utm_campaign' => $campaign,
		'utm_content' => $placement,
	], $external);

	return ['url' => $url, 'external' => true];
}
