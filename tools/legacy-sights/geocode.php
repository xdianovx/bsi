<?php

/**
 * Геокодер достопримечательностей: заполняет ACF `sight_map_coordinates`
 * по названию места и городу.
 *
 * Два источника подряд:
 *  1. Nominatim (OpenStreetMap) — точен, но знает мало русских названий;
 *  2. русская Википедия — у большинства достопримечательностей статья есть,
 *     а в ней координаты. Результат принимается, только если точка попала
 *     в границы страны: поиск Википедии нечёткий и легко уводит не туда.
 *
 * Nominatim выбран потому, что ключ Яндекс.Карт из footer.php — ключ JS API,
 * HTTP-геокодер по нему отвечает 403 «Invalid api key». Отдельного ключа нет.
 *
 * Лицензия Nominatim требует не больше 1 запроса в секунду и осмысленный
 * User-Agent — оба условия соблюдены, ускорять нельзя.
 *
 * Найденные координаты помечаются метой `bsi_sight_coords_source` = 'nominatim':
 * это машинная догадка, её нужно проверять глазами. Записи с уже заполненными
 * координатами скрипт не трогает (если не передан --force).
 *
 *   php geocode.php --country=velikobritaniya --cc=gb [--source=wiki|osm|latin|both]
 *                    [--dry-run] [--limit=10] [--force]
 *
 * По умолчанию --source=wiki: быстро. Медленный добор по OSM — отдельным
 * запуском с --source=osm, он идёт примерно по 3 секунды на запись.
 *
 * Локально запускать php из MAMP — системный не видит сокет MySQL:
 *   /Applications/MAMP/bin/php/php8.3.14/bin/php geocode.php …
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  exit("CLI only\n");
}

const BSI_GEOCODE_ENDPOINT = 'https://nominatim.openstreetmap.org/search';
const BSI_GEOCODE_UA = 'BSI-sights-geocoder/1.0 (+https://bsigroup.ru)';
/* Nominatim по лицензии — не чаще 1 запроса в секунду. Википедия таких
   ограничений не ставит, ей хватает вежливой паузы и User-Agent. */
const BSI_GEOCODE_DELAY_US = 1_100_000;
const BSI_WIKI_DELAY_US = 200_000;
const BSI_WIKI_ENDPOINT = 'https://ru.wikipedia.org/w/api.php';

/**
 * Рамки стран для отсева промахов Википедии: [юг, запад, север, восток].
 */
function bsi_geocode_bbox(string $cc): ?array
{
  $map = [
    'gb' => [49.8, -8.7, 61.0, 2.0],
    'ie' => [51.4, -10.6, 55.4, -5.9],
    'fr' => [41.3, -5.2, 51.1, 9.6],
    'it' => [35.4, 6.6, 47.1, 18.6],
    'es' => [35.9, -9.3, 43.8, 4.4],
    'de' => [47.2, 5.8, 55.1, 15.1],
  ];

  return $map[$cc] ?? null;
}

/**
 * HTTP GET с User-Agent и повтором на 429 (обе API просят вежливости).
 */
function bsi_geocode_http(string $url, int $delay_us = BSI_GEOCODE_DELAY_US): ?array
{
  for ($attempt = 0; $attempt < 2; $attempt++) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 8,
      CURLOPT_USERAGENT => BSI_GEOCODE_UA,
      CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    usleep($delay_us);

    if ($status === 429) {
      sleep(2);
      continue;
    }

    if ($body === false || $status !== 200) {
      return null;
    }

    $data = json_decode((string) $body, true);

    return is_array($data) ? $data : null;
  }

  return null;
}

/**
 * Значимые слова названия: без родовых слов, которые есть у половины объектов.
 * По ним сверяется заголовок статьи Википедии — поиск там нечёткий и охотно
 * подсовывает соседний объект («Аббатство Мелроуз» → «Эбботсфорд»).
 *
 * @return string[]
 */
function bsi_geocode_tokens(string $text): array
{
  $generic = [
    'замок', 'дворец', 'музей', 'парк', 'собор', 'аббатство', 'галерея', 'сад',
    'центр', 'дом', 'хаус', 'ház', 'мост', 'озеро', 'церковь', 'часовня', 'зоопарк',
    'национальный', 'королевский', 'ботанический', 'исторический', 'современного',
    'искусства', 'города', 'the', 'of',
  ];

  $text = mb_strtolower($text);
  $text = str_replace(['ё', '«', '»', '"', '-', '—', '/'], ['е', '', '', '', ' ', ' ', ' '], $text);
  $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);

  $tokens = preg_split('/\s+/u', trim((string) $text)) ?: [];

  return array_values(array_filter($tokens, static function (string $token) use ($generic): bool {
    return mb_strlen($token) >= 4 && !in_array($token, $generic, true);
  }));
}

