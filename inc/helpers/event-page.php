<?php
/**
 * Страница события по макетам 2026-10-08 (wiki/docs/event-page-tz.md): все данные для шаблона одним массивом.
 *
 * Новые поля читаются первыми, старые — запасными, чтобы записи до переноса
 * (wp bsi event-migrate) выглядели прилично: курорт (resort) → date_city, date_time → event_time,
 * tour_included_items → tour_included (WYSIWYG), event_hero_date → event_dates.
 *
 *   $page = bsi_event_page_data(get_the_ID());
 */

if (!defined('ABSPATH')) {
  exit;
}

/** Пакеты проживания в порядке показа: ключ поля accommodation_package → подпись. */
function bsi_event_packages_labels(): array
{
  return [
    'econom' => ['title' => 'Эконом', 'stars' => '3–4'],
    'standard' => ['title' => 'Стандарт', 'stars' => '4–4+'],
    'five' => ['title' => 'Пакет 5★', 'stars' => '5'],
    'vip' => ['title' => 'VIP', 'stars' => '5'],
  ];
}

/**
 * Пункты списка из старого WYSIWYG: <li> → текст, «(…)» в конце → пояснение.
 *
 * @return array<int,array{text:string,note:string}>
 */
function bsi_event_items_from_html(string $html): array
{
  if (trim(wp_strip_all_tags($html)) === '') {
    return [];
  }
  preg_match_all('~<li[^>]*>(.*?)</li>~isu', $html, $m);
  $raw = !empty($m[1]) ? $m[1] : preg_split('~<br\s*/?>|</p>~iu', $html);
  $out = [];
  foreach ($raw as $piece) {
    $text = trim(html_entity_decode(wp_strip_all_tags((string) $piece), ENT_QUOTES, 'UTF-8'));
    if ($text === '') {
      continue;
    }
    $note = '';
    if (preg_match('~^(.*\S)\s*\(([^()]+)\)\s*$~u', $text, $mm)) {
      $text = $mm[1];
      $note = $mm[2];
    }
    $out[] = ['text' => $text, 'note' => $note];
  }

  return $out;
}

/**
 * Итоговый FAQ события: свои новые → общие по типу (замены на своих местах, без скрытых).
 * Режим «Только свои» — только свои вопросы.
 *
 * @return array<int,array{question:string,answer:string}>
 */
function bsi_event_faq_items(int $post_id): array
{
  if (!function_exists('get_field')) {
    return [];
  }
  $own = get_field('event_faq', $post_id);
  $own = is_array($own) ? $own : [];
  $mode = (string) get_field('event_faq_mode', $post_id) ?: 'merge';

  $new = [];
  $replace = [];
  foreach ($own as $row) {
    $q = trim((string) ($row['faq_question'] ?? ''));
    if ($q === '') {
      continue;
    }
    $item = ['question' => $q, 'answer' => (string) ($row['faq_answer'] ?? '')];
    $rid = trim((string) ($row['faq_replaces'] ?? ''));
    if ($rid !== '' && $mode === 'merge') {
      $replace[$rid] = $item;
    } else {
      $new[] = $item;
    }
  }
  if ($mode === 'own') {
    return $new;
  }

  $hidden = (array) get_field('event_faq_hide', $post_id);
  $types = wp_get_post_terms($post_id, BSI_EVENT_TOUR_TYPE_TAXONOMY, ['fields' => 'ids']);
  $types = is_wp_error($types) ? [] : array_map('intval', $types);

  $common = get_field('event_common_faq', 'bsi_event_settings');
  $out = $new;
  foreach (is_array($common) ? $common : [] as $row) {
    $id = trim((string) ($row['faq_id'] ?? ''));
    $q = trim((string) ($row['question'] ?? ''));
    if ($q === '' || in_array($id, $hidden, true)) {
      continue;
    }
    $for = array_map('intval', array_filter((array) ($row['types'] ?? [])));
    if (!empty($for) && !array_intersect($for, $types)) {
      continue;
    }
    $out[] = $replace[$id] ?? ['question' => $q, 'answer' => (string) ($row['answer'] ?? '')];
  }

  return $out;
}

/**
 * Телефоны раздела: настройки → старые поля события → по умолчанию.
 *
 * @return array<int,array{label:string,tel:string}>
 */
function bsi_event_phones(int $post_id): array
{
  $pairs = [
    ['event_phone_primary', 'event_widget_phone_primary', '8 (495) 785-55-35'],
    ['event_phone_secondary', 'event_widget_phone_secondary', '8 (800) 200-55-35 (из регионов)'],
  ];
  $out = [];
  foreach ($pairs as [$option, $legacy, $default]) {
    $label = function_exists('get_field') ? trim((string) get_field($option, 'bsi_event_settings')) : '';
    if ($label === '' && function_exists('get_field')) {
      $label = trim((string) get_field($legacy, $post_id));
    }
    if ($label === '') {
      $label = $default;
    }
    $out[] = ['label' => $label, 'tel' => preg_replace('/\D/u', '', preg_replace('/\(.*?[а-я].*?\)/iu', '', $label))];
  }

  return $out;
}

