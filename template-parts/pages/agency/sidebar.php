<?php
$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
$current_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
$current_path = untrailingslashit($current_path);

$section_page = get_page_by_path('turagenstvam');
$section_url = $section_page ? get_permalink($section_page->ID) : home_url('/turagenstvam/');
$section_path = untrailingslashit((string) wp_parse_url($section_url, PHP_URL_PATH));

$items = [
  [
    'label' => 'Документы',
    'url' => $section_url,
    'match_paths' => [$section_path, '/agentstvam', '/turagenstvam'],
    'is_docs_tab' => true,
  ],
];

$sidebar_query = [
  'post_type' => 'documentation',
  'post_status' => 'publish',
  'post_parent' => 0,
  'posts_per_page' => -1,
  'orderby' => 'menu_order title',
  'order' => 'ASC',
];

if (taxonomy_exists('agency_item_type')) {
  $sidebar_query['tax_query'] = [
    [
      'taxonomy' => 'agency_item_type',
      'field' => 'slug',
      'terms' => ['document'],
      'operator' => 'NOT IN',
    ],
  ];
}

$sidebar_posts = get_posts($sidebar_query);
if (!empty($sidebar_posts)) {
  foreach ($sidebar_posts as $sidebar_post) {
    $items[] = [
      'label' => (string) get_the_title($sidebar_post->ID),
      'url' => get_permalink($sidebar_post->ID),
      'is_docs_tab' => false,
    ];
  }
}

$current_document_id = is_singular('documentation') ? (int) get_queried_object_id() : 0;
$current_document_is_document = false;
if ($current_document_id && taxonomy_exists('agency_item_type')) {
  $current_document_is_document = has_term('document', 'agency_item_type', $current_document_id);
}
?>

<nav class="agency-sidebar" aria-label="Разделы агентствам">
  <ul class="agency-sidebar__list">
    <?php foreach ($items as $item): ?>
      <?php
      $item_path = (string) wp_parse_url($item['url'], PHP_URL_PATH);
      $item_path = untrailingslashit($item_path);
      $match_paths = isset($item['match_paths']) && is_array($item['match_paths']) ? $item['match_paths'] : [];
      $normalized_match_paths = array_map('untrailingslashit', $match_paths);
      $is_active = $item_path === $current_path || in_array($current_path, $normalized_match_paths, true);

      if (!empty($item['is_docs_tab']) && $current_document_is_document) {
        $is_active = true;
      } elseif (empty($item['is_docs_tab']) && $current_document_id) {
        $is_active = $item['url'] === get_permalink($current_document_id);
      }
      ?>
      <li class="agency-sidebar__item">
        <a href="<?php echo esc_url($item['url']); ?>"
           class="agency-sidebar__link <?php echo $is_active ? 'is-active' : ''; ?>">
          <?php echo esc_html($item['label']); ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