/**
 * Похожи ли названия: совпал значимый корень (первые 5 букв) хотя бы у одной пары слов.
 */
function bsi_geocode_titles_match(string $a, string $b): bool
{
  $tokens_a = bsi_geocode_tokens($a);
  $tokens_b = bsi_geocode_tokens($b);

  if (empty($tokens_a) || empty($tokens_b)) {
    return false;
  }

  foreach ($tokens_a as $token_a) {
    foreach ($tokens_b as $token_b) {
      $len = min(5, mb_strlen($token_a), mb_strlen($token_b));
      if ($len >= 4 && mb_substr($token_a, 0, $len) === mb_substr($token_b, 0, $len)) {
        return true;
      }
    }
  }

  return false;
}

/**
 * Координаты из русской Википедии: поиск статьи → координаты статьи.
 *
 * @return array{lat:float, lng:float, display:string}|null
 */
function bsi_geocode_wiki(string $query, string $sight_title, ?array $bbox): ?array
{
  $search = bsi_geocode_http(BSI_WIKI_ENDPOINT . '?' . http_build_query([
    'action' => 'query',
    'format' => 'json',
    'list' => 'search',
    'srlimit' => '4',
    'srsearch' => $query,
  ]), BSI_WIKI_DELAY_US);

  $hits = $search['query']['search'] ?? [];
  if (empty($hits)) {
    return null;
  }

  $titles = array_map(static fn(array $h): string => (string) $h['title'], $hits);

  $pages = bsi_geocode_http(BSI_WIKI_ENDPOINT . '?' . http_build_query([
    'action' => 'query',
    'format' => 'json',
    'prop' => 'coordinates',
    'titles' => implode('|', $titles),
  ]), BSI_WIKI_DELAY_US);

  $list = $pages['query']['pages'] ?? [];

  /* Порядок ответа не совпадает с порядком поиска — идём по релевантности. */
  foreach ($titles as $title) {
    foreach ($list as $page) {
      if (($page['title'] ?? '') !== $title || empty($page['coordinates'][0])) {
        continue;
      }

      /* Статья должна быть про то же место, а не про соседнее. */
      if (!bsi_geocode_titles_match($sight_title, $title)) {
        continue;
      }

      $lat = (float) $page['coordinates'][0]['lat'];
      $lng = (float) $page['coordinates'][0]['lon'];

      if ($bbox !== null) {
        [$south, $west, $north, $east] = $bbox;
        if ($lat < $south || $lat > $north || $lng < $west || $lng > $east) {
          continue;
        }
      }

      return ['lat' => $lat, 'lng' => $lng, 'display' => 'Википедия: ' . $title];
    }
  }

  return null;
}

/**
 * @return array{country_slug:string, cc:string, source:string, dry_run:bool, limit:int, force:bool, wp_load:string}
 */
function bsi_geocode_cli_input(): array
{
  $options = getopt('', ['country:', 'cc:', 'wp:', 'limit:', 'source:', 'dry-run', 'force']);

  $source = (string) ($options['source'] ?? 'wiki');
  if (!in_array($source, ['wiki', 'osm', 'latin', 'both'], true)) {
    exit("--source: wiki, osm, latin или both\n");
  }

  $country_slug = (string) ($options['country'] ?? 'velikobritaniya');
  $cc = strtolower((string) ($options['cc'] ?? ''));

  $wp_load = (string) ($options['wp'] ?? '');
  if ($wp_load === '') {
    $dir = __DIR__;
    for ($i = 0; $i < 6; $i++) {
      $dir = dirname($dir);
      if (is_readable($dir . '/wp-load.php')) {
        $wp_load = $dir . '/wp-load.php';
        break;
      }
    }
  }
  if ($wp_load === '' || !is_readable($wp_load)) {
    exit("Не найден wp-load.php — укажи --wp=/path/to/wp-load.php\n");
  }

  return [
    'country_slug' => $country_slug,
    'cc' => $cc,
    'source' => $source,
    'dry_run' => isset($options['dry-run']),
    'limit' => isset($options['limit']) ? (int) $options['limit'] : 0,
    'force' => isset($options['force']),
    'wp_load' => $wp_load,
  ];
}

