<?php
$kind = isset($_GET['kind']) ? sanitize_key(wp_unslash($_GET['kind'])) : '';
$archive = isset($_GET['archive']) ? sanitize_text_field(wp_unslash($_GET['archive'])) : '';
$show_archives = ($archive === '1');
if ($kind === 'archive') {
  $kind = '';
}

$now_ts = (int) current_time('timestamp');
$today = wp_date('Y-m-d');

$posts_per_page = 24;
$base_query_args = [
  'post_type' => 'agency_event',
  'post_status' => 'publish',
  'posts_per_page' => $posts_per_page,
  'meta_key' => 'event_start_ts',
  'orderby' => 'meta_value_num',
  'meta_query' => [],
];

$tax_query = [];
if ($kind !== '') {
  $tax_query[] = [
    'taxonomy' => 'agency_event_kind',
    'field' => 'slug',
    'terms' => [$kind],
  ];
}
if (!empty($tax_query)) {
  $base_query_args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
}

$meta_query_upcoming = [
  'relation' => 'OR',
  [
    'key' => 'event_start_ts',
    'value' => $now_ts,
    'compare' => '>=',
    'type' => 'NUMERIC',
  ],
  [
    'relation' => 'AND',
    [
      'key' => 'event_start_ts',
      'compare' => 'NOT EXISTS',
    ],
    [
      'key' => 'event_start_date',
      'value' => $today,
      'compare' => '>=',
      'type' => 'DATE',
    ],
  ],
];

$meta_query_archive = [
  'relation' => 'OR',
  [
    'key' => 'event_start_ts',
    'value' => $now_ts,
    'compare' => '<',
    'type' => 'NUMERIC',
  ],
  [
    'relation' => 'AND',
    [
      'key' => 'event_start_ts',
      'compare' => 'NOT EXISTS',
    ],
    [
      'key' => 'event_start_date',
      'value' => $today,
      'compare' => '<',
      'type' => 'DATE',
    ],
  ],
];

$events_query_upcoming = new WP_Query([
  ...$base_query_args,
  'posts_per_page' => $posts_per_page,
  'order' => 'ASC',
  'meta_query' => $meta_query_upcoming,
]);

$events_query_archive = $show_archives ? new WP_Query([
  ...$base_query_args,
  'posts_per_page' => $posts_per_page,
  'order' => 'DESC',
  'meta_query' => $meta_query_archive,
]) : null;

$kind_terms = get_terms([
  'taxonomy' => 'agency_event_kind',
  'hide_empty' => true,
  'orderby' => 'name',
  'order' => 'ASC',
]);
$kind_terms = (!is_wp_error($kind_terms) && is_array($kind_terms)) ? $kind_terms : [];
?>

<section class="agency-education" data-agency-education>
  <div class="agency-education__head">
    <div class="agency-education-tabs">
      <button type="button"
              class="agency-education-tabs__btn <?php echo ($kind === '') ? 'is-active' : ''; ?>"
              data-agency-kind="">
        Все
      </button>
      <?php foreach ($kind_terms as $kind_term): ?>
        <button type="button"
                class="agency-education-tabs__btn <?php echo ($kind === $kind_term->slug) ? 'is-active' : ''; ?>"
                data-agency-kind="<?php echo esc_attr($kind_term->slug); ?>">
          <?php echo esc_html($kind_term->name); ?>
        </button>
      <?php endforeach; ?>
    </div>

  </div>

  <div class="agency-education__list" data-agency-education-list>
    <?php
    $has_any = ($events_query_upcoming->have_posts())
      || ($events_query_archive && $events_query_archive->have_posts());
    if ($has_any):
    ?>
      <?php while ($events_query_upcoming->have_posts()): ?>
        <?php $events_query_upcoming->the_post(); ?>
        <?php get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]); ?>
      <?php endwhile; ?>
      <?php wp_reset_postdata(); ?>
      <?php if ($events_query_archive): ?>
        <?php while ($events_query_archive->have_posts()): ?>
          <?php $events_query_archive->the_post(); ?>
          <?php get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]); ?>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
      <?php endif; ?>
    <?php else: ?>
      <div class="agency-page__empty">Пока нет мероприятий.</div>
    <?php endif; ?>
  </div>
</section>
