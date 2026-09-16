<?php

/**
 * Единый экспорт со старого сайта (Bitrix, past.bsigroup.ru) в JSON:
 * экскурсии (t_excursions) и достопримечательности (t_sights) одним файлом.
 *
 * Заменяет пару tools/legacy-excursions/export.php + tools/legacy-sights/export.php:
 * конфиг стран общий (countries.php), карта городов одна, регионы не размножаются.
 *
 * Запускается ТОЛЬКО локально — нужен доступ к MySQL старого сайта
 * (контейнер oldbsi_mysql, порт 13306 проброшен на хост).
 *
 *   php export.php --country=che [--out=data/che.json]
 *   php export.php --all
 *
 * Результат — data/{code}.json, заливается через import.php (локально)
 * или «Настройки сайта → Импорт со старого сайта» (прод, там только FTP).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

/* ID полей EAV старого сайта (t_additional_fields) */
const BSI_ADF_EXCURSION_TEXT = 62;
const BSI_ADF_EXCURSION_PREVIEW = 80;
const BSI_ADF_SIGHT_TEXT = 66;
const BSI_ADF_SIGHT_PREVIEW = 79;

$options = getopt('', ['country:', 'out:', 'all', 'host:', 'port:', 'user:', 'pass:', 'db:', 'with-hidden', 'skip-empty']);

/* На старом сайте скрытыми лежат записи без описания (одни названия).
   Забираем их только по флагу — импорт заведёт их черновиками. */
$with_hidden = isset($options['with-hidden']);

/* Записи без описания в CPT не заводим — каталог они не наполняют. */
$skip_empty = isset($options['skip-empty']);

$db_host = (string) ($options['host'] ?? '127.0.0.1');
$db_port = (int) ($options['port'] ?? 13306);
$db_user = (string) ($options['user'] ?? 'root');
$db_pass = (string) ($options['pass'] ?? 'root');
$db_name = (string) ($options['db'] ?? 'bx_sitemanager');

$countries = require __DIR__ . '/countries.php';

/* ───────────────────────────────────────────────────────────────────
 * Чистка и разбор значений источника
 * ─────────────────────────────────────────────────────────────────── */

/**
 * Приведение строки к валидному UTF-8.
 *
 * Соединение открыто в utf8mb4, но часть значений в t_additional_fields_values
 * реально лежит в cp1251 — без этой правки json_encode падает и пишет пустой файл.
 */
function to_utf8(?string $value): string
{
  $value = (string) $value;
  if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
    return $value;
  }

  $converted = @iconv('CP1251', 'UTF-8//IGNORE', $value);

  return $converted !== false ? $converted : mb_convert_encoding($value, 'UTF-8', 'CP1251');
}

/**
 * Чистка HTML старого сайта: Word-мусор, битые картинки, инлайн-стили.
 *
 * Фото не переносим (72 ГБ ассетов остались на старом сайте), поэтому <img>
 * вырезаем — иначе на новой странице получаем 404-заглушки. Ссылки на
 * past.bsigroup.ru разворачиваем в текст: на новом сайте таких URL нет.
 */