/**
 * Один запрос к Nominatim.
 *
 * @return array{lat:float, lng:float, display:string}|null
 */
function bsi_geocode_query(string $query, string $cc): ?array
{
  $params = [
    'format' => 'json',
    'limit' => '1',
    'accept-language' => 'ru',
    'q' => $query,
  ];
  if ($cc !== '') {
    $params['countrycodes'] = $cc;
  }

  $data = bsi_geocode_http(BSI_GEOCODE_ENDPOINT . '?' . http_build_query($params));

  if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
    return null;
  }

  return [
    'lat' => (float) $data[0]['lat'],
    'lng' => (float) $data[0]['lon'],
    'display' => (string) ($data[0]['display_name'] ?? ''),
  ];
}

/**
 * Запросы латиницей: русское название Nominatim знает плохо, зато у записей
 * со старого сайта латиница есть в трёх местах — почтовый индекс в тексте,
 * английское имя в скобках и slug (он же S_URLCODE Битрикса).
 *
 * Порядок — от точного к грубому: индекс адресует дом, имя в скобках — объект,
 * slug выручает, когда в тексте нет ни того, ни другого.
 *
 * @return string[]
 */
function bsi_geocode_latin_queries(int $post_id, string $country_en): array
{
  $queries = [];

  $post = get_post($post_id);
  $content = $post ? wp_strip_all_tags((string) $post->post_content) : '';
  $title = $post ? (string) $post->post_title : '';

  /* Британский почтовый индекс: «SA32 8QH». Улицу к нему не добавляем —
     Nominatim по индексу находит точнее, чем по кривому адресу из 2008 года. */
  if ($country_en === 'UK' && preg_match('/\b[A-Z]{1,2}[0-9][0-9A-Z]?\s*[0-9][A-Z]{2}\b/', $content, $m)) {
    $queries[] = trim($m[0]) . ', ' . $country_en;
  }

  /* Английское имя в скобках: «Абергласни (Aberglasney)». */
  if (preg_match_all('/\(([A-Za-z][A-Za-z\'\-\. ]{3,40})\)/u', $title . ' ' . $content, $mm)) {
    foreach ($mm[1] as $latin) {
      $latin = trim($latin);
      if ($latin !== '') {
        $queries[] = $latin . ', ' . $country_en;
      }
    }
  }

  /* Латиница прямо в названии без скобок: «Висковарня Glenkinchie», «Королевская яхта Britannia». */
  if (preg_match_all('/\b[A-Z][A-Za-z\'\-]{3,}(?:\s+[A-Z][A-Za-z\'\-]{2,}){0,3}\b/u', $title, $tt)) {
    foreach ($tt[0] as $latin) {
      $latin = trim($latin);
      if ($latin !== '') {
        $queries[] = $latin . ', ' . $country_en;
      }
    }
  }

  /* Slug: «marblearch», «sch_thirst» — служебные префиксы и подчёркивания убираем. */
  $slug = $post ? (string) $post->post_name : '';
  $slug = preg_replace('/^(sch|st|mus)_/', '', $slug);
  $slug = trim(str_replace(['_', '-'], ' ', (string) $slug));
  if ($slug !== '' && preg_match('/^[a-z0-9 ]+$/i', $slug)) {
    $queries[] = $slug . ', ' . $country_en;
  }

  return array_values(array_unique($queries));
}

/**
 * Название без кавычек-ёлочек и служебных приписок — Nominatim по ним промахивается.
 */
function bsi_geocode_clean_title(string $title): string
{
  /* get_the_title() отдаёт ёлочки как &#171;/&#187; — в запрос они уходить не должны. */
  $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $title = str_replace(['«', '»', '"', '“', '”'], '', $title);
  $title = preg_replace('/\s+/u', ' ', $title);

  return trim((string) $title);
}

