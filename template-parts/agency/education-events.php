<?php
/**
 * Список мероприятий раздела «Турагентствам».
 * Ближайшие — на странице мероприятий, прошедшие — на дочерней странице «Архив».
 * Вкладки типов, поиск и пагинация — обычные ссылки/GET, без AJAX.
 */

$show_archive = bsi_agency_events_is_archive_view();
$kind = bsi_agency_events_current_kind();
$search = bsi_agency_events_search_query();
$paged = bsi_agency_events_paged();
$base_url = bsi_agency_events_base_url();

$now_ts = (int) current_time('timestamp');
$today = wp_date('Y-m-d');

$query_args = [
  'post_type' => 'agency_event',
  'post_status' => 'publish',
  'posts_per_page' => 12,
  'paged' => $paged,
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

if ($search !== '') {
  $query_args['s'] = $search;
}

// Мероприятие считается прошедшим по event_start_ts; для записей без метки
// откатываемся на дату из ACF.
$compare = $show_archive ? '<' : '>=';

$query_args['meta_query'] = [
  'relation' => 'OR',
  [
    'key' => 'event_start_ts',
    'value' => $now_ts,
    'compare' => $compare,
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
      'compare' => $compare,
      'type' => 'DATE',
    ],
  ],
];

$events_query = new WP_Query($query_args);

$kind_terms = bsi_agency_event_kind_terms();

$tabs = [
  [
    'label' => 'Все',
    'url' => bsi_agency_events_tab_url('', $base_url),
    'is_active' => ($kind === ''),
  ],
];

foreach ($kind_terms as $kind_term) {
  $tabs[] = [
    'label' => bsi_agency_event_kind_plural($kind_term->slug, $kind_term->name),
    'url' => bsi_agency_events_tab_url($kind_term->slug, $base_url),
    'is_active' => ($kind === $kind_term->slug),
  ];
}

$empty_text = $show_archive ? 'В архиве пока пусто.' : 'Пока нет мероприятий.';
if ($search !== '') {
  $empty_text = 'По запросу ничего не найдено.';
}
?>

<section class="agency-education">
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

    <?php if ($show_archive): // поиск нужен только по накопленному архиву ?>
    <form class="agency-education-search" method="get" action="<?php echo esc_url($base_url); ?>" role="search">
      <?php if ($kind !== ''): ?>
        <input type="hidden" name="kind" value="<?php echo esc_attr($kind); ?>">
      <?php endif; ?>
      <label class="screen-reader-text" for="agency-events-search">Поиск по мероприятиям</label>
      <input class="agency-education-search__input" type="search" id="agency-events-search" name="q"
        value="<?php echo esc_attr($search); ?>" placeholder="Поиск по названию">
      <button class="agency-education-search__submit btn btn-gray" type="submit">Найти</button>
      <?php if ($search !== ''): ?>
        <?php $reset_url = ($kind !== '') ? add_query_arg('kind', $kind, $base_url) : $base_url; ?>
        <a class="agency-education-search__reset" href="<?php echo esc_url($reset_url); ?>">Сбросить</a>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div>

  <div class="agency-education__list">
    <?php if ($events_query->have_posts()): ?>
      <?php while ($events_query->have_posts()): ?>
        <?php $events_query->the_post(); ?>
        <?php get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]); ?>
      <?php endwhile; ?>
      <?php wp_reset_postdata(); ?>
    <?php else: ?>
      <div class="agency-page__empty"><?php echo esc_html($empty_text); ?></div>
    <?php endif; ?>
  </div>

  <?php
  $pagination = paginate_links([
    'base' => trailingslashit($base_url) . 'page/%#%/',
    'format' => '',
    'current' => $paged,
    'total' => (int) $events_query->max_num_pages,
    'add_args' => array_filter([
      'kind' => $kind !== '' ? $kind : null,
      'q' => $search !== '' ? $search : null,
    ]),
    'prev_text' => 'Назад',
    'next_text' => 'Вперёд',
  ]);
  ?>
  <?php if ($pagination): ?>
    <nav class="ui-pagination agency-education-pagination" aria-label="Страницы мероприятий">
      <?php echo $pagination; ?>
    </nav>
  <?php endif; ?>
</section>
