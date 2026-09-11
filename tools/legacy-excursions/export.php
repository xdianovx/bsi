<?php

/**
 * Экспорт экскурсий из старого сайта (Bitrix, past.bsigroup.ru) в JSON.
 *
 * Запускается ТОЛЬКО локально — нужен доступ к MySQL старого сайта
 * (контейнер oldbsi_mysql, порт 13306 проброшен на хост).
 *
 * Использование:
 *   php export.php --country=gbr [--out=data/gbr.json]
 *
 * Результат — JSON, который переносится на прод и заливается через import.php.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

$options = getopt('', ['country:', 'out:', 'host:', 'port:', 'user:', 'pass:', 'db:']);

$country_code = strtolower((string) ($options['country'] ?? 'gbr'));

$db_host = (string) ($options['host'] ?? '127.0.0.1');
$db_port = (int) ($options['port'] ?? 13306);
$db_user = (string) ($options['user'] ?? 'root');
$db_pass = (string) ($options['pass'] ?? 'root');
$db_name = (string) ($options['db'] ?? 'bx_sitemanager');

/* ───────────────────────────────────────────────────────────────────
 * Конфиг стран: связь старого кода страны с записью CPT country
 * и раскладка городов по регионам (в Bitrix C_REGION мусорный).
 * ─────────────────────────────────────────────────────────────────── */

$countries = [
  'gbr' => [
    'legacy_id' => 222,
    'country_slug' => 'velikobritaniya',
    'default_region' => null,
    'cities' => [
      'Лондон' => 'Лондон',
      'Эдинбург' => 'Шотландия',
      'Глазго' => 'Шотландия',
      'Кембридж' => 'Восточная Англия',
      'Белфаст' => 'Северная Ирландия',
      'Графство Антрим' => 'Северная Ирландия',
      'Лондондерри' => 'Северная Ирландия',
      'Эннискиллен' => 'Северная Ирландия',
      'Данганнон' => 'Северная Ирландия',
      'Сент Хелиер' => 'Нормандские острова',
      'Сент Питер Порт' => 'Нормандские острова',
      'Бат' => 'Юг Англии',
      'Стоунхедж' => 'Юг Англии',
      'Винчестер' => 'Юг Англии',
      'Котсволд' => 'Юг Англии',
      'Брайтон' => 'Юго-восточная Англия',
      'Кент' => 'Юго-восточная Англия',
      'Стратфорд-на-Эйвоне' => 'Западный Мидлендс',
      'Ворвик' => 'Западный Мидлендс',
    ],
  ],
];

if (!isset($countries[$country_code])) {
  exit("Нет конфига для страны '$country_code'. Добавь в \$countries.\n");
}

$config = $countries[$country_code];
$out_path = (string) ($options['out'] ?? __DIR__ . '/data/' . $country_code . '.json');

/* ───────────────────────────────────────────────────────────────────
 * Подключение. Таблицы в cp1251, но *_lang поля — utf8,
 * поэтому читаем соединением в utf8mb4 и правим только реально битые строки.
 * ─────────────────────────────────────────────────────────────────── */

$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($mysqli->connect_errno) {
  exit("Не подключиться к базе старого сайта: {$mysqli->connect_error}\n");
}
$mysqli->set_charset('utf8mb4');

$legacy_country_id = (int) $config['legacy_id'];

/**
 * Чистка HTML старого сайта: Word-мусор, битые картинки, инлайн-стили.
 *
 * Фото не переносим (файлы остаются на старом сайте), поэтому <img> вырезаем —
 * иначе на новой странице получаем 404-заглушки. Ссылки на past.bsigroup.ru
 * разворачиваем в текст: на новом сайте таких URL нет.
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

/* Экскурсии */