function bsi_geocode_run(array $input): void
{
  $country = get_page_by_path($input['country_slug'], OBJECT, 'country');
  if (!$country) {
    exit("Не найдена страна CPT country со слагом «{$input['country_slug']}»\n");
  }

  $country_id = (int) $country->ID;
  $country_title = (string) $country->post_title;

  $ids = get_posts([
    'post_type' => 'sight',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'orderby' => 'title',
    'order' => 'ASC',
    'no_found_rows' => true,
    'meta_query' => [
      ['key' => 'sight_country', 'value' => $country_id, 'compare' => '='],
    ],
  ]);

  if (!$input['force']) {
    $ids = array_values(array_filter($ids, static function ($id): bool {
      return trim((string) get_field('sight_map_coordinates', (int) $id)) === '';
    }));
  }

  if ($input['limit'] > 0) {
    $ids = array_slice($ids, 0, $input['limit']);
  }

  printf(
    "Страна: %s (ID %d), к геокодированию: %d%s\n\n",
    $country_title,
    $country_id,
    count($ids),
    $input['dry_run'] ? ' [dry-run]' : ''
  );

  $found = 0;
  $missed = 0;

  foreach ($ids as $post_id) {
    $post_id = (int) $post_id;
    $title = bsi_geocode_clean_title(get_the_title($post_id));

    $resorts = wp_get_post_terms($post_id, 'resort', ['fields' => 'names']);
    $city = (!is_wp_error($resorts) && !empty($resorts)) ? (string) $resorts[0] : '';

    /* Город известен — ищем только по нему. Запрос «место + страна» для
       родовых названий вроде «Ботанический сад» находит одноимённый объект
       в другом конце страны: белфастский сад так уехал в Эдинбург. */
    $queries = $city !== ''
      ? [$title . ', ' . $city]
      : [$title . ', ' . $country_title];

    /* Сначала Википедия: отвечает за доли секунды и знает русские названия
       достопримечательностей лучше. Nominatim ставит жёсткий лимит на частоту,
       под нагрузкой отвечает по 15-20 секунд и отдаёт 429, поэтому по умолчанию
       (--source=wiki) он выключен и включается отдельным медленным проходом. */
    $hit = null;

    if ($input['source'] === 'latin') {
      /* Латинские запросы идут в Nominatim по очереди: индекс → имя в скобках → slug. */
      foreach (bsi_geocode_latin_queries($post_id, strtoupper($input['cc']) === 'GB' ? 'UK' : $country_title) as $query) {
        $hit = bsi_geocode_query($query, $input['cc']);
        if ($hit !== null) {
          break;
        }
      }
    }

    if ($hit === null && !in_array($input['source'], ['osm', 'latin'], true)) {
      $hit = bsi_geocode_wiki(
        $title . ' ' . ($city !== '' ? $city : $country_title),
        $title,
        bsi_geocode_bbox($input['cc'])
      );
    }

    if ($hit === null && !in_array($input['source'], ['wiki', 'latin'], true)) {
      foreach ($queries as $query) {
        $hit = bsi_geocode_query($query, $input['cc']);
        if ($hit !== null) {
          break;
        }
      }
    }

    if ($hit === null) {
      $missed++;
      printf("  — не найдено: %s%s\n", $title, $city !== '' ? " ($city)" : '');

      /* Перепроверка (--force): старое значение — такая же машинная догадка,
         и если строгий поиск её не подтвердил, честнее очистить. Ручные
         координаты (без меты источника) не трогаем. */
      if ($input['force'] && !$input['dry_run'] && get_post_meta($post_id, 'bsi_sight_coords_source', true) !== '') {
        update_field('sight_map_coordinates', '', $post_id);
        delete_post_meta($post_id, 'bsi_sight_coords_source');
        printf("    снята прежняя координата\n");
      }

      continue;
    }

    $found++;
    $coords = sprintf('%.6F, %.6F', $hit['lat'], $hit['lng']);

    printf("  + %s → %s | %s\n", $title, $coords, mb_substr($hit['display'], 0, 70));

    if ($input['dry_run']) {
      continue;
    }

    $source = str_starts_with($hit['display'], 'Википедия: ')
      ? 'wikipedia'
      : ($input['source'] === 'latin' ? 'nominatim-latin' : 'nominatim');

    update_field('sight_map_coordinates', $coords, $post_id);
    update_post_meta($post_id, 'bsi_sight_coords_source', $source);
  }

  printf("\nНайдено: %d, не найдено: %d\n", $found, $missed);

  if (!$input['dry_run'] && $found > 0) {
    echo "Координаты помечены метой bsi_sight_coords_source (nominatim|wikipedia) — требуют проверки.\n";
  }
}

$bsi_geocode_input = bsi_geocode_cli_input();

define('WP_USE_THEMES', false);
require $bsi_geocode_input['wp_load'];

if (!function_exists('update_field')) {
  exit("ACF не активен — записывать координаты некуда.\n");
}

bsi_geocode_run($bsi_geocode_input);
