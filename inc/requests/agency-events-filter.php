<?php

add_action('wp_ajax_agency_events_filter', 'bsi_agency_events_filter');
add_action('wp_ajax_nopriv_agency_events_filter', 'bsi_agency_events_filter');

function bsi_agency_events_filter()
{
  $kind = isset($_POST['kind']) ? sanitize_key(wp_unslash($_POST['kind'])) : '';
  $direction = isset($_POST['direction']) ? sanitize_key(wp_unslash($_POST['direction'])) : '';

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

  ob_start();
  if ($events_query->have_posts()) {
    while ($events_query->have_posts()) {
      $events_query->the_post();
      get_template_part('template-parts/agency/event-card', null, ['post_id' => get_the_ID()]);
    }
  } else {
    echo '<div class="agency-page__empty">Пока нет мероприятий.</div>';
  }
  wp_reset_postdata();

  wp_send_json_success([
    'html' => ob_get_clean(),
    'total' => (int) $events_query->found_posts,
  ]);
}
