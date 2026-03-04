<?php
$kind = isset($_GET['kind']) ? sanitize_key(wp_unslash($_GET['kind'])) : '';
$direction = isset($_GET['direction']) ? sanitize_key(wp_unslash($_GET['direction'])) : '';

$query_args = [
  'post_type' => 'agency_event',
  'post_status' => 'publish',
  'posts_per_page' => 24,
  'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
];

$tax_query = [];
if ($kind !== '') {
  $tax_query[] = [
    'taxonomy' => 'agency_event_kind',
    'field' => 'slug',
    'terms' => [$kind],
  ];
}
if ($direction !== '') {
  $tax_query[] = [
    'taxonomy' => 'agency_event_direction',
    'field' => 'slug',
    'terms' => [$direction],
  ];
}
if (!empty($tax_query)) {
  $query_args['tax_query'] = array_merge([['relation' => 'AND']], $tax_query);
}

$events_query = new WP_Query($query_args);

$direction_terms = get_terms([
  'taxonomy' => 'agency_event_direction',
  'hide_empty' => true,
  'orderby' => 'name',
  'order' => 'ASC',
]);

$all_kind_tabs = [
  '' => 'Все',
  'webinar' => 'Вебинары',
  'event' => 'Мероприятия',
  'promo-tour' => 'Рекламные туры',
];

$existing_kind_terms = get_terms([
  'taxonomy' => 'agency_event_kind',
  'hide_empty' => true,
  'fields' => 'slugs',
]);
$existing_kind_slugs = (!is_wp_error($existing_kind_terms) && is_array($existing_kind_terms))
  ? $existing_kind_terms
  : [];
?>

<section class="agency-education" data-agency-education>
  <div class="agency-education__head">
    <div class="agency-education-tabs">
      <?php foreach ($all_kind_tabs as $tab_slug => $tab_label): ?>
        <?php if ($tab_slug !== '' && !in_array($tab_slug, $existing_kind_slugs, true)) continue; ?>
        <button type="button"
                class="agency-education-tabs__btn <?php echo $kind === $tab_slug ? 'is-active' : ''; ?>"
                data-agency-kind="<?php echo esc_attr($tab_slug); ?>">
          <?php echo esc_html($tab_label); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <?php if (!is_wp_error($direction_terms) && !empty($direction_terms)): ?>
      <select class="agency-education__direction-select" data-agency-direction>
        <option value="">Направление: все</option>
        <?php foreach ($direction_terms as $term): ?>
          <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($direction, $term->slug); ?>>
            <?php echo esc_html($term->name); ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
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
