<?php

add_action('wp_ajax_agency_events_filter', 'bsi_agency_events_filter');
add_action('wp_ajax_nopriv_agency_events_filter', 'bsi_agency_events_filter');

function bsi_agency_events_filter()
{
  $kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
  $direction = isset($_POST['direction']) ? sanitize_key(wp_unslash($_POST['direction'])) : '';
  $archive = isset($_POST['archive']) ? sanitize_text_field(wp_unslash($_POST['archive'])) : '';
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
  if ($direction !== '') {
    $tax_query[] = [
      'taxonomy' => 'agency_event_direction',
      'field' => 'slug',
      'terms' => [$direction],
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
    'order' => 'ASC',
    'meta_query' => $meta_query_upcoming,
  ]);

  $events_query_archive = $show_archives ? new WP_Query([
    ...$base_query_args,
    'order' => 'DESC',
    'meta_query' => $meta_query_archive,
  ]) : null;

  ob_start();
  $has_any = ($events_query_upcoming->have_posts())
    || ($events_query_archive && $events_query_archive->have_posts());

  if ($has_any) {
    while ($events_query_upcoming->have_posts()) {
      $events_query_upcoming->the_post();
      get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]);
    }
    wp_reset_postdata();

    if ($events_query_archive) {
      while ($events_query_archive->have_posts()) {
        $events_query_archive->the_post();
        get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]);
      }
      wp_reset_postdata();
    }
  } else {
    echo '<div class="agency-page__empty">Пока нет мероприятий.</div>';
  }

  wp_send_json_success([
    'html' => ob_get_clean(),
    'total' => (int) $events_query_upcoming->found_posts + ($events_query_archive ? (int) $events_query_archive->found_posts : 0),
  ]);
}
