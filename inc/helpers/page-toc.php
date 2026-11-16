<?php
/**
 * Оглавление страницы по заголовкам H2.
 *
 * Включается галкой «Оглавление по заголовкам» (ACF `bsi_page_toc`) у страницы.
 * Выводит page.php: контент прогоняется через the_content, заголовкам H2
 * проставляются id, из них собирается список ссылок.
 *
 * @see custom-fields/page-toc.php
 * @see template-parts/ui/page-toc.php
 */

/** Минимум заголовков, при котором оглавление имеет смысл. */
const BSI_PAGE_TOC_MIN_ITEMS = 2;

/**
 * Включено ли оглавление у страницы.
 */
function bsi_page_toc_enabled(int $post_id): bool
{
  return function_exists('get_field') && (bool) get_field('bsi_page_toc', $post_id);
}

/**
 * Латинский якорь из заголовка: «6. Страховка» → «6-strakhovka».
 *
 * sanitize_title() на фронте не транслитерирует (cyr2lat работает при сохранении),
 * и id получался в виде %d0%be…, поэтому транслитерация своя.
 */
function bsi_page_toc_slug(string $title): string
{
  static $map = [
    'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
    'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
    'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
    'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
    'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
  ];

  $slug = strtr(mb_strtolower($title, 'UTF-8'), $map);
  $slug = preg_replace('~[^a-z0-9]+~', '-', $slug);
  $slug = trim((string) $slug, '-');

  // Длинные заголовки дают длинные URL — режем по границе слова.
  if (strlen($slug) > 60) {
    $slug = substr($slug, 0, 60);
    $cut = strrpos($slug, '-');
    if ($cut !== false && $cut > 20) {
      $slug = substr($slug, 0, $cut);
    }
  }

  return $slug;
}

/**
 * Проставляет id заголовкам H2 и собирает пункты оглавления.
 *
 * Существующий id у заголовка сохраняется: на него уже могут вести ссылки.
 *
 * @return array{html: string, items: array<int, array{id: string, title: string}>}
 */
function bsi_page_toc_build(string $html): array
{
  $items = [];
  $used = [];

  $html = preg_replace_callback(
    '~<h2(\s[^>]*)?>(.*?)</h2>~is',
    static function (array $m) use (&$items, &$used): string {
      $attrs = $m[1] ?? '';
      $inner = $m[2];
      $title = trim(preg_replace('~\s+~u', ' ', html_entity_decode(wp_strip_all_tags($inner), ENT_QUOTES, 'UTF-8')));

      if ($title === '') {
        return $m[0];
      }

      if (preg_match('~\sid\s*=\s*(["\'])(.*?)\1~i', $attrs, $idm) && $idm[2] !== '') {
        $id = $idm[2];
      } else {
        $base = bsi_page_toc_slug($title);
        if ($base === '') {
          $base = 'section';
        }
        $id = $base;
        $n = 2;
        while (isset($used[$id])) {
          $id = $base . '-' . $n++;
        }
        $attrs .= ' id="' . esc_attr($id) . '"';
      }

      $used[$id] = true;
      $items[] = ['id' => $id, 'title' => $title];

      return '<h2' . $attrs . '>' . $inner . '</h2>';
    },
    $html
  );

  return ['html' => (string) $html, 'items' => $items];
}
