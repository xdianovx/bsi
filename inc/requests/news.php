<?php

add_action('wp_ajax_bsi_filter_news', 'bsi_filter_news');
add_action('wp_ajax_nopriv_bsi_filter_news', 'bsi_filter_news');

function bsi_filter_news()
{
  $term_slug = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';

  $args = [
    'post_type' => 'news',
    'post_status' => 'publish',
    'posts_per_page' => 10,
  ];

  if ($term_slug !== '') {
    $args['tax_query'] = [
      [
        'taxonomy' => 'news_type',
        'field' => 'slug',
        'terms' => [$term_slug],
      ],
    ];
  }

  $query = new WP_Query($args);

  if ($query->have_posts()) {
    ob_start();
    while ($query->have_posts()) {
      $query->the_post();
      get_template_part('template-parts/news/card');
    }
    wp_reset_postdata();
    echo ob_get_clean();
  } else {
    echo '<div class="no-news"><p>Новостей пока нет.</p></div>';
  }

  wp_die();
}