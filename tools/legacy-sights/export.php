<?php

/**
 * Экспорт достопримечательностей старого сайта (Bitrix, past.bsigroup.ru) в JSON.
 *
 * Запускается ТОЛЬКО локально — нужен доступ к MySQL старого сайта
 * (контейнер oldbsi_mysql, порт 13306 проброшен на хост).
 *
 * Использование:
 *   php export.php --country=gbr [--out=data/gbr.json]
 *
 * Структура источника:
 *   t_sights                       — S_ID, S_COUNTRY, S_CITY, S_SUBTYPE, S_URLCODE, S_VISIBLE
 *   t_sights_lang                  — S_NAME (ru)
 *   t_additional_fields_values     — ADFV_ADFID 66 (текст), 79 (превью)
 *
 * Координат в старой базе нет вообще: поле sight_map_coordinates останется пустым,
 * его заполняют вручную либо отдельным геокодером.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

const BSI_SIGHTS_ADF_TEXT = 66;
const BSI_SIGHTS_ADF_PREVIEW = 79;

$options = getopt('', ['country:', 'out:', 'host:', 'port:', 'user:', 'pass:', 'db:', 'with-hidden']);

$country_code = strtolower((string) ($options['country'] ?? 'gbr'));
$with_hidden = isset($options['with-hidden']);

$db_host = (string) ($options['host'] ?? '127.0.0.1');
$db_port = (int) ($options['port'] ?? 13306);
$db_user = (string) ($options['user'] ?? 'root');
$db_pass = (string) ($options['pass'] ?? 'root');
$db_name = (string) ($options['db'] ?? 'bx_sitemanager');

/* ───────────────────────────────────────────────────────────────────
 * Конфиг стран.
 *
 * `cities` — раскладка городов по регионам, те же названия, что у импорта
 * экскурсий (tools/legacy-excursions/export.php), чтобы термины region
 * не размножались.
 * `subtype_regions` — запасной вариант для записей без города: в t_sights
 * поле S_SUBTYPE хранит историческую область (Англия, Шотландия, Уэльс…).
 * ─────────────────────────────────────────────────────────────────── */

$countries = [
  'gbr' => [
    'legacy_id' => 222,
    'country_slug' => 'velikobritaniya',
    'default_region' => null,
    'cities' => [
      'Лондон' => 'Лондон',
      'Хэмптон-Корт' => 'Лондон',
      'Эдинбург' => 'Шотландия',
      'Глазго' => 'Шотландия',
      'Сент Эндрюс' => 'Шотландия',
      'Белфаст' => 'Северная Ирландия',
      'Графство Антрим' => 'Северная Ирландия',
      'Лондондерри' => 'Северная Ирландия',
      'Эннискиллен' => 'Северная Ирландия',
      'Данганнон' => 'Северная Ирландия',
      'Даунпатрик' => 'Северная Ирландия',
      'Ньютаунардс' => 'Северная Ирландия',
      'Арма' => 'Северная Ирландия',
      'Бушмилс' => 'Северная Ирландия',
      'Сент Хелиер' => 'Нормандские острова',
      'Сент Питер Порт' => 'Нормандские острова',
      'Сент Мартин' => 'Нормандские острова',
      'Плейнмонт' => 'Нормандские острова',
      'Бат' => 'Юг Англии',
      'Стоунхедж' => 'Юг Англии',
      /* Курорт «Оксфорд» уже заведён в регионе «Оксфордшир» — не плодим второй. */
      'Оксфорд' => 'Оксфордшир',
      'Виндзор' => 'Юг Англии',
      'Брайтон' => 'Юго-восточная Англия',
      'Кардифф' => 'Уэльс',
      'Конви' => 'Уэльс',
      'Карлион' => 'Уэльс',
      'Монмут' => 'Уэльс',
      'Лландудно' => 'Уэльс',
      'Карнарвон' => 'Уэльс',
      'Бомарис' => 'Уэльс',
      'Велшпул' => 'Уэльс',
    ],
    /* Мусор старой базы: у «Брекон-Биконс» в S_CITY стоит хорватский Пелешац. */
    'ignore_cities' => ['п-о. Пелешац'],
    'subtype_regions' => [
      'Шотландия' => 'Шотландия',
      'Уэльс' => 'Уэльс',
      'Северная Ирландия' => 'Северная Ирландия',
      'Остров Джерси' => 'Нормандские острова',
      'Остров Гернси' => 'Нормандские острова',
    ],
  ],
];

if (!isset($countries[$country_code])) {
  exit("Нет конфига для страны '$country_code'. Добавь в \$countries.\n");
}

$config = $countries[$country_code];
$out_path = (string) ($options['out'] ?? __DIR__ . '/data/' . $country_code . '.json');

/* ───────────────────────────────────────────────────────────────────
 * Тип достопримечательности по названию.
 * В старой базе типа нет — определяем по ключевым словам, чтобы каталог
 * сразу фильтровался. Не распознали — тип не проставляем.
 * ─────────────────────────────────────────────────────────────────── */

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

/* ───────────────────────────────────────────────────────────────────
 * Чистка HTML старого сайта — тот же набор правил, что у экскурсий.
 * Картинки вырезаем: файлы остались на старом сайте (72 ГБ не переносили).
 * ─────────────────────────────────────────────────────────────────── */

