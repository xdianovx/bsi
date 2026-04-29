<?php
/**
 * Агрегация отзывов с лендингов (шаблоны page-bsimice.php, page-delovoy.php) для страницы MICE.
 */

/**
 * Все непустые слайды с лендингов: page-bsimice.php — поле bsimice_reviews; page-delovoy.php — delovoy_reviews.
 *
 * @return list<array<string, mixed>>
 */
function bsi_get_mice_parent_reviews_rows(): array
{
  if (!function_exists('get_field')) {
    return [];
  }

  $merged = [];
  $template_fields = [
    'page-bsimice.php' => 'bsimice_reviews',
    'page-delovoy.php' => 'delovoy_reviews',
  ];

  foreach ($template_fields as $template => $field_name) {
    $page_ids = get_posts([
      'post_type' => 'page',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'menu_order',
      'order' => 'ASC',
      'fields' => 'ids',
      'meta_key' => '_wp_page_template',
      'meta_value' => $template,
      'no_found_rows' => true,
    ]);

    if (!is_array($page_ids)) {
      continue;
    }

    foreach ($page_ids as $page_id) {
      $rows = get_field($field_name, (int) $page_id);
      if (empty($rows) || !is_array($rows)) {
        continue;
      }
      foreach ($rows as $row) {
        if (!is_array($row)) {
          continue;
        }
        $quote = isset($row['quote']) ? trim((string) $row['quote']) : '';
        $author = isset($row['author_name']) ? trim((string) $row['author_name']) : '';
        if ($quote === '' && $author === '') {
          continue;
        }
        $merged[] = $row;
      }
    }
  }

  return $merged;
}
