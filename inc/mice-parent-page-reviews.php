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
      return [];
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

    return $rows;
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