function clean_legacy_html(?string $html): string
{
  $html = trim((string) $html);
  if ($html === '') {
    return '';
  }

  $html = preg_replace('#<!--.*?-->#s', '', $html);
  $html = preg_replace('#<(o:p|v:\w+)[^>]*>.*?</\1>#is', '', $html);
  $html = preg_replace('#</?(o:p|v:\w+|font|span)[^>]*>#i', '', $html);

  $html = preg_replace('#<img[^>]*>#i', '', $html);

  $html = preg_replace('#<a[^>]*href=["\'][^"\']*(past\.)?bsigroup\.ru[^"\']*["\'][^>]*>(.*?)</a>#is', '$2', $html);

  $html = preg_replace_callback('#<(\w+)([^>]*)>#', static function (array $m): string {
    $tag = strtolower($m[1]);
    if ($tag === 'a' && preg_match('#href=["\']([^"\']+)["\']#i', $m[2], $href)) {
      return '<a href="' . $href[1] . '">';
    }
    return '<' . $tag . '>';
  }, $html);

  $html = preg_replace('#<(p|div|span|b|strong|em|i)>\s*(&nbsp;|\s)*</\1>#i', '', $html);
  $html = preg_replace('#(<br\s*/?>\s*){3,}#i', '<br><br>', $html);
  $html = preg_replace('#\s{2,}#', ' ', $html);

  return trim($html);
}

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

/* ───────────────────────────────────────────────────────────────────
 * Выборка
 * ─────────────────────────────────────────────────────────────────── */

$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($mysqli->connect_errno) {
  exit("Не подключиться к базе старого сайта: {$mysqli->connect_error}\n");
}
$mysqli->set_charset('utf8mb4');

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
    text_field.ADFV_VALUE     AS content,
    preview_field.ADFV_VALUE  AS preview
  FROM t_sights s
  INNER JOIN t_sights_lang sl ON sl.S_ID = s.S_ID AND sl.S_LID = 'ru'
  LEFT JOIN t_cities_lang cl ON cl.C_ID = s.S_CITY AND cl.C_LID = 'ru'
  LEFT JOIN t_subtypes_lang stl ON stl.ST_ID = s.S_SUBTYPE AND stl.ST_LID = 'ru'
  LEFT JOIN t_additional_fields_values text_field
    ON text_field.ADFV_ADFID = " . BSI_SIGHTS_ADF_TEXT . " AND text_field.ADFV_LANG = 'ru' AND text_field.ADFV_ELEMENTID = s.S_ID
  LEFT JOIN t_additional_fields_values preview_field
    ON preview_field.ADFV_ADFID = " . BSI_SIGHTS_ADF_PREVIEW . " AND preview_field.ADFV_LANG = 'ru' AND preview_field.ADFV_ELEMENTID = s.S_ID
  WHERE s.S_COUNTRY = {$legacy_country_id}{$visible_clause}
  ORDER BY cl.C_NAME, sl.S_NAME
";

$result = $mysqli->query($sql);
if (!$result) {
  exit("Ошибка запроса достопримечательностей: {$mysqli->error}\n");
}

$items = [];

while ($row = $result->fetch_assoc()) {
  $legacy_id = (int) $row['legacy_id'];
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

  $region = null;
  if ($city !== '' && isset($config['cities'][$city])) {
    $region = $config['cities'][$city];
  } elseif ($subtype !== '' && isset($config['subtype_regions'][$subtype])) {
    $region = $config['subtype_regions'][$subtype];
  } else {
    $region = $config['default_region'];
  }

  $items[] = [
    'legacy_id' => $legacy_id,
    'slug' => to_utf8($row['urlcode'] ?? ''),
    'title' => $title,
    'content' => $content,
    'excerpt' => build_excerpt(to_utf8($row['preview']), $content),
    'city' => $city,
    'region' => $region,
    'subtype' => $subtype,
    'sight_type' => detect_sight_type($title),
    'visible' => (string) $row['visible'] === '1',
  ];
}

/* ───────────────────────────────────────────────────────────────────
 * Итог
 * ─────────────────────────────────────────────────────────────────── */

$payload = [
  'generated_at' => date('c'),
  'source' => 'past.bsigroup.ru (bitrix), t_sights',
  'country_code' => $country_code,
  'country_slug' => $config['country_slug'],
  'legacy_country_id' => $legacy_country_id,
  'items' => $items,
];

if (!is_dir(dirname($out_path))) {
  mkdir(dirname($out_path), 0775, true);
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
  exit('json_encode: ' . json_last_error_msg() . "\n");
}

file_put_contents($out_path, $json);

$without_region = count(array_filter($items, static fn(array $i): bool => empty($i['region'])));
$without_type = count(array_filter($items, static fn(array $i): bool => $i['sight_type'] === ''));
$without_city = count(array_filter($items, static fn(array $i): bool => $i['city'] === ''));

printf(
  "Готово: %d достопримечательностей (без региона %d, без города %d, без типа %d) → %s\n",
  count($items),
  $without_region,
  $without_city,
  $without_type,
  $out_path
);
