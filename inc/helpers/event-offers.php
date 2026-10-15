<?php
/**
 * Событийный тур — одна запись на событие (артист + город): даты, секторы и цены билетов,
 * формат «только билет / тур с билетом» и состояние «событие завершено».
 *
 * Данные:
 * - event_dates[]            — даты (date_value / date_value_end / date_time); город и площадка — у события
 *                              (курорт resort / event_venue), старые date_city / date_venue / date_tour_url — запасные;
 * - event_sectors[]          — секторы: цена на все даты + исключения по датам + абонемент;
 * - event_tour_sector        — сектор, входящий в тур (остальные — доплата через заявку);
 * - event_no_separate_tickets — билеты отдельно не продаются;
 * - event_notify_email       — копия всех заявок события (event_tickets_email убран 14.10, читается копией);
 * - event_status             — auto / closed / cancelled.
 *
 * Новый виджет включается, только если заполнены секторы или ссылка на тур у даты —
 * старые записи выглядят как раньше.
 */

if (!defined('ABSPATH')) {
  exit;
}

const BSI_EVENT_LAST_DATE_META = 'bsi_event_last_date';
const BSI_EVENT_PASS_KEY = 'all';

/**
 * Цена в валюте → структура для data-атрибутов переключателя валют.
 *
 * @return array{rub:int,original:?float,currency:?string}|null
 */
function bsi_event_price_struct($amount, string $currency): ?array
{
  if ($amount === null || $amount === '' || !is_numeric($amount)) {
    return null;
  }
  $amount = (float) $amount;
  if ($amount <= 0) {
    return null;
  }
  $currency = strtoupper(trim($currency)) ?: 'RUB';
  $rub = function_exists('bsi_education_convert_price_to_rub')
    ? bsi_education_convert_price_to_rub($amount, $currency)
    : ($currency === 'RUB' ? $amount : null);
  if ($rub === null || $rub <= 0) {
    return null;
  }

  return [
    'rub' => (int) round($rub),
    'original' => $currency !== 'RUB' ? $amount : null,
    'currency' => $currency !== 'RUB' ? $currency : null,
  ];
}

/**
 * Атрибуты для .js-event-price (переключатель «Показать в валюте»).
 */
function bsi_event_price_attrs(?array $price, bool $has_from = false): string
{
  if (!$price) {
    return '';
  }
  $out = ' data-price-rub="' . esc_attr((string) (int) $price['rub']) . '"';
  if (!empty($price['original']) && !empty($price['currency'])) {
    $out .= ' data-price-original="' . esc_attr((string) $price['original']) . '"';
    $out .= ' data-price-currency="' . esc_attr((string) $price['currency']) . '"';
  }
  if ($has_from) {
    $out .= ' data-has-from="true"';
  }

  return $out;
}

function bsi_event_price_text(?array $price, bool $has_from = false): string
{
  if (!$price) {
    return '';
  }

  return ($has_from ? 'от ' : '') . number_format((int) $price['rub'], 0, ',', ' ') . ' ₽';
}

/**
 * Сегодня в часовом поясе сайта, 'Y-m-d'.
 */
function bsi_event_today(): string
{
  return wp_date('Y-m-d');
}

/**
 * Последняя дата события 'Y-m-d' (по повторителю дат, иначе по датам первого экрана).
 */
function bsi_event_last_date(int $post_id): string
{
  if (!function_exists('get_field')) {
    return '';
  }
  $last = '';
  $rows = get_field('event_dates', $post_id);
  if (is_array($rows)) {
    foreach ($rows as $row) {
      foreach (['date_value_end', 'date_value'] as $key) {
        $ts = !empty($row[$key]) ? strtotime((string) $row[$key]) : 0;
        if ($ts) {
          $d = gmdate('Y-m-d', $ts);
          if ($d > $last) {
            $last = $d;
          }
        }
      }
    }
  }
  if ($last === '') {
    foreach (['event_hero_date_end', 'event_hero_date'] as $key) {
      $raw = get_field($key, $post_id);
      $ts = is_string($raw) && $raw !== '' ? strtotime($raw) : 0;
      if ($ts) {
        $last = gmdate('Y-m-d', $ts);
        break;
      }
    }
  }

  return $last;
}

/**
 * Состояние события: active | past | closed | cancelled.
 */
function bsi_event_status(int $post_id): string
{
  $manual = function_exists('get_field') ? (string) get_field('event_status', $post_id) : '';
  if (in_array($manual, ['closed', 'cancelled'], true)) {
    return $manual;
  }
  $last = bsi_event_last_date($post_id);
  if ($last !== '' && $last < bsi_event_today()) {
    return 'past';
  }

  return 'active';
}