$sql = "
  SELECT
    e.E_ID                                        AS legacy_id,
    NULLIF(e.E_URLCODE, '')                       AS urlcode,
    el.E_NAME                                     AS title,
    e.E_LENGTH                                    AS length_raw,
    e.E_NOTES                                     AS notes,
    cl.C_NAME                                     AS city,
    LOWER(ct.C_ALPHACODE)                         AS city_code,
    text_field.ADFV_VALUE                         AS content,
    preview_field.ADFV_VALUE                      AS excerpt
  FROM t_excursions e
  INNER JOIN t_excursions_lang el ON el.E_ID = e.E_ID AND el.E_LID = 'ru'
  LEFT JOIN t_cities ct ON ct.C_ID = e.E_CITY
  LEFT JOIN t_cities_lang cl ON cl.C_ID = ct.C_ID AND cl.C_LID = 'ru'
  LEFT JOIN t_additional_fields_values text_field
    ON text_field.ADFV_ADFID = 62 AND text_field.ADFV_LANG = 'ru' AND text_field.ADFV_ELEMENTID = e.E_ID
  LEFT JOIN t_additional_fields_values preview_field
    ON preview_field.ADFV_ADFID = 80 AND preview_field.ADFV_LANG = 'ru' AND preview_field.ADFV_ELEMENTID = e.E_ID
  WHERE e.E_COUNTRY = {$legacy_country_id} AND e.E_VISIBLE = 1
  ORDER BY cl.C_NAME, el.E_NAME
";

$result = $mysqli->query($sql);
if (!$result) {
  exit("Ошибка запроса экскурсий: {$mysqli->error}\n");
}

$items = [];
$ids = [];

while ($row = $result->fetch_assoc()) {
  $legacy_id = (int) $row['legacy_id'];
  $ids[] = $legacy_id;

  $city = trim((string) $row['city']);

  $items[$legacy_id] = [
    'legacy_id' => $legacy_id,
    /* Пустой slug — импорт сгенерирует его из заголовка (транслит через cyr2lat). */
    'slug' => (string) ($row['urlcode'] ?? ''),
    'title' => trim((string) $row['title']),
    'content' => clean_legacy_html($row['content']),
    'excerpt' => trim(preg_replace('/\s+/u', ' ', strip_tags((string) $row['excerpt']))),
    'duration_hours' => parse_duration_hours($row['length_raw']),
    'duration_raw' => trim((string) $row['length_raw']),
    'notes' => trim((string) $row['notes']),
    'city' => $city,
    'city_code' => trim((string) $row['city_code']),
    'region' => $city !== '' ? ($config['cities'][$city] ?? $config['default_region']) : null,
    'tickets' => [],
  ];
}

/* Цены: t_addservices + t_tours_costs (услуга типа «экскурсия», S_SVKEY = 4) */

if ($ids) {
  $ids_in = implode(',', array_map('intval', $ids));

  $sql_prices = "
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

  $result_prices = $mysqli->query($sql_prices);
  if (!$result_prices) {
    exit("Ошибка запроса цен: {$mysqli->error}\n");
  }

  $seen = [];

  while ($row = $result_prices->fetch_assoc()) {
    $legacy_id = (int) $row['legacy_id'];
    if (!isset($items[$legacy_id])) {
      continue;
    }

    $amount = (float) $row['amount'];
    if ($amount <= 0) {
      continue;
    }

    $currency = strtoupper(trim((string) $row['currency'])) ?: 'RUB';

    $name = trim((string) $row['variant']);
    if ($name === '') {
      $name = trim((string) $row['description']);
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

/* Итог */

$payload = [
  'generated_at' => date('c'),
  'source' => 'past.bsigroup.ru (bitrix)',
  'country_code' => $country_code,
  'country_slug' => $config['country_slug'],
  'legacy_country_id' => $legacy_country_id,
  'items' => array_values($items),
];

if (!is_dir(dirname($out_path))) {
  mkdir(dirname($out_path), 0775, true);
}

file_put_contents(
  $out_path,
  json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
);

$with_prices = count(array_filter($payload['items'], static fn(array $i): bool => !empty($i['tickets'])));
$without_region = count(array_filter($payload['items'], static fn(array $i): bool => empty($i['region'])));

printf(
  "Готово: %d экскурсий (%d с ценами, %d без региона) → %s\n",
  count($payload['items']),
  $with_prices,
  $without_region,
  $out_path
);
