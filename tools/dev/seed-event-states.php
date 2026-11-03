<?php
// Тестовые события под состояния макета (только локальная база, проверка home_url). Запуск: wp eval-file wp-content/themes/bsi/tools/dev/seed-event-states.php. Повторный запуск обновляет те же записи (по slug).
if (strpos(home_url(), 'localhost') === false) { WP_CLI::error('Только локально'); }

$C = ['sg' => 2309, 'cn' => 2292, 'my' => 2308, 'th' => 2290, 'tr' => 4792];
$IMG = ['stray' => 5995, 'arena' => 5850, 'hk' => 5359, 'scheme' => 5994, 'thumb' => 5019];
$TOUR_URL = 'https://online.bsigroup.ru/search_crosstour?TOWNFROMINC=1&STATEINC=81&TOURINC=1100&CHECKIN_BEG=20270305&NIGHTS_FROM=2&CHECKIN_END=20270305&NIGHTS_TILL=2&ADULT=2&CURRENCY=1&CHILD=0&TOWNS_ANY=1&STARS_ANY=1&HOTELS_ANY=1&MEALS_ANY=1&ROOMS_ANY=1&FREIGHT=1&PRICEPAGE=1&DOLOAD=1';

$sport = term_exists('Спорт', 'event_tour_type') ?: wp_insert_term('Спорт', 'event_tour_type', ['slug' => 'sport']);
$T = ['concert' => 334, 'sport' => (int) (is_array($sport) ? $sport['term_id'] : $sport)];

// Общий FAQ: дописываем вопросы по типам, существующие (с faq_id) не трогаем
$common = get_field('event_common_faq', 'bsi_event_settings') ?: [];
$have = array_column($common, 'question');
$add = [
  ['faq_id' => 'agelimit01', 'question' => 'Со скольки лет можно на концерт?', 'answer' => 'Ограничение задаёт организатор, оно указано в описании события. Детям до 16 лет — со взрослым.', 'types' => [$T['concert']]],
  ['faq_id' => 'sportdoc01', 'question' => 'Какие документы нужны на стадион?', 'answer' => 'Билет на телефоне и паспорт. На матчи турнира пускают только по именным билетам.', 'types' => [$T['sport']]],
  ['faq_id' => 'visa000001', 'question' => 'Нужна ли виза?', 'answer' => 'Зависит от страны. Условия въезда — на странице страны, менеджер подскажет.', 'types' => []],
];
foreach ($add as $row) { if (!in_array($row['question'], $have, true)) { $common[] = $row; } }
update_field('field_event_common_faq', $common, 'bsi_event_settings');

$sectors_std = [
  ['sector_name' => 'VIP Soundcheck', 'sector_description' => 'Танцпол у сцены, саундчек и мерч', 'sector_price' => 650, 'sector_currency' => 'USD', 'sector_color' => '#1f1f1f'],
  ['sector_name' => 'CAT 1', 'sector_description' => 'Нижний ярус сбоку от сцены', 'sector_price' => 420, 'sector_currency' => 'USD', 'sector_color' => '#f28ba0'],
  ['sector_name' => 'CAT 2', 'sector_description' => 'Средний ярус напротив сцены', 'sector_price' => 280, 'sector_currency' => 'USD', 'sector_color' => '#7d86a8'],
  ['sector_name' => 'CAT 3', 'sector_description' => 'Верхний ярус, дальние места', 'sector_price' => 190, 'sector_currency' => 'USD', 'sector_color' => '#c9cede'],
];
$included_std = [
  ['text' => 'Проживание в выбранном отеле, завтраки', 'note' => 'Цена указана за человека при размещении вдвоём'],
  ['text' => 'Групповые трансферы аэропорт — отель — аэропорт', 'note' => 'С англоговорящим водителем'],
  ['text' => 'Групповой трансфер отель — стадион — отель в день концерта', 'note' => ''],
  ['text' => 'Билет на концерт', 'note' => 'CAT 2 — средний ярус напротив сцены'],
];
$extra_std = [
  ['text' => 'Медицинская страховка', 'note' => '', 'price_mode' => 'from', 'price' => 520],
  ['text' => 'Личные расходы и чаевые', 'note' => '', 'price_mode' => 'none', 'price' => ''],
  ['text' => 'Туристический налог', 'note' => 'Оплачивается в отеле при заселении', 'price_mode' => 'none', 'price' => ''],
  ['text' => 'Доплата за другой билет', 'note' => 'Цены по секторам — в блоке «Билет в туре»', 'price_mode' => 'request', 'price' => ''],
];
$hotels_std = [
  ['accommodation_package' => 'econom', 'accommodation_hotel_name' => 'Hotel Boss', 'accommodation_stars' => 3, 'accommodation_description' => '10 минут до метро, завтраки'],
  ['accommodation_package' => 'standard', 'accommodation_hotel_name' => 'Hotel G Singapore', 'accommodation_stars' => 4, 'accommodation_description' => 'Центр, завтраки'],
  ['accommodation_package' => 'five', 'accommodation_hotel_name' => 'Pan Pacific Singapore', 'accommodation_stars' => 5, 'accommodation_description' => 'Марина-Бэй, 15 минут до стадиона'],
];
$about = '<p>%s Билеты в рублях по курсу ЦБ, туры с отелем и трансферами — в одном месте.</p>';