/**
 * Город события — из базы курортов (таксономия resort, поле «Город» в «Когда и где»).
 */
function bsi_event_city(int $post_id): string
{
  $terms = get_the_terms($post_id, 'resort');

  return (!empty($terms) && !is_wp_error($terms)) ? (string) $terms[0]->name : '';
}

/**
 * Даты события для виджета (только будущие и сегодняшние).
 *
 * @return array<int,array<string,mixed>>
 */
function bsi_event_offer_dates(int $post_id): array
{
  if (!function_exists('get_field')) {
    return [];
  }
  $rows = get_field('event_dates', $post_id);
  // Старые туры без таблицы «Даты» — дата только в hero (на проде 9 туров на 14.10)
  if (empty($rows) && ($hero = (string) get_post_meta($post_id, 'event_hero_date', true)) !== '') {
    $rows = [['date_value' => $hero, 'date_value_end' => (string) get_post_meta($post_id, 'event_hero_date_end', true)]];
  }
  if (!is_array($rows)) {
    return [];
  }
  $today = bsi_event_today();
  $event_time = trim((string) get_field('event_time', $post_id));
  $event_city = bsi_event_city($post_id);
  $event_venue = trim((string) get_field('event_venue', $post_id));
  $out = [];
  foreach ($rows as $row) {
    $ts = !empty($row['date_value']) ? strtotime((string) $row['date_value']) : 0;
    if (!$ts) {
      continue;
    }
    $key = gmdate('Y-m-d', $ts);
    $end_ts = !empty($row['date_value_end']) ? strtotime((string) $row['date_value_end']) : 0;
    $end = $end_ts && $end_ts !== $ts ? gmdate('Y-m-d', $end_ts) : '';
    if (($end !== '' ? $end : $key) < $today) {
      continue;
    }
    $out[$key] = [
      'key' => $key,
      'date' => $key,
      'date_end' => $end,
      'label' => $end !== ''
        ? date_i18n('j', $ts) . '–' . date_i18n('j F', $end_ts)
        : date_i18n('j F', $ts),
      'weekday' => $end !== '' ? '' : date_i18n('D', $ts),
      'time' => trim((string) ($row['date_time'] ?? '')) ?: $event_time,
      'city' => $event_city !== '' ? $event_city : trim((string) ($row['date_city'] ?? '')),
      // Площадка даты важнее общей: у турниров (Суперкубок) каждый день — свой стадион.
      'venue' => trim((string) ($row['date_venue'] ?? '')) ?: $event_venue,
      // Своя схема зала у даты — у турниров на разных стадионах; пусто — общая схема события.
      'scheme_url' => bsi_event_date_scheme_url($row['date_scheme'] ?? ''),
      'tour_url' => trim((string) ($row['date_tour_url'] ?? '')),
    ];
  }
  ksort($out);

  return array_values($out);
}

/**
 * URL схемы даты: ACF отдаёт url, сырое мета — ID вложения.
 */
function bsi_event_date_scheme_url($value): string
{
  if (is_array($value)) {
    return (string) ($value['url'] ?? '');
  }
  if (is_numeric($value) && (int) $value > 0) {
    return (string) wp_get_attachment_url((int) $value);
  }

  return is_string($value) ? trim($value) : '';
}

/**
 * «6, 7 марта 2027» / «30 декабря 2026, 2 января 2027» — подпись всех дат (карточка, hero).
 *
 * @param array<int,array<string,mixed>> $dates
 */
function bsi_event_dates_label(array $dates): string
{
  if (empty($dates)) {
    return '';
  }
  $groups = [];
  foreach ($dates as $d) {
    $ts = strtotime($d['date']);
    $ym = gmdate('Y-m', $ts);
    $piece = date_i18n('j', $ts);
    if (!empty($d['date_end'])) {
      $end_ts = strtotime($d['date_end']);
      if (gmdate('Y-m', $end_ts) !== $ym) {
        // Многодневное через границу месяца — отдельной группой целиком.
        $groups[] = ['ym' => $ym . '-' . $d['date'], 'days' => [date_i18n('j F', $ts) . ' – ' . date_i18n('j F', $end_ts)], 'ts' => $end_ts, 'full' => true];
        continue;
      }
      $piece .= '–' . date_i18n('j', $end_ts);
    }
    $last = end($groups);
    if ($last && $last['ym'] === $ym && empty($last['full'])) {
      $groups[key($groups)]['days'][] = $piece;
    } else {
      $groups[] = ['ym' => $ym, 'days' => [$piece], 'ts' => $ts, 'full' => false];
    }
  }
  $parts = [];
  $last_year = gmdate('Y', (int) end($groups)['ts']);
  foreach ($groups as $i => $g) {
    $text = implode(', ', $g['days']);
    if (empty($g['full'])) {
      // 'j F' даёт месяц в родительном падеже («марта»), отрезаем число.
      $text .= ' ' . preg_replace('/^\d+\s+/u', '', date_i18n('j F', (int) $g['ts']));
    }
    $year = gmdate('Y', (int) $g['ts']);
    if ($year !== $last_year || $i === count($groups) - 1) {
      $text .= ' ' . $year;
    }
    $parts[] = $text;
  }

  return implode(', ', $parts);
}

