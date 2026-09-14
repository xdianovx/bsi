<?php

/**
 * Ядро импорта экскурсий старого сайта. Требует загруженный WordPress.
 *
 * Ядро вызывается из единого импорта (tools/legacy-import/), у которого две
 * точки входа: CLI import.php локально и страница «Настройки сайта → Импорт
 * со старого сайта» на проде, где есть только FTP.
 *
 * Импорт идемпотентный: запись ищется по мете `bsi_legacy_excursion_id`.
 * Записи, текст которых правили после импорта, повторный прогон не перетирает —
 * см. bsi_legacy_is_edited_by_hand().
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

/* Отпечаток записи на момент импорта — по нему следующий прогон отличает
   правку контент-команды от собственного результата. */
const BSI_LEGACY_HASH_META = 'bsi_legacy_import_hash';

/**
 * Транслитерация кириллицы для слага (плагин cyr2lat недоступен в CLI).
 */
function bsi_legacy_translit(string $text): string
{
  $map = [
    'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
    'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm',
    'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
    'ф' => 'f', 'х' => 'h', 'ц' => 'cz', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh',
    'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
  ];

  return strtr(mb_strtolower($text), $map);
}

/**
 * Термин region по имени: находим существующий у этой страны либо создаём.
 */
function bsi_legacy_get_region_id(string $name, int $country_id, bool $dry_run, array &$log): int
{
  $name = trim($name);
  if ($name === '') {
    return 0;
  }

  $terms = get_terms([
    'taxonomy' => 'region',
    'name' => $name,
    'hide_empty' => false,
  ]);

  if (!is_wp_error($terms)) {
    foreach ($terms as $term) {
      $term_country = (int) get_field('region_country', 'region_' . $term->term_id);
      if ($term_country === $country_id || $term_country === 0) {
        if ($term_country === 0 && !$dry_run) {
          update_field('region_country', $country_id, 'region_' . $term->term_id);
        }
        return (int) $term->term_id;
      }
    }
  }

  if ($dry_run) {
    $log[] = "регион (создать): $name";
    return 0;
  }

  $created = wp_insert_term($name, 'region');
  if (is_wp_error($created)) {
    $log[] = "! регион не создан: $name — " . $created->get_error_message();
    return 0;
  }

  $term_id = (int) $created['term_id'];
  update_field('region_country', $country_id, 'region_' . $term_id);
  $log[] = "регион создан: $name (ID $term_id)";

  return $term_id;
}

/**
 * Термин resort по имени: находим существующий в нужном регионе либо создаём.
 */
function bsi_legacy_get_resort_id(string $name, int $region_id, bool $dry_run, array &$log): int
{
  $name = trim($name);
  if ($name === '') {
    return 0;
  }

  $terms = get_terms([
    'taxonomy' => 'resort',
    'name' => $name,
    'hide_empty' => false,
  ]);

  if (!is_wp_error($terms)) {
    foreach ($terms as $term) {
      $term_region = (int) get_field('resort_region', 'resort_' . $term->term_id);
      if ($term_region === $region_id || $term_region === 0) {
        if ($term_region === 0 && $region_id > 0 && !$dry_run) {
          update_field('resort_region', $region_id, 'resort_' . $term->term_id);
        }
        return (int) $term->term_id;
      }
    }
  }

  if ($dry_run) {
    $log[] = "курорт (создать): $name";
    return 0;
  }

  $created = wp_insert_term($name, 'resort');
  if (is_wp_error($created)) {
    $log[] = "! курорт не создан: $name — " . $created->get_error_message();
    return 0;
  }

  $term_id = (int) $created['term_id'];
  if ($region_id > 0) {
    update_field('resort_region', $region_id, 'resort_' . $term_id);
  }
  $log[] = "курорт создан: $name (ID $term_id)";

  return $term_id;
}

/**
 * Существующая запись по legacy-идентификатору.
 */
function bsi_legacy_find_excursion(int $legacy_id): int
{
  $found = get_posts([
    'post_type' => 'excursion',
    'post_status' => 'any',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => [
      ['key' => 'bsi_legacy_excursion_id', 'value' => $legacy_id, 'compare' => '='],
    ],
    'no_found_rows' => true,
  ]);

  return $found ? (int) $found[0] : 0;
}

/**
 * Чужая запись (не из импорта) с тем же слагом или заголовком.
 *
 * На проде экскурсии заводили руками — их нельзя ни перезаписать, ни продублировать,
 * поэтому такие совпадения пропускаем и показываем в отчёте.
 */