$events = [
  // 1. Тур с билетом, несколько дат — существующая тестовая запись
  'test-stray-kids-singapur' => [
    'title' => 'Концерт Stray Kids в Сингапуре 6 и 7 марта 2027', 'country' => $C['sg'], 'type' => 'concert', 'cover' => $IMG['stray'],
    'subtitle' => 'Билеты и тур на концерт 6 и 7 марта',
    'venue' => 'National Stadium', 'city' => 'Сингапур',
    'dates' => [['2027-03-06', '', '20:00'], ['2027-03-07', '', '19:00']],
    'sectors' => $sectors_std, 'tour_sector' => 'CAT 2', 'tour_url' => $TOUR_URL, 'scheme' => $IMG['scheme'],
    'hotels' => $hotels_std, 'included' => $included_std, 'extra' => $extra_std,
    'about' => sprintf($about, 'Stray Kids (Стрэй Кидс) возвращаются в Сингапур с новым мировым туром: два вечера на National Stadium, 55 000 зрителей.'),
    'important' => '<ul><li><strong>Билеты на концерт именные.</strong></li><li>Детям до 16 лет — только со взрослым.</li></ul>',
    'faq' => [
      ['faq_replaces' => '76348e4ec0', 'faq_question' => 'Сколько стоит билет на концерт Stray Kids в рублях?', 'faq_answer' => 'От 17 000 ₽ за CAT 3. Цены всех секторов — в блоке «Билеты», пересчёт по курсу ЦБ на сегодня.'],
      ['faq_replaces' => '', 'faq_question' => 'Можно ли пронести лайтстик?', 'faq_answer' => 'Да, официальные лайтстики разрешены.'],
    ],
  ],
  // 2. Только билет
  'test-bruno-mars-gonkong-2027' => [
    'title' => 'Концерт Bruno Mars в Гонконге 7 и 8 мая 2027', 'country' => $C['cn'], 'type' => 'concert', 'cover' => $IMG['hk'],
    'subtitle' => 'Купить билеты на концерт 7 и 8 мая',
    'venue' => 'Kai Tak Stadium', 'city' => 'Гонконг',
    'dates' => [['2027-05-07', '', '20:00'], ['2027-05-08', '', '20:00']],
    'sectors' => $sectors_std, 'scheme' => $IMG['scheme'],
    'about' => sprintf($about, 'Bruno Mars (Бруно Марс) — два шоу на новом Kai Tak Stadium.'),
  ],
  // 3. Только тур (билеты отдельно не продаём)
  'test-limp-bizkit-kuala-lumpur' => [
    'title' => 'Концерт Limp Bizkit в Куала-Лумпуре 9 декабря 2026', 'country' => $C['my'], 'type' => 'concert', 'cover' => $IMG['arena'],
    'subtitle' => 'Тур на концерт: отель, трансферы и билет',
    'venue' => 'Axiata Arena', 'city' => 'Куала-Лумпур',
    'dates' => [['2026-12-09', '', '20:30']],
    'sectors' => [['sector_name' => 'Танцпол', 'sector_description' => 'Входит в тур', 'sector_price' => 150, 'sector_currency' => 'USD', 'sector_color' => '#7d86a8']],
    'tour_sector' => 'Танцпол', 'no_tickets' => 1, 'tour_url' => $TOUR_URL,
    'hotels' => [$hotels_std[0], $hotels_std[1]], 'included' => $included_std, 'extra' => array_slice($extra_std, 0, 3),
    'about' => sprintf($about, 'Limp Bizkit (Лимп Бизкит) впервые в Малайзии.'),
  ],
  // 4. Одна дата, тур + билет
  'test-weeknd-bangkok' => [
    'title' => 'Концерт The Weeknd в Бангкоке 24 октября 2026', 'country' => $C['th'], 'type' => 'concert', 'cover' => $IMG['arena'],
    'subtitle' => 'Билеты и тур на концерт',
    'venue' => 'Rajamangala Stadium', 'city' => 'Бангкок',
    'dates' => [['2026-10-24', '', '20:00']],
    'sectors' => array_slice($sectors_std, 1), 'tour_sector' => 'CAT 2', 'tour_url' => $TOUR_URL, 'scheme' => $IMG['scheme'],
    'hotels' => $hotels_std, 'included' => $included_std, 'extra' => $extra_std,
    'about' => sprintf($about, 'The Weeknd (Уикенд) — After Hours til Dawn в Бангкоке.'),
  ],
  // 5. Минимально заполненное: только обязательное
  'test-joji-gonkong' => [
    'title' => 'Концерт Joji в Гонконге 29 ноября 2026', 'country' => $C['cn'], 'type' => 'concert', 'cover' => $IMG['hk'],
    'venue' => 'AsiaWorld-Arena', 'city' => 'Гонконг',
    'dates' => [['2026-11-29', '', '20:00']],
    'sectors' => [
      ['sector_name' => 'CAT 1', 'sector_price' => 210, 'sector_currency' => 'USD'],
      ['sector_name' => 'CAT 2', 'sector_price' => 140, 'sector_currency' => 'USD'],
    ],
  ],
  // 6. Событие прошло — существующая тестовая запись
  'test-bruno-mars-proshlo' => [
    'title' => 'Концерт Bruno Mars в Гонконге 12 мая 2026', 'country' => $C['cn'], 'type' => 'concert', 'cover' => $IMG['hk'],
    'venue' => 'Kai Tak Stadium', 'city' => 'Гонконг',
    'dates' => [['2026-05-12', '', '20:00']],
    'sectors' => array_slice($sectors_std, 1), 'scheme' => $IMG['scheme'],
    'about' => sprintf($about, 'Bruno Mars (Бруно Марс) в Гонконге.'),
  ],
  // 7. Событие отменено
  'test-maroon5-bangkok' => [
    'title' => 'Концерт Maroon 5 в Бангкоке 9 февраля 2027', 'country' => $C['th'], 'type' => 'concert', 'cover' => $IMG['arena'],
    'venue' => 'Impact Arena', 'city' => 'Бангкок',
    'dates' => [['2027-02-09', '', '20:00']],
    'sectors' => array_slice($sectors_std, 1), 'status' => 'cancelled',
    'about' => sprintf($about, 'Maroon 5 (Марун 5) в Бангкоке.'),
  ],
  // 8. Турнир, несколько дней, только билеты (матчи — этап 2)
  'test-superkubok-ispanii-2027' => [
    'title' => 'Суперкубок Испании 2027', 'country' => $C['tr'], 'type' => 'sport', 'cover' => $IMG['arena'],
    'subtitle' => 'Два полуфинала и финал в Стамбуле',
    'venue' => 'Стадионы Стамбула', 'city' => 'Стамбул',
    'dates' => [['2027-02-02', '', '21:00'], ['2027-02-03', '', '21:00'], ['2027-02-06', '', '21:00']],
    'sectors' => [
      ['sector_name' => 'Категория 1', 'sector_description' => 'Центральная трибуна', 'sector_price' => 900, 'sector_currency' => 'EUR', 'sector_color' => '#f28ba0'],
      ['sector_name' => 'Категория 2', 'sector_description' => 'Боковая трибуна', 'sector_price' => 600, 'sector_currency' => 'EUR', 'sector_color' => '#7d86a8'],
      ['sector_name' => 'Категория 3', 'sector_description' => 'За воротами', 'sector_price' => 450, 'sector_currency' => 'EUR', 'sector_color' => '#c9cede', 'sector_pass_price' => 1200],
    ],
    'scheme' => $IMG['scheme'],
    'about' => sprintf($about, 'Суперкубок Испании 2027: «Реал», «Барселона», «Атлетико» и «Атлетик» в Стамбуле.'),
    'faq_mode' => 'merge', 'faq_hide' => ['visa000001'],
  ],
];