/**
 * Секторы и цены по датам.
 *
 * @param string[] $date_keys ключи дат 'Y-m-d'
 * @return array<int,array<string,mixed>>
 */
function bsi_event_sectors(int $post_id, array $date_keys): array
{
  if (!function_exists('get_field')) {
    return [];
  }
  // По ключу: у старых записей нет meta-ссылки _event_sectors, по имени ACF вернёт null и подстановка из легенды не сработает.
  $rows = get_field('field_event_sectors', $post_id);
  if (!is_array($rows)) {
    return [];
  }
  $out = [];
  foreach ($rows as $row) {
    $name = trim((string) ($row['sector_name'] ?? ''));
    if ($name === '') {
      continue;
    }
    $currency = (string) ($row['sector_currency'] ?? 'RUB');
    $base = bsi_event_price_struct($row['sector_price'] ?? null, $currency);
    $prices = [];
    foreach ($date_keys as $key) {
      $prices[$key] = $base;
    }
    if (!empty($row['sector_date_prices']) && is_array($row['sector_date_prices'])) {
      foreach ($row['sector_date_prices'] as $ov) {
        $ts = !empty($ov['sdp_date']) ? strtotime((string) $ov['sdp_date']) : 0;
        $key = $ts ? gmdate('Y-m-d', $ts) : '';
        if ($key !== '' && array_key_exists($key, $prices)) {
          $prices[$key] = bsi_event_price_struct($ov['sdp_price'] ?? null, $currency);
        }
      }
    }
    $pass = bsi_event_price_struct($row['sector_pass_price'] ?? null, $currency);
    if ($pass) {
      $prices[BSI_EVENT_PASS_KEY] = $pass;
    }
    $out[] = [
      'name' => $name,
      'description' => trim((string) ($row['sector_description'] ?? '')),
      'color' => sanitize_hex_color((string) ($row['sector_color'] ?? '')) ?: '',
      'currency' => strtoupper($currency) ?: 'RUB',
      'prices' => $prices,
      'tour_extra' => bsi_event_price_struct($row['sector_tour_extra'] ?? null, $currency),
    ];
  }

  return $out;
}

/**
 * Минимальная цена из набора структур.
 *
 * @param array<int,?array> $list
 */
function bsi_event_min_price(array $list): ?array
{
  $best = null;
  foreach ($list as $p) {
    if ($p && ($best === null || (int) $p['rub'] < (int) $best['rub'])) {
      $best = $p;
    }
  }

  return $best;
}

/**
 * Всё для виджета «дата → формат» на странице события.
 *
 * @return array<string,mixed>
 */
function bsi_event_offers(int $post_id): array
{
  $dates = bsi_event_offer_dates($post_id);
  $keys = array_column($dates, 'key');
  $sectors = bsi_event_sectors($post_id, $keys);
  $no_tickets = function_exists('get_field') && (bool) get_field('event_no_separate_tickets', $post_id);
  $tour_sector = function_exists('get_field') ? trim((string) get_field('event_tour_sector', $post_id)) : '';

  $has_pass = false;
  foreach ($sectors as $s) {
    if (!empty($s['prices'][BSI_EVENT_PASS_KEY])) {
      $has_pass = true;
      break;
    }
  }

  $ticket_from = [];
  foreach (array_merge($keys, $has_pass ? [BSI_EVENT_PASS_KEY] : []) as $key) {
    $ticket_from[$key] = bsi_event_min_price(array_map(static fn($s) => $s['prices'][$key] ?? null, $sectors));
  }

  $has_date_tour = false;
  foreach ($dates as $d) {
    if ($d['tour_url'] !== '') {
      $has_date_tour = true;
      break;
    }
  }

  return [
    'enabled' => !empty($sectors) || $has_date_tour,
    'dates' => $dates,
    'sectors' => $sectors,
    'has_pass' => $has_pass,
    'tickets_enabled' => !$no_tickets && !empty($sectors),
    'ticket_from' => $ticket_from,
    'ticket_min' => bsi_event_min_price(array_values($ticket_from)),
    'tour_sector' => $tour_sector,
  ];
}