function clean_legacy_html(?string $html): string
{
  $html = trim((string) $html);
  if ($html === '') {
    return '';
  }

  /* Word-разметка и служебные теги */
  $html = preg_replace('#<!--.*?-->#s', '', $html);
  $html = preg_replace('#<(o:p|v:\w+)[^>]*>.*?</\1>#is', '', $html);
  $html = preg_replace('#</?(o:p|v:\w+|font|span)[^>]*>#i', '', $html);

  /* Картинки старого сайта */
  $html = preg_replace('#<img[^>]*>#i', '', $html);

  /* Ссылки на старый сайт — оставляем только текст */
  $html = preg_replace('#<a[^>]*href=["\'][^"\']*(past\.)?bsigroup\.ru[^"\']*["\'][^>]*>(.*?)</a>#is', '$2', $html);

  /* Атрибуты: оставляем только href у ссылок */
  $html = preg_replace_callback('#<(\w+)([^>]*)>#', static function (array $m): string {
    $tag = strtolower($m[1]);
    if ($tag === 'a' && preg_match('#href=["\']([^"\']+)["\']#i', $m[2], $href)) {
      return '<a href="' . $href[1] . '">';
    }
    return '<' . $tag . '>';
  }, $html);

  /* Пустые обёртки и лишние переносы */
  $html = preg_replace('#<(p|div|span|b|strong|em|i)>\s*(&nbsp;|\s)*</\1>#i', '', $html);
  $html = preg_replace('#(<br\s*/?>\s*){3,}#i', '<br><br>', $html);
  $html = preg_replace('#\s{2,}#', ' ', $html);

  return trim($html);
}

/**
 * Короткое описание: превью старого сайта, иначе первый абзац текста.
 */
function build_excerpt(?string $preview, string $content): string
{
  $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $preview)));
  if ($text === '') {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($content)));
  }
  if ($text === '') {
    return '';
  }

  if (mb_strlen($text) <= 300) {
    return $text;
  }

  $cut = mb_substr($text, 0, 300);
  $last_space = mb_strrpos($cut, ' ');
  $cut = $last_space ? mb_substr($cut, 0, $last_space) : $cut;

  /* rtrim() режет байты: тире «—» многобайтовое, и обрезка ломала UTF-8. */
  return preg_replace('/[\s,.;:—-]+$/u', '', $cut) . '…';
}

/**
 * «полдня», «8 часов», «целый день» → число часов. Null если не распарсили.
 */
function parse_duration_hours(?string $raw): ?int
{
  $raw = trim(mb_strtolower((string) $raw));
  if ($raw === '') {
    return null;
  }

  if (preg_match('/(\d+)\s*-\s*(\d+)\s*час/u', $raw, $m)) {
    return (int) $m[2];
  }
  if (preg_match('/(\d+)\s*час/u', $raw, $m)) {
    return (int) $m[1];
  }
  if (str_contains($raw, 'полдня')) {
    return 4;
  }
  if (str_contains($raw, 'целый день') || $raw === 'день') {
    return 8;
  }

  return null;
}

/**
 * Название не экскурсии, а строки заявки («Депозит за отель», «Доплата жена 1082»).
 *
 * @param string[] $patterns регулярки из конфига страны
 */
function bsi_legacy_is_junk_title(string $title, array $patterns): bool
{
  foreach ($patterns as $pattern) {
    if (preg_match($pattern, $title) === 1) {
      return true;
    }
  }

  return false;
}

/**
 * Тип достопримечательности по названию.
 * В старой базе типа нет — определяем по ключевым словам, чтобы каталог
 * сразу фильтровался. Не распознали — тип не проставляем.
 */
function detect_sight_type(string $title): string
{
  $title = mb_strtolower($title);

  $rules = [
    'Замки и дворцы' => ['замок', 'дворец', 'крепость', 'форт', 'башня', 'поместье', 'руины'],
    'Музеи и галереи' => ['музей', 'галере', 'выставк', 'планетари'],
    'Храмы' => ['собор', 'аббатств', 'церков', 'часовн', 'монастыр'],
    'Природа' => ['парк', 'сад', 'озеро', 'ущель', 'пещер', 'гора', 'остров', 'ферма', 'тропа'],
    'Развлечения' => ['зоопарк', 'аквариум', 'колесо', 'стадион', 'центр', 'площадка', 'фестивал'],
    'Гастрономия' => ['висковарн', 'вискокурн', 'виски', 'паб', 'винн', 'пивовар'],
  ];

  foreach ($rules as $type => $keywords) {
    foreach ($keywords as $keyword) {
      if (str_contains($title, $keyword)) {
        return $type;
      }
    }
  }

  return '';
}

