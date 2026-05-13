<?php
/**
 * Отзывы, заданные на родительской странице MICE (шаблон page-mice.php).
 * Используются как общий источник для page-mice.php, page-bsimice.php, page-delovoy.php.
 *
 * Не путать с inc/mice-aggregate-reviews.php — там обратная агрегация (с лендингов в MICE).
 */

if (!function_exists('bsi_get_mice_parent_page_ids')) {
  /**
   * Все опубликованные страницы, использующие шаблон page-mice.php.
   *
   * @return list<int>
   */
  function bsi_get_mice_parent_page_ids(): array
  {
    static $cache = null;
    if ($cache !== null) {
      return $cache;
    }

    $ids = get_posts([
      'post_type' => 'page',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'menu_order',
      'order' => 'ASC',
      'fields' => 'ids',
      'meta_key' => '_wp_page_template',
      'meta_value' => 'page-mice.php',
      'no_found_rows' => true,
    ]);

    $cache = is_array($ids) ? array_map('intval', $ids) : [];
    return $cache;
  }
}

if (!function_exists('bsi_get_mice_page_reviews_rows')) {
  /**
   * Непустые слайды из ACF-репитера mice_page_reviews на странице(ах) MICE.
   *
   * @return list<array<string, mixed>>
   */
  function bsi_get_mice_page_reviews_rows(): array
  {
    if (!function_exists('get_field')) {
      return bsi_get_mice_page_reviews_test_rows();
    }

    $rows = [];
    foreach (bsi_get_mice_parent_page_ids() as $page_id) {
      $page_rows = get_field('mice_page_reviews', $page_id);
      if (empty($page_rows) || !is_array($page_rows)) {
        continue;
      }
      foreach ($page_rows as $row) {
        if (!is_array($row)) {
          continue;
        }
        $quote = isset($row['quote']) ? trim((string) $row['quote']) : '';
        $author = isset($row['author_name']) ? trim((string) $row['author_name']) : '';
        if ($quote === '' && $author === '') {
          continue;
        }
        $rows[] = $row;
      }
    }

    // === [TEST] ===========================================================
    // Если в ACF на странице MICE отзывы ещё не заведены — отдаём заглушки,
    // чтобы проверить, что блок одинаково отрисовывается на page-mice.php,
    // page-bsimice.php и page-delovoy.php. Удалить эти 3 строки чтобы откатить.
    if ($rows === []) {
      $rows = bsi_get_mice_page_reviews_test_rows();
    }
    // === [/TEST] ==========================================================

    return $rows;
  }
}

if (!function_exists('bsi_get_mice_page_reviews_test_rows')) {
  /**
   * [TEST] Заглушка отзывов «с родительской страницы MICE» для проверки вывода
   * на page-mice.php, page-bsimice.php и page-delovoy.php.
   * Удалить функцию вместе с TEST-блоком в bsi_get_mice_page_reviews_rows().
   *
   * @return list<array{quote: string, author_name: string, author_title: string}>
   */
  function bsi_get_mice_page_reviews_test_rows(): array
  {
    return [
      [
        'quote' => '[TEST · родительская MICE] Команда BSI Group полностью взяла на себя организацию нашей корпоративной конференции — от подбора площадки до логистики делегатов. Всё прошло безупречно.',
        'author_name' => 'Анна Петрова',
        'author_title' => 'HR-директор «ТестКорп»',
      ],
      [
        'quote' => '[TEST · родительская MICE] Организовали инсентив-тур на 120 человек за 4 недели — нашли отель, маршрут, гидов, технику. Менеджер был на связи 24/7. Рекомендуем.',
        'author_name' => 'Дмитрий Соколов',
        'author_title' => 'Коммерческий директор «Демо Холдинг»',
      ],
      [
        'quote' => '[TEST · родительская MICE] С BSI работаем уже 5 лет: командировки, конференции, тимбилдинги. Прозрачные отчёты, фиксированные цены, никаких сюрпризов.',
        'author_name' => 'Ольга Иванова',
        'author_title' => 'Финансовый директор «Пример Групп»',
      ],
      [
        'quote' => '[TEST · родительская MICE] Заказывали форум на 500 участников. Регистрация, кейтеринг, синхронный перевод, трансляция — всё под ключ. Готовы рекомендовать.',
        'author_name' => 'Сергей Михайлов',
        'author_title' => 'CEO «Образец Индастриз»',
      ],
    ];
  }
}

if (!function_exists('bsi_get_mice_page_reviews_heading')) {
  /**
   * Заголовок секции отзывов, заданный на странице MICE. Пустая строка если не задан.
   */
  function bsi_get_mice_page_reviews_heading(): string
  {
    if (!function_exists('get_field')) {
      return '';
    }

    foreach (bsi_get_mice_parent_page_ids() as $page_id) {
      $heading = get_field('mice_page_reviews_heading', $page_id);
      if (is_string($heading) && trim($heading) !== '') {
        return trim($heading);
      }
    }

    return '';
  }
}