/**
 * Доплата к туру за сектор на дату: своя из админки, иначе разница с сектором тура.
 */
function bsi_event_sector_tour_extra(array $sector, array $sectors, string $tour_sector, string $date_key): ?array
{
  if ($tour_sector === '' || $sector['name'] === $tour_sector) {
    return null;
  }
  if (!empty($sector['tour_extra'])) {
    return $sector['tour_extra'];
  }
  $own = $sector['prices'][$date_key] ?? null;
  $base = null;
  foreach ($sectors as $s) {
    if ($s['name'] === $tour_sector) {
      $base = $s['prices'][$date_key] ?? null;
      break;
    }
  }
  if (!$own || !$base) {
    return null;
  }
  $diff = (int) $own['rub'] - (int) $base['rub'];
  if ($diff <= 0) {
    return null;
  }
  $out = ['rub' => $diff, 'original' => null, 'currency' => null];
  if (!empty($own['currency']) && $own['currency'] === ($base['currency'] ?? null)) {
    $out['original'] = (float) $own['original'] - (float) $base['original'];
    $out['currency'] = $own['currency'];
  }

  return $out;
}

/* -------------------------------------------------------------------------
 * Последняя дата в мете — для фильтра каталога по завершённым событиям.
 * ---------------------------------------------------------------------- */

function bsi_event_sync_last_date(int $post_id): void
{
  $last = bsi_event_last_date($post_id);
  if ($last === '') {
    delete_post_meta($post_id, BSI_EVENT_LAST_DATE_META);
    return;
  }
  update_post_meta($post_id, BSI_EVENT_LAST_DATE_META, str_replace('-', '', $last));
}

add_action('acf/save_post', static function ($post_id) {
  if (is_numeric($post_id) && get_post_type((int) $post_id) === 'event') {
    bsi_event_sync_last_date((int) $post_id);
  }
}, 20);

// Однократно проставляем мету уже существующим событиям.
add_action('admin_init', static function () {
  if (!current_user_can('manage_options') || get_option('bsi_event_last_date_synced_v1')) {
    return;
  }
  $ids = get_posts([
    'post_type' => 'event',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'suppress_filters' => true,
  ]);
  foreach ($ids as $id) {
    bsi_event_sync_last_date((int) $id);
  }
  update_option('bsi_event_last_date_synced_v1', time());
}, 30);

/**
 * Завершённые события (прошли / закрыты / отменены) не попадают в списки на сайте.
 * Сама страница события открывается — показывает состояние «завершено».
 */
add_action('pre_get_posts', static function (WP_Query $query) {
  if (function_exists('bsi_content_schedule_allow_query_filters') && !bsi_content_schedule_allow_query_filters()) {
    return;
  }
  if (!function_exists('bsi_content_schedule_allow_query_filters') && is_admin() && !wp_doing_ajax()) {
    return;
  }
  if ($query->is_singular() || $query->get('bsi_include_finished_events')) {
    return;
  }
  $types = (array) $query->get('post_type');
  if (!in_array('event', $types, true)) {
    return;
  }

  // Существующий meta_query (у страны там relation OR) — отдельной веткой, иначе наши условия попадут в OR.
  $existing = (array) $query->get('meta_query');
  $meta_query = ['relation' => 'AND'];
  if (!empty($existing)) {
    $meta_query[] = $existing;
  }
  $meta_query[] = [
    'relation' => 'OR',
    ['key' => BSI_EVENT_LAST_DATE_META, 'compare' => 'NOT EXISTS'],
    ['key' => BSI_EVENT_LAST_DATE_META, 'value' => wp_date('Ymd'), 'compare' => '>=', 'type' => 'NUMERIC'],
  ];
  $meta_query[] = [
    'relation' => 'OR',
    ['key' => 'event_status', 'compare' => 'NOT EXISTS'],
    ['key' => 'event_status', 'value' => ['closed', 'cancelled'], 'compare' => 'NOT IN'],
  ];
  $query->set('meta_query', $meta_query);
}, 19);

/* -------------------------------------------------------------------------
 * Админка: защита от кривого заполнения.
 * ---------------------------------------------------------------------- */