/**
 * Регион и курорт по городу записи.
 *
 * Приоритет: ручная карта `cities` → `subtype_regions` по исторической области
 * записи → историческая область самого города (t_cities.C_SUBTYPE) →
 * `default_region`. Не нашлось нигде — регион пустой, остаётся только курорт:
 * выдумывать названия регионов нельзя, справочник ведёт контент-команда.
 *
 * @return array{region: ?string, resort: string}
 */
function resolve_place(array $config, string $city, string $item_subtype, string $city_subtype): array
{
  if ($city !== '' && isset($config['cities'][$city])) {
    $mapped = $config['cities'][$city];
    if (is_array($mapped)) {
      return [
        'region' => $mapped['region'] ?? null,
        'resort' => array_key_exists('resort', $mapped) ? (string) $mapped['resort'] : $city,
      ];
    }

    return ['region' => (string) $mapped, 'resort' => $city];
  }

  $region = null;
  if ($item_subtype !== '' && isset($config['subtype_regions'][$item_subtype])) {
    $region = $config['subtype_regions'][$item_subtype];
  } elseif ($city_subtype !== '') {
    $region = $city_subtype;
  } elseif ($item_subtype !== '') {
    $region = $item_subtype;
  } else {
    $region = $config['default_region'];
  }

  return ['region' => $region, 'resort' => $city];
}

/* ───────────────────────────────────────────────────────────────────
 * Выборки
 * ─────────────────────────────────────────────────────────────────── */

/**
 * Экскурсии страны + цены (t_addservices, услуга типа «экскурсия»).
 *
 * @return array{items: array<int, array<string, mixed>>, skipped_junk: string[], skipped_empty: string[]}
 */
function export_excursions(mysqli $db, array $config, bool $with_hidden, bool $skip_empty): array
{
  $legacy_country_id = (int) $config['legacy_id'];
  $visible_where = $with_hidden ? '' : ' AND e.E_VISIBLE = 1';

  $sql = "
    SELECT
      e.E_ID                                        AS legacy_id,
      NULLIF(e.E_URLCODE, '')                       AS urlcode,
      el.E_NAME                                     AS title,
      e.E_LENGTH                                    AS length_raw,
      e.E_NOTES                                     AS notes,
      e.E_VISIBLE                                   AS visible,
      cl.C_NAME                                     AS city,
      LOWER(ct.C_ALPHACODE)                         AS city_code,
      city_subtype.ST_NAME                          AS city_subtype,
      text_field.ADFV_VALUE                         AS content,
      preview_field.ADFV_VALUE                      AS excerpt
    FROM t_excursions e
    INNER JOIN t_excursions_lang el ON el.E_ID = e.E_ID AND el.E_LID = 'ru'
    LEFT JOIN t_cities ct ON ct.C_ID = e.E_CITY
    LEFT JOIN t_cities_lang cl ON cl.C_ID = ct.C_ID AND cl.C_LID = 'ru'
    LEFT JOIN t_subtypes_lang city_subtype
      ON city_subtype.ST_ID = ct.C_SUBTYPE AND city_subtype.ST_LID = 'ru'
    LEFT JOIN t_additional_fields_values text_field
      ON text_field.ADFV_ADFID = " . BSI_ADF_EXCURSION_TEXT . " AND text_field.ADFV_LANG = 'ru' AND text_field.ADFV_ELEMENTID = e.E_ID
    LEFT JOIN t_additional_fields_values preview_field
      ON preview_field.ADFV_ADFID = " . BSI_ADF_EXCURSION_PREVIEW . " AND preview_field.ADFV_LANG = 'ru' AND preview_field.ADFV_ELEMENTID = e.E_ID
    WHERE e.E_COUNTRY = {$legacy_country_id}{$visible_where}
    ORDER BY cl.C_NAME, el.E_NAME
  ";

  $result = $db->query($sql);
  if (!$result) {
    exit("Ошибка запроса экскурсий: {$db->error}\n");
  }

  $items = [];
  $skipped_junk = [];
  $skipped_empty = [];

  while ($row = $result->fetch_assoc()) {
    $legacy_id = (int) $row['legacy_id'];

    $title = trim(to_utf8($row['title']));
    if ($title === '') {
      continue;
    }
    if (bsi_legacy_is_junk_title($title, (array) ($config['skip_title_patterns'] ?? []))) {
      $skipped_junk[] = $title;
      continue;
    }

    $city = trim(to_utf8($row['city']));
    if ($city !== '' && in_array($city, (array) ($config['ignore_cities'] ?? []), true)) {
      $city = '';
    }

    $place = resolve_place($config, $city, '', trim(to_utf8($row['city_subtype'])));

    $content = clean_legacy_html(to_utf8($row['content']));
    $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags(to_utf8($row['excerpt']))));

    if ($skip_empty && $content === '' && $excerpt === '') {
      $skipped_empty[] = $title;
      continue;
    }

    $items[$legacy_id] = [
      'legacy_id' => $legacy_id,
      /* Пустой slug — импорт сгенерирует его из заголовка. */
      'slug' => to_utf8($row['urlcode'] ?? ''),
      'title' => $title,
      'content' => $content,
      'excerpt' => $excerpt,
      'duration_hours' => parse_duration_hours($row['length_raw']),
      'duration_raw' => trim(to_utf8($row['length_raw'])),
      'notes' => trim(to_utf8($row['notes'])),
      'city' => $city !== '' ? $place['resort'] : '',
      'city_code' => trim((string) $row['city_code']),
      'region' => $city !== '' ? $place['region'] : null,
      /* Скрытые на старом сайте — черновики: у них нет ни описания, ни слага. */
      'status' => (int) $row['visible'] === 1 ? 'publish' : 'draft',
      'tickets' => [],
    ];
  }

  if ($items) {
    attach_excursion_prices($db, $items);
  }

  return [
    'items' => array_values($items),
    'skipped_junk' => $skipped_junk,
    'skipped_empty' => $skipped_empty,
  ];
}

