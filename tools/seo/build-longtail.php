<?php
/**
 * Собирает низкочастотный хвост семантики под экскурсии и достопримечательности.
 *
 * Берёт не выдуманные фразы, а те, под которые на сайте уже есть страница
 * с наполнением: страна с экскурсиями, индексируемый курорт, страна
 * с достопримечательностями. Падежи — через bsi_country_*_title() и
 * bsi_resort_locative(), без ручных склонений.
 *
 * Запросы, уже заведённые в pr-cy, отбрасываются — сравнение по нормализованной
 * форме (регистр, «ё», лишние пробелы).
 *
 * Запуск:
 *   /Applications/MAMP/bin/php/php8.3.14/bin/php tools/seo/build-longtail.php \
 *     "wiki/docs/seo-keywords-clustered-2026-09-15.csv" \
 *     "wiki/docs/prcy-2026-09-15/20-longtail.csv"
 */

define('WP_USE_THEMES', false);
require dirname(__DIR__, 5) . '/wp-load.php';

// курорт попадает в выборку, если экскурсий на нём не меньше этого
const RESORT_MIN_EXCURSIONS = 5;
// страна попадает, если экскурсий не меньше этого
const COUNTRY_MIN_EXCURSIONS = 10;
// то же для достопримечательностей
const COUNTRY_MIN_SIGHTS = 10;

$src = $argv[1] ?? '';
$dst = $argv[2] ?? '';
if ($src === '' || $dst === '') {
    fwrite(STDERR, "нужны: <размеченный csv> <куда писать csv>\n");
    exit(1);
}

/**
 * Предлог перед названием в предложном падеже: «во Франции», но «в Италии».
 *
 * «Во» ставится перед В и Ф, а также перед стечением согласных в начале слова
 * (во Вьетнаме, во Владимире). Правило приблизительное, но на нашем наборе
 * стран и курортов даёт верный результат.
 */
function lt_v(string $name): string
{
    $first = mb_strtolower(mb_substr($name, 0, 1), 'UTF-8');
    $second = mb_strtolower(mb_substr($name, 1, 1), 'UTF-8');
    $cons = 'бвгджзйклмнпрстфхцчшщ';

    if (in_array($first, ['в', 'ф'], true) && mb_strpos($cons, $second) !== false) {
        return 'во ' . $name;
    }

    return 'в ' . $name;
}

/** Нормализация для сравнения запросов между собой. */
function lt_norm(string $q): string
{
    $q = mb_strtolower(trim($q), 'UTF-8');
    $q = str_replace('ё', 'е', $q);

    return preg_replace('/\s+/u', ' ', $q);
}

/** Локальный адрес в продовый: скрипт работает на MAMP, цель — bsigroup.ru. */
function lt_prod_url(string $url): string
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    $path = preg_replace('~^/bsinew~', '', $path);

    return 'https://bsigroup.ru' . $path;
}

/** Уже заведённые в pr-cy запросы. */
function lt_existing(string $path): array
{
    $fh = fopen($path, 'r');
    if (!$fh) {
        fwrite(STDERR, "не читается: $path\n");
        exit(1);
    }

    $head = fgetcsv($fh, 0, ';');
    $col = array_search('Запрос', array_map(fn($h) => trim($h, "\xEF\xBB\xBF"), $head), true);
    $out = [];
    while (($row = fgetcsv($fh, 0, ';')) !== false) {
        if (isset($row[$col])) {
            $out[lt_norm($row[$col])] = true;
        }
    }
    fclose($fh);

    return $out;
}

$existing = lt_existing($src);
$rows = [];
$seen = [];
// курорты с контентом, но без пригодного адреса — их чинить, а не заводить запросы
$skipped = [];

/**
 * Кладёт запрос в выборку, если он новый.
 */
function lt_add(string $query, string $url, string $group, string $source): void
{
    global $rows, $seen, $existing;

    $key = lt_norm($query);
    if ($key === '' || isset($existing[$key]) || isset($seen[$key])) {
        return;
    }
    $seen[$key] = true;
    $rows[] = ['query' => $query, 'url' => $url, 'group' => $group, 'source' => $source];
}

/* ---------- страны ---------- */