// В поле «Сектор, входящий в тур» — секторы этой записи.
add_filter('acf/load_field/key=field_event_tour_sector', static function ($field) {
  $field['choices'] = ['' => '— не выбран —'];
  $post_id = isset($_GET['post']) ? (int) $_GET['post'] : (int) get_the_ID();
  if ($post_id && function_exists('get_field')) {
    $rows = get_field('field_event_sectors', $post_id);
    if (is_array($rows)) {
      foreach ($rows as $row) {
        $name = trim((string) ($row['sector_name'] ?? ''));
        if ($name !== '') {
          $field['choices'][$name] = $name;
        }
      }
    }
  }

  return $field;
});

// Ссылка на тур — не поиск авиабилетов.
add_filter('acf/validate_value/key=field_event_date_tour_url', 'bsi_event_validate_tour_url', 10, 2);
add_filter('acf/validate_value/key=field_event_booking_url', 'bsi_event_validate_tour_url', 10, 2);
add_filter('acf/validate_value/key=field_event_booking_urls_more_url', 'bsi_event_validate_tour_url', 10, 2);
function bsi_event_validate_tour_url($valid, $value)
{
  if ($valid !== true || !is_string($value) || $value === '') {
    return $valid;
  }
  $path = (string) wp_parse_url($value, PHP_URL_PATH);
  if (preg_match('~/tickets/?$~i', $path)) {
    return 'Это ссылка на поиск авиабилетов в Самотуре, а не на концерт. Для билетов на событие ссылка не нужна — заявка приходит с сайта. Сюда вставляется ссылка на тур (search_crosstour).';
  }

  return $valid;
}

// Памятка и предупреждение о дублях на экране редактирования события.
add_action('admin_notices', static function () {
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'event') {
    return;
  }
  echo '<div class="notice notice-info"><p><strong>Одно событие = одна запись.</strong> '
    . 'Все даты, секторы с ценами и туры добавляйте строками ниже. Не создавайте отдельные записи «Билеты на…» и «Тур на…» под каждую дату.</p></div>';

  $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
  $title = $post_id ? get_the_title($post_id) : '';
  if ($title !== '' && (preg_match('/^\s*(Билеты|Тур)\s+на\s/iu', $title) || preg_match('/\d{1,2}\.\d{2}\.\d{2,4}/u', $title))) {
    echo '<div class="notice notice-warning"><p>В заголовке есть дата или «Билеты на / Тур на». '
      . 'Похоже на дубль: назовите запись по событию («Stray Kids в Сингапуре»), а даты добавьте в «Даты проведения».</p></div>';
  }
});

/**
 * Старые записи: «Секторы и цены» пустые, а легенда схемы заполнена → подставляем секторы
 * из легенды (одна строка на сектор, разные цены по датам — исключения). На сайте —
 * чтобы старые события показывали билеты без переноса; в админке сохранится,
 * когда менеджер нажмёт «Обновить».
 */
add_filter('acf/load_value/key=field_event_sectors', static function ($value, $post_id) {
  if (!empty($value) || !is_numeric($post_id) || !function_exists('get_field')) {
    return $value;
  }
  $legend = get_field('venue_scheme_legend', (int) $post_id, false);
  if (empty($legend) || !is_array($legend)) {
    return $value;
  }
  // Сырые значения: ключи — ключи полей легенды.
  $by_name = [];
  foreach ($legend as $row) {
    $name = trim(wp_strip_all_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string) ($row['field_event_legend_label'] ?? ''))));
    $price = $row['field_event_legend_price'] ?? '';
    if ($name === '' || $price === '' || !is_numeric($price)) {
      continue;
    }
    $date_raw = (string) ($row['field_event_legend_date'] ?? '');
    $by_name[$name]['currency'] = (string) ($row['field_event_legend_currency'] ?? 'RUB') ?: 'RUB';
    $by_name[$name]['prices'][] = ['date' => $date_raw, 'price' => (float) $price];
  }
  if (empty($by_name)) {
    return $value;
  }
  $out = [];
  foreach ($by_name as $name => $info) {
    $base = min(array_column($info['prices'], 'price'));
    $overrides = [];
    foreach ($info['prices'] as $p) {
      if ($p['date'] !== '' && $p['price'] !== $base) {
        $overrides[] = ['field_event_sdp_date' => $p['date'], 'field_event_sdp_price' => $p['price']];
      }
    }
    $out[] = [
      'field_event_sector_name' => $name,
      'field_event_sector_color' => '',
      'field_event_sector_price' => $base,
      'field_event_sector_currency' => $info['currency'],
      'field_event_sector_pass_price' => '',
      'field_event_sector_tour_extra' => '',
      'field_event_sector_date_prices' => $overrides,
    ];
  }

  return $out;
}, 10, 2);