/**
 * Цены экскурсий: t_addservices + t_tours_costs (услуга типа «экскурсия», S_SVKEY = 4).
 *
 * @param array<int, array<string, mixed>> $items ключ — legacy_id, изменяется по ссылке
 */
function attach_excursion_prices(mysqli $db, array &$items): void
{
  $ids_in = implode(',', array_map('intval', array_keys($items)));

  $sql = "
    SELECT DISTINCT
      s.S_EXCURSION      AS legacy_id,
      sl.S_DESCRIPTION1  AS description,
      sl.S_DESCRIPTION2  AS variant,
      sl.S_NAME          AS raw_name,
      tc.CS_COST         AS amount,
      tc.CS_RATE         AS currency,
      s.S_NMEN_MIN       AS men_min,
      s.S_NMEN_MAX       AS men_max
    FROM t_addservices s
    INNER JOIN t_tours_costs tc
      ON s.S_SVKEY = tc.CS_SVKEY
      AND s.S_CODE = tc.CS_CODE
      AND s.S_SUBCODE1 = tc.CS_SUBCODE1
      AND s.S_SUBCODE2 = tc.CS_SUBCODE2
      AND s.S_PARTNERKEY = tc.CS_PRKEY
      AND s.S_PKKEY = tc.CS_PKKEY
    LEFT JOIN t_addservices_lang sl ON sl.S_ID = s.S_ID AND sl.S_LID = 'ru'
    WHERE s.S_SVKEY = 4 AND s.S_EXCURSION IN ({$ids_in})
    ORDER BY s.S_EXCURSION, tc.CS_COST
  ";

  $result = $db->query($sql);
  if (!$result) {
    exit("Ошибка запроса цен: {$db->error}\n");
  }

  $seen = [];

  while ($row = $result->fetch_assoc()) {
    $legacy_id = (int) $row['legacy_id'];
    if (!isset($items[$legacy_id])) {
      continue;
    }

    $amount = (float) $row['amount'];
    if ($amount <= 0) {
      continue;
    }

    $currency = strtoupper(trim((string) $row['currency'])) ?: 'RUB';

    $name = trim(to_utf8($row['variant']));
    if ($name === '') {
      $name = trim(to_utf8($row['description']));
    }
    if ($name === '') {
      $name = 'Стоимость';
    }

    $men_min = (int) $row['men_min'];
    $men_max = (int) $row['men_max'];
    $description = '';
    if ($men_min > 0 && $men_max > 0 && $men_max < 99) {
      $description = "от {$men_min} до {$men_max} чел.";
    }

    $key = $legacy_id . '|' . $name . '|' . $amount . '|' . $currency;
    if (isset($seen[$key])) {
      continue;
    }
    $seen[$key] = true;

    $items[$legacy_id]['tickets'][] = [
      'name' => $name,
      'description' => $description,
      'amount' => $amount,
      'currency' => $currency,
    ];
  }
}

