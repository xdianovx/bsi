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
?>

<section class="agency-education" data-agency-education>
  <div class="agency-education__head">
    <div class="agency-education-tabs">
      <button type="button"
              class="agency-education-tabs__btn <?php echo $kind === '' ? 'is-active' : ''; ?>"
              data-agency-kind="">
        Все
      </button>
      <button type="button"
              class="agency-education-tabs__btn <?php echo $kind === 'webinar' ? 'is-active' : ''; ?>"
              data-agency-kind="webinar">
        Вебинары
      </button>
      <button type="button"
              class="agency-education-tabs__btn <?php echo $kind === 'event' ? 'is-active' : ''; ?>"
              data-agency-kind="event">
        Мероприятия
      </button>
      <button type="button"
              class="agency-education-tabs__btn <?php echo $kind === 'promo-tour' ? 'is-active' : ''; ?>"
              data-agency-kind="promo-tour">
        Рекламные туры
      </button>
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