/**
 * Все данные страницы события.
 *
 * @return array<string,mixed>
 */
function bsi_event_page_data(int $post_id): array
{
  $f = static fn(string $key) => function_exists('get_field') ? get_field($key, $post_id) : null;

  $status = bsi_event_status($post_id);
  $finished = $status !== 'active';
  $offers = bsi_event_offers($post_id);

  // Даты: будущие — для выбора; все — для подписи прошедшего события.
  $all_rows = is_array($f('event_dates')) ? $f('event_dates') : [];
  $all_dates = [];
  foreach ($all_rows as $row) {
    $ts = !empty($row['date_value']) ? strtotime((string) $row['date_value']) : 0;
    if ($ts) {
      $end_ts = !empty($row['date_value_end']) ? strtotime((string) $row['date_value_end']) : 0;
      $all_dates[] = ['date' => gmdate('Y-m-d', $ts), 'date_end' => $end_ts && $end_ts !== $ts ? gmdate('Y-m-d', $end_ts) : ''];
    }
  }
  if (empty($all_dates) && ($hero = (string) $f('event_hero_date')) !== '') {
    $hero_end = (string) $f('event_hero_date_end');
    $all_dates[] = ['date' => gmdate('Y-m-d', strtotime($hero)), 'date_end' => $hero_end !== '' ? gmdate('Y-m-d', strtotime($hero_end)) : ''];
  }
  usort($all_dates, static fn($a, $b) => strcmp($a['date'], $b['date']));
  $dates = $offers['dates'];
  $dates_label = bsi_event_dates_label(!empty($dates) && !$finished ? $dates : $all_dates);

  $first_row = $all_rows[0] ?? [];
  $venue = trim((string) $f('event_venue')) ?: trim((string) ($first_row['date_venue'] ?? ''));
  $city = bsi_event_city($post_id) ?: trim((string) ($first_row['date_city'] ?? ''));
  // Разные площадки по датам — в hero «3 площадки», в сайдбаре площадка выбранной даты.
  $date_venues = array_values(array_unique(array_filter(array_column($dates, 'venue'))));
  $venue_count = count($date_venues);
  $venue_label = $venue_count > 1 ? $venue_count . ' ' . bsi_plural_ru($venue_count, 'площадка', 'площадки', 'площадок') : $venue;
  $time = $dates[0]['time'] ?? (trim((string) ($first_row['date_time'] ?? '')) ?: trim((string) $f('event_time')));

  // Обложка: своё поле → миниатюра → галерея.
  $cover = $f('event_hero_cover');
  $cover_url = is_array($cover) && !empty($cover['ID']) ? (string) wp_get_attachment_image_url((int) $cover['ID'], 'full') : '';
  if ($cover_url === '') {
    $cover_url = (string) get_the_post_thumbnail_url($post_id, 'full');
  }
  if ($cover_url === '' && is_array($gallery = $f('tour_gallery')) && !empty($gallery[0]['url'])) {
    $cover_url = (string) $gallery[0]['url'];
  }

  // Тур: ручная ссылка Само приоритетнее, иначе авто по slug.
  $tour_url = $finished ? '' : trim((string) $f('tour_booking_url'));
  $crosstour_ref = (!$finished && function_exists('bsi_crosstour_event_ref')) ? bsi_crosstour_event_ref($post_id) : null;
  $samo_url = ($crosstour_ref && function_exists('bsi_crosstour_booking_url')) ? bsi_crosstour_booking_url($crosstour_ref) : '';
  $booking_url = $tour_url !== '' ? $tour_url : $samo_url;

  $tour_price = null;
  if ($tour_url !== '' && stripos($tour_url, 'search_crosstour') !== false && function_exists('bsi_crosstour_ref_from_url')) {
    $ref = bsi_crosstour_ref_from_url($tour_url);
    $rub = null;
    if ($ref && function_exists('bsi_crosstour_tour_matrix')) {
      $rub = bsi_crosstour_tour_matrix($ref)['price_rub'] ?? null;
    }
    if ($ref && $rub === null && function_exists('bsi_crosstour_quick_price')) {
      $rub = bsi_crosstour_quick_price($ref);
    }
    $tour_price = $rub ? ['rub' => (int) $rub, 'original' => null, 'currency' => null] : null;
  }

  $has_tour = !$finished && $booking_url !== '' && !empty($dates);
  // Без секторов и без тура — билеты «по запросу»: событие ведут руками, без Само (на проде 2 таких на 14.10)
  $manual_tickets = $booking_url === '' && empty($offers['sectors']) && !(bool) $f('event_no_separate_tickets');
  $has_tickets = !$finished && (!empty($offers['tickets_enabled']) || $manual_tickets) && !empty($dates);
  $formats = array_values(array_filter([$has_tour ? 'tour' : '', $has_tickets ? 'ticket' : '']));
  $default_format = $has_tour ? 'tour' : 'ticket';

  // «Цена от» — только по тому, что продаётся: тур без отдельных билетов не показывает цену билета.
  $price_min = bsi_event_min_price([$has_tickets ? ($offers['ticket_min'] ?? null) : null, $has_tour ? $tour_price : null]);

  // Проживание по пакетам (порядок — как в Само).
  $labels = bsi_event_packages_labels();
  $packages = [];
  foreach ((array) $f('event_accommodation') as $row) {
    $name = trim((string) ($row['accommodation_hotel_name'] ?? ''));
    if ($name === '') {
      continue;
    }
    $key = (string) ($row['accommodation_package'] ?? '');
    $key = isset($labels[$key]) ? $key : 'other';
    $packages[$key]['hotels'][] = [
      'name' => $name,
      'stars' => max(0, min(5, (int) ($row['accommodation_stars'] ?? 0))) . (!empty($row['accommodation_stars_plus']) ? '+' : ''),
      'descr' => trim((string) ($row['accommodation_description'] ?? '')),
    ];
  }
  $packages_out = [];
  foreach (array_merge(array_keys($labels), ['other']) as $key) {
    if (!empty($packages[$key])) {
      $packages_out[] = [
        'key' => $key,
        'title' => $labels[$key]['title'] ?? 'Отели',
        'stars' => $labels[$key]['stars'] ?? '',
        'hotels' => $packages[$key]['hotels'],
      ];
    }
  }

  $included = [];
  foreach ((array) $f('tour_included_items') as $row) {
    if (trim((string) ($row['text'] ?? '')) !== '') {
      $included[] = ['text' => trim((string) $row['text']), 'note' => trim((string) ($row['note'] ?? ''))];
    }
  }
  if (empty($included)) {
    $included = bsi_event_items_from_html((string) $f('tour_included'));
  }
  $extra = [];
  foreach ((array) $f('tour_extra_items') as $row) {
    if (trim((string) ($row['text'] ?? '')) === '') {
      continue;
    }
    $mode = (string) ($row['price_mode'] ?? 'none');
    $amount = (int) ($row['price'] ?? 0);
    $price = '';
    if ($mode === 'request') {
      $price = 'по запросу';
    } elseif (in_array($mode, ['from', 'exact'], true) && $amount > 0) {
      $price = ($mode === 'from' ? 'от ' : '') . number_format($amount, 0, ',', ' ') . ' ₽';
    }
    $extra[] = ['text' => trim((string) $row['text']), 'note' => trim((string) ($row['note'] ?? '')), 'price' => $price];
  }
  if (empty($extra)) {
    foreach (bsi_event_items_from_html((string) $f('tour_not_included')) as $it) {
      $extra[] = $it + ['price' => ''];
    }
  }

  $scheme = $f('venue_scheme');
  $types = get_the_terms($post_id, BSI_EVENT_TOUR_TYPE_TAXONOMY);

  return [
    'id' => $post_id,
    'title' => get_the_title($post_id),
    'subtitle' => trim((string) $f('event_hero_subtitle')),
    'cover_url' => $cover_url,
    'status' => $status,
    'finished' => $finished,
    'offers' => $offers,
    'dates' => $dates,
    'all_dates' => $all_dates,
    'dates_label' => $dates_label,
    'time' => $time,
    'venue' => $venue,
    'city' => $city,
    'place' => implode(', ', array_filter([$venue_label, $city])),
    'date_place' => implode(', ', array_filter([$date_venues[0] ?? $venue, $city])),
    'tour_url' => $tour_url,
    'samo_url' => $samo_url,
    'booking_url' => $booking_url,
    'crosstour' => (bool) $crosstour_ref,
    'tour_price' => $tour_price,
    'has_tour' => $has_tour,
    'has_tickets' => $has_tickets,
    'formats' => $formats,
    'default_format' => $default_format,
    'tour_sector' => (string) ($offers['tour_sector'] ?? ''),
    'price_min' => $price_min,
    'packages' => $packages_out,
    'included' => $included,
    'extra' => $extra,
    'about' => (string) $f('event_about'),
    'important' => (string) $f('event_important'),
    // Старые блоки — выводятся, пока контент не перенесён в конструктор (TODO.md, «выкатка без потери индекса»)
    'additional' => (string) $f('event_additional'),
    'program' => bsi_event_program_days($post_id),
    'scheme_url' => is_array($scheme) && !empty($scheme['url']) ? (string) $scheme['url'] : '',
    'faq' => bsi_event_faq_items($post_id),
    'phones' => bsi_event_phones($post_id),
    'type_names' => is_array($types) ? wp_list_pluck($types, 'name') : [],
    'country_id' => (int) $f('tour_country'),
  ];
}