function bsi_legacy_find_conflict(string $slug, string $title): int
{
  $candidates = get_posts([
    'post_type' => 'excursion',
    'post_status' => 'any',
    'posts_per_page' => 5,
    'fields' => 'ids',
    'name' => $slug,
    'no_found_rows' => true,
  ]);

  if (empty($candidates)) {
    $candidates = get_posts([
      'post_type' => 'excursion',
      'post_status' => 'any',
      'posts_per_page' => 5,
      'fields' => 'ids',
      'title' => $title,
      'no_found_rows' => true,
    ]);
  }

  foreach ($candidates as $candidate_id) {
    if (!get_post_meta((int) $candidate_id, 'bsi_legacy_excursion_id', true)) {
      return (int) $candidate_id;
    }
  }

  return 0;
}

/**
 * Текст для сравнения: сущности развёрнуты, пробелы схлопнуты, края обрезаны.
 *
 * Две причины расхождений, которые правкой не являются: экспорт схлопывает
 * пробелы, а редактор WordPress может добавить переносы; и `wp_insert_post()`
 * экранирует голый `&` в `&amp;`, из-за чего запись сразу после импорта
 * выглядела бы изменённой.
 */
function bsi_legacy_normalize_text(string $text): string
{
  $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

  return trim(preg_replace('/\s+/u', ' ', $text));
}

/**
 * Запись правили руками после импорта — перезаписывать её нельзя.
 *
 * Источник заморожен (дамп старого сайта), поэтому расхождение между записью
 * и тем, что даёт источник, означает только одно: текст меняли на сайте.
 * Сравниваем заголовок, контент и краткое описание.
 *
 * @param array{title?: string, content?: string, excerpt?: string} $item
 */
function bsi_legacy_is_edited_by_hand(int $post_id, array $item): bool
{
  $post = get_post($post_id);
  if (!$post) {
    return false;
  }

  /* Точный путь: отпечаток, снятый при прошлом импорте с уже сохранённой
     записи. Совпал с текущим состоянием — запись после импорта не трогали. */
  $stored = (string) get_post_meta($post_id, BSI_LEGACY_HASH_META, true);
  if ($stored !== '') {
    return $stored !== bsi_legacy_post_hash($post);
  }

  /* Запасной путь для записей, залитых прежним импортом (на проде такие все):
     отпечатка нет, сравниваем с источником. Разметку при этом снимаем —
     WordPress режет теги вне белого списка kses (`<nobr>`, `<?p>`, `<br />`
     в заголовке — всё это есть в старой базе), и запись выглядела бы правленой
     без причины. Правка контент-команды меняет слова, а не только теги.
     Дальше сработает отпечаток: он снимается при первом же прогоне и точен. */
  $pairs = [
    [(string) $post->post_title, (string) ($item['title'] ?? '')],
    [(string) $post->post_content, (string) ($item['content'] ?? '')],
    [(string) $post->post_excerpt, (string) ($item['excerpt'] ?? '')],
  ];

  foreach ($pairs as [$current, $source]) {
    $current = bsi_legacy_normalize_text(strip_tags($current));
    $source = bsi_legacy_normalize_text(strip_tags($source));

    if ($current !== $source) {
      return true;
    }
  }

  return false;
}

/**
 * Отпечаток сохранённой записи: заголовок, контент и краткое описание.
 */
function bsi_legacy_post_hash(WP_Post $post): string
{
  return md5(implode('|', [
    bsi_legacy_normalize_text((string) $post->post_title),
    bsi_legacy_normalize_text((string) $post->post_content),
    bsi_legacy_normalize_text((string) $post->post_excerpt),
  ]));
}

/**
 * Снять отпечаток с записи после импорта — по нему следующий прогон поймёт,
 * правили её или нет. Читаем свежий объект: wp_insert_post() мог изменить
 * текст фильтрами kses.
 */
function bsi_legacy_store_post_hash(int $post_id): void
{
  $post = get_post($post_id);
  if ($post instanceof WP_Post) {
    update_post_meta($post_id, BSI_LEGACY_HASH_META, bsi_legacy_post_hash($post));
  }
}

/**
 * Термин на запись: у новой ставим всегда, у существующей — только если
 * таксономия пуста.
 *
 * Регион и курорт после импорта правят менеджеры; повторный прогон не должен
 * возвращать их к тому, что было в старой базе.
 */
function bsi_legacy_assign_term(int $post_id, int $term_id, string $taxonomy, bool $is_new): void
{
  if ($term_id <= 0) {
    return;
  }

  if (!$is_new && has_term('', $taxonomy, $post_id)) {
    return;
  }

  wp_set_object_terms($post_id, [$term_id], $taxonomy, false);
}

/**
 * Импорт среза записей.
 *
 * @param array  $items      элементы JSON (уже нарезанные для батча)
 * @param int    $country_id ID записи CPT country
 * @param string $status     'publish' | 'draft' — по умолчанию для всего прогона;
 *                           элемент может переопределить его полем `status`
 * @param bool   $dry_run    только посчитать, ничего не писать
 * @param bool   $force      перезаписывать записи, правленые руками после импорта
 *
 * @return array{created:int, updated:int, skipped:int, conflicts:int, protected:int, with_prices:int, log:string[]}
 */