/**
 * Достопримечательности страны.
 *
 * @return array{items: array<int, array<string, mixed>>, skipped_empty: string[]}
 */
function export_sights(mysqli $db, array $config, bool $with_hidden, bool $skip_empty): array
{
  $legacy_country_id = (int) $config['legacy_id'];
  $visible_clause = $with_hidden ? '' : ' AND s.S_VISIBLE = 1';

  $sql = "
    SELECT
      s.S_ID                    AS legacy_id,
      NULLIF(s.S_URLCODE, '')   AS urlcode,
      sl.S_NAME                 AS title,
      s.S_VISIBLE               AS visible,
      cl.C_NAME                 AS city,
      stl.ST_NAME               AS subtype,
      city_subtype.ST_NAME      AS city_subtype,
      text_field.ADFV_VALUE     AS content,
      preview_field.ADFV_VALUE  AS preview
    FROM t_sights s
    INNER JOIN t_sights_lang sl ON sl.S_ID = s.S_ID AND sl.S_LID = 'ru'
    LEFT JOIN t_cities ct ON ct.C_ID = s.S_CITY
    LEFT JOIN t_cities_lang cl ON cl.C_ID = ct.C_ID AND cl.C_LID = 'ru'
    LEFT JOIN t_subtypes_lang stl ON stl.ST_ID = s.S_SUBTYPE AND stl.ST_LID = 'ru'
    LEFT JOIN t_subtypes_lang city_subtype
      ON city_subtype.ST_ID = ct.C_SUBTYPE AND city_subtype.ST_LID = 'ru'
    LEFT JOIN t_additional_fields_values text_field
      ON text_field.ADFV_ADFID = " . BSI_ADF_SIGHT_TEXT . " AND text_field.ADFV_LANG = 'ru' AND text_field.ADFV_ELEMENTID = s.S_ID
    LEFT JOIN t_additional_fields_values preview_field
      ON preview_field.ADFV_ADFID = " . BSI_ADF_SIGHT_PREVIEW . " AND preview_field.ADFV_LANG = 'ru' AND preview_field.ADFV_ELEMENTID = s.S_ID
    WHERE s.S_COUNTRY = {$legacy_country_id}{$visible_clause}
    ORDER BY cl.C_NAME, sl.S_NAME
  ";

  $result = $db->query($sql);
  if (!$result) {
    exit("Ошибка запроса достопримечательностей: {$db->error}\n");
  }

  $items = [];
  $skipped_empty = [];

  while ($row = $result->fetch_assoc()) {
    $title = trim(to_utf8($row['title']));
    if ($title === '') {
      continue;
    }

    $city = trim(to_utf8($row['city']));
    if ($city !== '' && in_array($city, (array) ($config['ignore_cities'] ?? []), true)) {
      $city = '';
    }

    $subtype = trim(to_utf8($row['subtype']));
    $content = clean_legacy_html(to_utf8($row['content']));
    $excerpt = build_excerpt(to_utf8($row['preview']), $content);

    if ($skip_empty && $content === '' && $excerpt === '') {
      $skipped_empty[] = $title;
      continue;
    }

    $place = resolve_place($config, $city, $subtype, trim(to_utf8($row['city_subtype'])));

    $items[] = [
      'legacy_id' => (int) $row['legacy_id'],
      'slug' => to_utf8($row['urlcode'] ?? ''),
      'title' => $title,
      'content' => $content,
      'excerpt' => $excerpt,
      'city' => $city !== '' ? $place['resort'] : '',
      'region' => $place['region'],
      'subtype' => $subtype,
      'sight_type' => detect_sight_type($title),
      'visible' => (string) $row['visible'] === '1',
    ];
  }

  return ['items' => $items, 'skipped_empty' => $skipped_empty];
}