/**
 * Похожие события: та же страна, ближайшие, без прошедших (фильтр каталога),
 * у прошедшего события при пустой стране — ближайшие любые.
 *
 * @return WP_Post[]
 */
function bsi_event_related(int $post_id, int $country_id, bool $finished): array
{
  $args = [
    'post_type' => 'event',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'post__not_in' => [$post_id],
    'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows' => true,
  ];
  $posts = [];
  if ($country_id > 0 && function_exists('bsi_build_tour_country_meta_query')) {
    $meta = bsi_build_tour_country_meta_query($country_id);
    if (!empty($meta)) {
      $posts = get_posts(bsi_query_args_append_schedule($args + ['meta_query' => $meta]));
    }
  }
  if (empty($posts) && $finished) {
    $posts = get_posts(bsi_query_args_append_schedule($args));
  }

  return $posts;
}

/**
 * Данные для js/modules/event-offer.js: даты, секторы с ценами по датам, доплаты в туре.
 *
 * @return array<string,mixed>
 */
function bsi_event_page_json(array $page): array
{
  $offers = $page['offers'];
  $keys = array_column($page['dates'], 'key');
  if (!empty($offers['has_pass']) && $page['has_tickets']) {
    $keys[] = BSI_EVENT_PASS_KEY;
  }
  $dates = [];
  foreach ($keys as $key) {
    $od = null;
    foreach ($page['dates'] as $d) {
      if ($d['key'] === $key) {
        $od = $d;
        break;
      }
    }
    $sectors = [];
    foreach ($offers['sectors'] as $sec) {
      $sp = $sec['prices'][$key] ?? null;
      $extra = $key !== BSI_EVENT_PASS_KEY ? bsi_event_sector_tour_extra($sec, $offers['sectors'], $page['tour_sector'], $key) : null;
      $sectors[] = [
        'name' => $sec['name'],
        'label' => $sec['name'] . ($sp ? ' · ' . bsi_event_price_text($sp) : ''),
        'price' => $sp ? bsi_event_price_text($sp) : '',
        'tourExtra' => $extra ? '+' . number_format((int) $extra['rub'], 0, ',', ' ') . ' ₽' : '',
      ];
    }
    $dates[] = [
      'key' => $key,
      'label' => $od ? $od['label'] : 'Все дни',
      'full' => $od ? bsi_format_event_date_range($od['date'], $od['date_end']) : 'Абонемент на все дни',
      'place' => $od
        ? implode(' · ', array_filter([$od['time'] !== '' ? 'Начало в ' . $od['time'] : '', $od['venue']]))
        : 'все дни события',
      'venueLine' => $od ? implode(', ', array_filter([$od['venue'], $page['city']])) : $page['place'],
      'tourHref' => $od ? ($od['tour_url'] !== '' ? $od['tour_url'] : $page['booking_url']) : '',
      'sectors' => $sectors,
    ];
  }

  return [
    'defaultFormat' => $page['default_format'],
    'formats' => $page['formats'],
    'tourSector' => $page['tour_sector'],
    'dates' => $dates,
  ];
}