function bsi_legacy_import_items(array $items, int $country_id, string $status, bool $dry_run, bool $force = false): array
{
  $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'protected' => 0, 'with_prices' => 0, 'log' => []];

  foreach ($items as $item) {
    $legacy_id = (int) ($item['legacy_id'] ?? 0);
    $title = trim((string) ($item['title'] ?? ''));

    if ($legacy_id <= 0 || $title === '') {
      $stats['skipped']++;
      continue;
    }

    $region_id = !empty($item['region'])
      ? bsi_legacy_get_region_id((string) $item['region'], $country_id, $dry_run, $stats['log'])
      : 0;
    $resort_id = !empty($item['city'])
      ? bsi_legacy_get_resort_id((string) $item['city'], $region_id, $dry_run, $stats['log'])
      : 0;

    $post_id = bsi_legacy_find_excursion($legacy_id);

    /* Слаг: код старого сайта, иначе транслит заголовка. */
    $slug = sanitize_title((string) ($item['slug'] ?? ''));
    if ($slug === '' || ctype_digit($slug) || str_starts_with($slug, '%')) {
      $slug = sanitize_title(bsi_legacy_translit($title));
    }

    /* Запись, заведённая руками, — не трогаем и не дублируем. */
    if ($post_id === 0) {
      $conflict_id = bsi_legacy_find_conflict($slug, $title);
      if ($conflict_id > 0) {
        $stats['conflicts']++;
        $stats['log'][] = sprintf('пропущена «%s» — уже есть запись без legacy-id (ID %d)', $title, $conflict_id);
        continue;
      }
    }

    /* Текст правили после импорта — повторный прогон его не перетирает. */
    if ($post_id > 0 && !$force && bsi_legacy_is_edited_by_hand($post_id, $item)) {
      $stats['protected']++;
      $stats['log'][] = sprintf('сохранена «%s» (ID %d) — текст правили вручную', $title, $post_id);
      continue;
    }

    /* Экспорт может задать статус для конкретной записи (скрытые на старом
       сайте приходят черновиками); иначе действует статус всего прогона. */
    $item_status = (string) ($item['status'] ?? '');
    if (!in_array($item_status, ['publish', 'draft'], true)) {
      $item_status = $status;
    }

    $postarr = [
      'post_type' => 'excursion',
      'post_title' => $title,
      'post_name' => $slug,
      'post_content' => (string) ($item['content'] ?? ''),
      'post_excerpt' => (string) ($item['excerpt'] ?? ''),
      'post_status' => $item_status,
    ];

    if ($dry_run) {
      $stats[$post_id ? 'updated' : 'created']++;
      if (!empty($item['tickets'])) {
        $stats['with_prices']++;
      }
      continue;
    }

    $is_new = $post_id === 0;

    if ($post_id > 0) {
      $postarr['ID'] = $post_id;
      /* Статус уже опубликованной записи не понижаем повторным импортом. */
      if (get_post_status($post_id) === 'publish' && $item_status === 'draft') {
        unset($postarr['post_status']);
      }
      $result = wp_update_post($postarr, true);
      $stats['updated']++;
    } else {
      $result = wp_insert_post($postarr, true);
      $stats['created']++;
    }

    if (is_wp_error($result)) {
      $stats['log'][] = "! ошибка записи «$title»: " . $result->get_error_message();
      continue;
    }

    $post_id = (int) $result;

    /* Отпечаток снимаем с уже сохранённой записи: следующий прогон по нему
       отличит правку контент-команды от собственного результата импорта. */
    bsi_legacy_store_post_hash($post_id);

    /* Картинки импорт не трогает вообще: ни `_thumbnail_id`, ни галерея
       здесь не пишутся. Фото старого сайта не переносятся (72 ГБ остались там),
       а всё, что подобрала контент-команда, переживает любой повторный прогон. */

    update_post_meta($post_id, 'bsi_legacy_excursion_id', $legacy_id);
    update_field('excursion_country', $country_id, $post_id);

    if (!empty($item['duration_hours'])) {
      update_field('excursion_duration_hours', (int) $item['duration_hours'], $post_id);
    }

    bsi_legacy_assign_term($post_id, $resort_id, 'resort', $is_new);
    bsi_legacy_assign_term($post_id, $region_id, 'region', $is_new);

    if (!empty($item['tickets']) && is_array($item['tickets'])) {
      $rows = [];
      foreach ($item['tickets'] as $ticket) {
        $rows[] = [
          'ticket_name' => (string) ($ticket['name'] ?? 'Стоимость'),
          'ticket_description' => (string) ($ticket['description'] ?? ''),
          'ticket_price_amount' => (float) ($ticket['amount'] ?? 0),
          'ticket_price_currency' => (string) ($ticket['currency'] ?? 'RUB'),
        ];
      }
      update_field('excursion_tickets', $rows, $post_id);
      $stats['with_prices']++;
    }
  }

  return $stats;
}