/* ───────────────────────────────────────────────────────────────────
 * Прогон
 * ─────────────────────────────────────────────────────────────────── */

$db = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($db->connect_errno) {
  exit("Не подключиться к базе старого сайта: {$db->connect_error}\n");
}
$db->set_charset('utf8mb4');

if (isset($options['all'])) {
  $codes = array_keys($countries);
} else {
  $code = strtolower((string) ($options['country'] ?? ''));
  if ($code === '' || !isset($countries[$code])) {
    exit("Укажи --country=<код> из countries.php или --all\n");
  }
  $codes = [$code];
}

foreach ($codes as $country_code) {
  $config = $countries[$country_code];

  $out_path = count($codes) === 1
    ? (string) ($options['out'] ?? __DIR__ . '/data/' . $country_code . '.json')
    : __DIR__ . '/data/' . $country_code . '.json';

  $excursions = export_excursions($db, $config, $with_hidden, $skip_empty);
  $sights = export_sights($db, $config, $with_hidden, $skip_empty);

  if (!$excursions['items'] && !$sights['items']) {
    printf("%s: пусто, файл не пишем\n", $country_code);
    continue;
  }

  $payload = [
    'generated_at' => date('c'),
    'source' => 'past.bsigroup.ru (bitrix): t_excursions + t_sights',
    'country_code' => $country_code,
    'country_slug' => $config['country_slug'],
    'legacy_country_id' => (int) $config['legacy_id'],
    'excursions' => $excursions['items'],
    'sights' => $sights['items'],
  ];

  if (!is_dir(dirname($out_path))) {
    mkdir(dirname($out_path), 0775, true);
  }

  $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  if ($json === false) {
    exit("json_encode ({$country_code}): " . json_last_error_msg() . "\n");
  }

  file_put_contents($out_path, $json);

  $exc_no_region = count(array_filter($excursions['items'], static fn(array $i): bool => empty($i['region'])));
  $sight_no_region = count(array_filter($sights['items'], static fn(array $i): bool => empty($i['region'])));
  $with_prices = count(array_filter($excursions['items'], static fn(array $i): bool => !empty($i['tickets'])));

  printf(
    "%s → экскурсий %d (цены %d, без региона %d), достопримечательностей %d (без региона %d) → %s\n",
    $country_code,
    count($excursions['items']),
    $with_prices,
    $exc_no_region,
    count($sights['items']),
    $sight_no_region,
    basename($out_path)
  );

  if ($excursions['skipped_junk']) {
    printf("  отсеяно не-экскурсий: %d (%s)\n", count($excursions['skipped_junk']), implode('; ', array_slice($excursions['skipped_junk'], 0, 3)));
  }
  if ($excursions['skipped_empty'] || $sights['skipped_empty']) {
    printf("  пропущено без описания: %d\n", count($excursions['skipped_empty']) + count($sights['skipped_empty']));
  }
}