$out = [];
foreach ($events as $slug => $e) {
  $existing = get_page_by_path($slug, OBJECT, 'event');
  $id = $existing ? $existing->ID : wp_insert_post(['post_type' => 'event', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $e['title']]);
  update_post_meta($id, '_bsi_manual_slug', 1);
  wp_update_post(['ID' => $id, 'post_title' => $e['title'], 'post_name' => $slug]);
  update_post_meta($id, '_bsi_test_seed', 1);
  if (!has_post_thumbnail($id)) set_post_thumbnail($id, $IMG['thumb']);
  wp_set_object_terms($id, [$T[$e['type']]], 'event_tour_type');

  update_field('field_event_country', $e['country'], $id);
  // Обложку, схему и текст с прода (подгружены отдельно) повторный запуск не перетирает.
  if (!get_post_meta($id, 'event_hero_cover', true)) update_field('field_event_hero_cover', $e['cover'], $id);
  update_field('field_event_hero_subtitle', $e['subtitle'] ?? '', $id);
  update_field('field_event_venue', $e['venue'], $id);
  $resort = term_exists($e['city'], 'resort') ?: wp_insert_term($e['city'], 'resort');
  wp_set_object_terms($id, [(int) (is_array($resort) ? $resort['term_id'] : $resort)], 'resort');
  update_field('field_event_dates', array_map(fn($d) => ['date_value' => $d[0], 'date_value_end' => $d[1], 'date_time' => $d[2]], $e['dates']), $id);
  update_field('field_event_sectors', $e['sectors'], $id);
  update_field('field_event_tour_sector', $e['tour_sector'] ?? '', $id);
  update_field('field_event_no_separate_tickets', $e['no_tickets'] ?? 0, $id);
  update_field('field_event_booking_url', $e['tour_url'] ?? '', $id);
  if (!get_post_meta($id, 'venue_scheme', true)) update_field('field_event_venue_scheme', $e['scheme'] ?? '', $id);
  update_field('field_event_accommodation', $e['hotels'] ?? [], $id);
  update_field('field_event_included_items', $e['included'] ?? [], $id);
  update_field('field_event_extra_items', $e['extra'] ?? [], $id);
  if (!get_post_meta($id, 'event_about', true)) update_field('field_event_about', $e['about'] ?? '', $id);
  update_field('field_event_important', $e['important'] ?? '', $id);
  update_field('field_event_faq_mode', $e['faq_mode'] ?? 'merge', $id);
  update_field('field_event_faq_hide', $e['faq_hide'] ?? [], $id);
  update_field('field_event_faq', $e['faq'] ?? [], $id);
  update_field('field_event_status', $e['status'] ?? 'auto', $id);
  update_field('field_event_data_source', 'auto', $id);
  do_action('acf/save_post', $id); // пересчёт bsi_event_last_date

  $out[] = [$id, $e['title'], get_permalink($id)];
}
foreach ($out as $o) { WP_CLI::line(implode(' | ', $o)); }
