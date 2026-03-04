<?php
$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
$current_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
$current_path = untrailingslashit($current_path);

$section_page = get_page_by_path('turagenstvam');
$section_url = $section_page ? get_permalink($section_page->ID) : home_url('/turagenstvam/');
$section_path = untrailingslashit((string) wp_parse_url($section_url, PHP_URL_PATH));

$is_education_page = is_singular('documentation')
  && get_post_field('post_name', get_queried_object_id()) === 'obuchenie';

$current_kind = isset($_GET['kind']) ? sanitize_key(wp_unslash($_GET['kind'])) : '';

$education_kinds = [
  ['slug' => 'webinar', 'label' => 'Вебинары'],
  ['slug' => 'event', 'label' => 'Мероприятия'],
  ['slug' => 'promo-tour', 'label' => 'Рекламные туры'],
];

$items = [
  [
    'label' => 'Документы',
    'url' => $section_url,
    'match_paths' => [$section_path, '/agentstvam', '/turagenstvam'],
    'is_docs_tab' => true,
    'children' => [],
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
    $permalink = get_permalink($sidebar_post->ID);
    $children = [];

    if ($sidebar_post->post_name === 'obuchenie') {
      foreach ($education_kinds as $ek) {
        $children[] = [
          'label' => $ek['label'],
          'url' => add_query_arg('kind', $ek['slug'], $permalink),
          'kind_slug' => $ek['slug'],
        ];
      }
    }

    $items[] = [
      'label' => (string) get_the_title($sidebar_post->ID),
      'url' => $permalink,
      'is_docs_tab' => false,
      'children' => $children,
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

      $has_children = !empty($item['children']);
      ?>
      <li class="agency-sidebar__item">
        <a href="<?php echo esc_url($item['url']); ?>"
          class="agency-sidebar__link <?php echo $is_active ? 'is-active' : ''; ?>">
          <?php echo esc_html($item['label']); ?>
        </a>

        <?php if ($has_children && $is_active): ?>
          <ul class="agency-sidebar__children">
            <?php foreach ($item['children'] as $child): ?>
              <?php $child_is_active = $is_education_page && $current_kind === $child['kind_slug']; ?>
              <li class="agency-sidebar__child-item">
                <a href="<?php echo esc_url($child['url']); ?>"
                  class="agency-sidebar__child-link <?php echo $child_is_active ? 'is-active' : ''; ?>">
                  <?php echo esc_html($child['label']); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>

<?php if (!$is_education_page): ?>
  <?php
  $sidebar_events = new WP_Query([
    'post_type' => 'agency_event',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'meta_key' => 'event_start_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => [
      [
        'key' => 'event_start_date',
        'value' => date('Y-m-d'),
        'compare' => '>=',
        'type' => 'DATE',
      ],
    ],
  ]);

  $education_post = get_page_by_path('obuchenie', OBJECT, 'documentation');
  $education_url = $education_post ? get_permalink($education_post->ID) : '#';
  ?>

  <?php if ($sidebar_events->have_posts()): ?>
    <div class="agency-sidebar-education">
      <h3 class="agency-sidebar-education__title">Ближайшие</h3>

      <div class="agency-sidebar-education__list">
        <?php while ($sidebar_events->have_posts()): ?>
          <?php $sidebar_events->the_post(); ?>
          <?php get_template_part('template-parts/agency/event-card-sidebar', null, ['post_id' => get_the_ID()]); ?>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
      </div>

      <a href="<?php echo esc_url($education_url); ?>" class="agency-sidebar-education__more">
        Смотреть все
      </a>
    </div>
  <?php endif; ?>
<?php endif; ?>