/**
 * Короткое название для сайдбара: «Концерт Stray Kids в Сингапуре 6 и 7 марта 2027» → «Stray Kids в Сингапуре».
 */
function bsi_event_short_title(string $title): string
{
  $short = preg_replace('/^Концерт\s+/u', '', $title);
  $short = preg_replace('/\s+\d{1,2}(?:[–-]\d{1,2})?(?:(?:,\s*|\s+и\s+)\d{1,2}(?:[–-]\d{1,2})?)*\s+[а-яё]+(?:\s+\d{4})?$/u', '', (string) $short);

  return trim((string) $short) !== '' ? trim((string) $short) : $title;
}

/**
 * Старая «Программа тура» по дням (tour_program): только дни с заголовком или текстом.
 */
function bsi_event_program_days(int $post_id): array
{
  $rows = function_exists('get_field') ? get_field('tour_program', $post_id) : [];
  if (!is_array($rows)) {
    return [];
  }

  $days = [];
  foreach ($rows as $row) {
    $title = trim((string) ($row['day_title'] ?? ''));
    $content = (string) ($row['day_content'] ?? '');
    if ($title === '' && trim(wp_strip_all_tags($content)) === '') {
      continue;
    }
    $days[] = ['title' => $title, 'content' => $content];
  }

  return $days;
}
