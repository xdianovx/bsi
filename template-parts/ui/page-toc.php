<?php
/**
 * Оглавление страницы по H2.
 *
 * @var array<int, array{id: string, title: string}> $page_toc_items  set_query_var('page_toc_items', …)
 * @see inc/helpers/page-toc.php
 */

$page_toc_items = get_query_var('page_toc_items', []);
if (empty($page_toc_items)) {
  return;
}
?>
<details class="page-toc" data-page-toc open>
  <summary class="page-toc-summary">
    <span>Содержание</span>
    <svg class="page-toc-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
  </summary>
  <nav>
    <ol class="page-toc-list">
      <?php foreach ($page_toc_items as $item): ?>
        <li>
          <a class="page-toc-link" href="#<?php echo esc_attr($item['id']); ?>"><?php echo esc_html($item['title']); ?></a>
        </li>
      <?php endforeach; ?>
    </ol>
  </nav>
</details>