$countries = get_posts([
    'post_type'      => 'country',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$stat = ['страны-экскурсии' => 0, 'страны-достопримечательности' => 0, 'курорты' => 0];

foreach ($countries as $cid) {
    $excursions = (int) (new WP_Query([
        'post_type'      => 'excursion',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [['key' => 'excursion_country', 'value' => $cid]],
    ]))->found_posts;

    $sights = (int) (new WP_Query([
        'post_type'      => 'sight',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [['key' => 'sight_country', 'value' => $cid]],
    ]))->found_posts;

    $slug = get_post_field('post_name', $cid);
    $loc  = lt_v(bsi_country_locative_title($cid));   // «в Италии», «во Франции»
    $acc  = bsi_country_accusative_title($cid);       // «Италию»

    if ($excursions >= COUNTRY_MIN_EXCURSIONS) {
        $url = "https://bsigroup.ru/country/$slug/ekskursii/";
        lt_add("экскурсии $loc", $url, 'Экскурсии', "страна, $excursions экскурсий");
        lt_add("экскурсионные туры в $acc", $url, 'Экскурсии', "страна, $excursions экскурсий");
        lt_add("экскурсии $loc цены", $url, 'Экскурсии', "страна, $excursions экскурсий");
        lt_add("индивидуальные экскурсии $loc", $url, 'Экскурсии', "страна, $excursions экскурсий");
        lt_add("экскурсии $loc на русском языке", $url, 'Экскурсии', "страна, $excursions экскурсий");
        $stat['страны-экскурсии']++;
    }

    if ($sights >= COUNTRY_MIN_SIGHTS) {
        $url = "https://bsigroup.ru/country/$slug/dostoprimechatelnosti/";
        $gen = bsi_country_genitive_title($cid);
        lt_add("достопримечательности $gen", $url, 'Достопримечательности', "страна, $sights объектов");
        lt_add("что посмотреть $loc", $url, 'Достопримечательности', "страна, $sights объектов");
        $stat['страны-достопримечательности']++;
    }
}

/* ---------- курорты ---------- */

$resorts = get_terms([
    'taxonomy'   => 'resort',
    'hide_empty' => false,
    'fields'     => 'ids',
]);

foreach ((array) $resorts as $tid) {
    $tid = (int) $tid;
    $n = bsi_resort_count($tid, 'excursion');
    if ($n < RESORT_MIN_EXCURSIONS) {
        continue;
    }

    $term = get_term($tid, 'resort');
    $name = $term instanceof WP_Term ? $term->name : '';
    if ($name === '') {
        continue;
    }

    $raw = bsi_resort_url($tid);
    $url = lt_prod_url($raw);

    /* Причины разводим по порядку: bsi_resort_is_indexable() возвращает false
       и для незаполненного региона тоже, поэтому служебный адрес проверяем
       первым — иначе Вена со 172 экскурсиями числится «тонкой». */
    if (strpos($raw, '?resort=') !== false) {
        $skipped[] = [$n, $name, 'не заполнен регион, адрес служебный /?resort='];
        continue;
    }
    if (strpos($url, '%') !== false) {
        $skipped[] = [$n, $name, 'кириллица в адресе: ' . urldecode($url)];
        continue;
    }
    if (!bsi_resort_is_indexable($tid)) {
        $skipped[] = [$n, $name, 'страница считается тонкой'];
        continue;
    }
    $loc = lt_v(bsi_resort_locative($tid)); // «в Вене», «во Флоренции»

    lt_add("экскурсии $loc", $url, 'Экскурсии', "курорт, $n экскурсий");
    lt_add("экскурсии $loc на русском", $url, 'Экскурсии', "курорт, $n экскурсий");
    lt_add("что посмотреть $loc", $url, 'Достопримечательности', "курорт, $n экскурсий");
    $stat['курорты']++;
}

/* ---------- запись ---------- */

usort($rows, fn($a, $b) => [$a['group'], $a['query']] <=> [$b['group'], $b['query']]);

$fh = fopen($dst, 'w');
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['Запрос', 'Целевая ссылка', 'Имя группы', 'Источник'], ';');
foreach ($rows as $r) {
    fputcsv($fh, [$r['query'], $r['url'], $r['group'], $r['source']], ';');
}
fclose($fh);

// плоский список — его принимает диалог «Добавить запросы»
$txt = preg_replace('/\.csv$/', '.txt', $dst);
file_put_contents($txt, implode("\n", array_column($rows, 'query')) . "\n");

$byGroup = [];
foreach ($rows as $r) {
    $byGroup[$r['group']] = ($byGroup[$r['group']] ?? 0) + 1;
}

printf("новых запросов: %d\n", count($rows));
foreach ($byGroup as $g => $n) {
    printf("  %-26s %d\n", $g, $n);
}
echo "источники: ";
foreach ($stat as $k => $v) {
    printf('%s %d; ', $k, $v);
}
printf("\n%s\n%s\n", $dst, $txt);

if ($skipped) {
    usort($skipped, fn($a, $b) => $b[0] <=> $a[0]);
    printf("\nпропущено курортов: %d — есть экскурсии, но нет пригодного адреса\n", count($skipped));
    foreach (array_slice($skipped, 0, 20) as [$n, $name, $why]) {
        printf("  %4d  %-20s %s\n", $n, $name, $why);
    }
}
