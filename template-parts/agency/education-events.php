<?php
/**
 * Список мероприятий раздела «Турагентствам».
 * Вкладки — обычные ссылки (?kind=…, ?archive=1), страница рендерится на сервере.
 */

$kind = bsi_agency_events_current_kind();
$show_archive = bsi_agency_events_is_archive_view();

$now_ts = (int) current_time('timestamp');
$today = wp_date('Y-m-d');

$query_args = [
  'post_type' => 'agency_event',
  'post_status' => 'publish',
  'posts_per_page' => 24,
  'meta_key' => 'event_start_ts',
  'orderby' => 'meta_value_num',
  'order' => $show_archive ? 'DESC' : 'ASC',
];

if ($kind !== '') {
  $query_args['tax_query'] = [
    [
      'taxonomy' => 'agency_event_kind',
      'field' => 'slug',
      'terms' => [$kind],
    ],
  ];
}

// Мероприятие считается прошедшим по event_start_ts; для записей без метки
// откатываемся на дату из ACF.
$compare_ts = $show_archive ? '<' : '>=';
$compare_date = $show_archive ? '<' : '>=';

$query_args['meta_query'] = [
  'relation' => 'OR',
  [
    'key' => 'event_start_ts',
    'value' => $now_ts,
    'compare' => $compare_ts,
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
      'compare' => $compare_date,
      'type' => 'DATE',
    ],
  ],
];

$events_query = new WP_Query($query_args);

$kind_terms = function_exists('bsi_agency_event_kind_terms') ? bsi_agency_event_kind_terms() : [];

// Вкладки не сбрасывают режим архива — «Архив» переключается ссылкой в шапке.
$tab_suffix = $show_archive ? ['archive' => '1'] : [];

$tabs = [
  [
    'label' => 'Все',
    'url' => add_query_arg($tab_suffix, bsi_agency_events_tab_url('')),
    'is_active' => ($kind === ''),
  ],
];

foreach ($kind_terms as $kind_term) {
  $tabs[] = [
    'label' => bsi_agency_event_kind_plural($kind_term->slug, $kind_term->name),
    'url' => add_query_arg($tab_suffix, bsi_agency_events_tab_url($kind_term->slug)),
    'is_active' => ($kind === $kind_term->slug),
  ];
}
?>

<section class="agency-education" data-agency-education>
  <div class="agency-education__head">
    <nav class="agency-education-tabs" aria-label="Типы мероприятий">
      <?php foreach ($tabs as $tab): ?>
        <a href="<?php echo esc_url($tab['url']); ?>"
          class="agency-education-tabs__btn <?php echo $tab['is_active'] ? 'is-active' : ''; ?>"
          <?php echo $tab['is_active'] ? 'aria-current="page"' : ''; ?>>
          <?php echo esc_html($tab['label']); ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <div class="agency-education__list" data-agency-education-list>
    <?php if ($events_query->have_posts()): ?>
      <?php while ($events_query->have_posts()): ?>
        <?php $events_query->the_post(); ?>
        <?php get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]); ?>
      <?php endwhile; ?>
      <?php wp_reset_postdata(); ?>
    <?php else: ?>
      <div class="agency-page__empty">
        <?php echo $show_archive ? 'В архиве пока пусто.' : 'Пока нет мероприятий.'; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
