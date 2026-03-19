<?php
$kind = isset($_GET['kind']) ? sanitize_key(wp_unslash($_GET['kind'])) : '';

$query_args = [
  'post_type' => 'agency_event',
  'post_status' => 'publish',
  'posts_per_page' => 24,
  'meta_key' => 'event_start_date',
  'orderby' => 'meta_value',
  'order' => 'ASC',
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
  $query_args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
}

$events_query = new WP_Query($query_args);

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
              class="agency-education-tabs__btn <?php echo $kind === '' ? 'is-active' : ''; ?>"
              data-agency-kind="">
        Все
      </button>
      <?php foreach ($kind_terms as $kind_term): ?>
        <button type="button"
                class="agency-education-tabs__btn <?php echo $kind === $kind_term->slug ? 'is-active' : ''; ?>"
                data-agency-kind="<?php echo esc_attr($kind_term->slug); ?>">
          <?php echo esc_html($kind_term->name); ?>
        </button>
      <?php endforeach; ?>
    </div>

  </div>

  <div class="agency-education__list" data-agency-education-list>
    <?php if ($events_query->have_posts()): ?>
      <?php while ($events_query->have_posts()): ?>
        <?php $events_query->the_post(); ?>
        <?php get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]); ?>
      <?php endwhile; ?>
      <?php wp_reset_postdata(); ?>
    <?php else: ?>
      <div class="agency-page__empty">Пока нет мероприятий.</div>
    <?php endif; ?>
  </div>
</section>
