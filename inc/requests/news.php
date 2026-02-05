<?php

add_action('wp_ajax_bsi_filter_news', 'bsi_filter_news');
add_action('wp_ajax_nopriv_bsi_filter_news', 'bsi_filter_news');

function bsi_filter_news()
{
  $term_slug = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
  $paged = isset($_POST['paged']) ? absint(wp_unslash($_POST['paged'])) : 1;

  $args = [
    'post_type' => 'news',
    'post_status' => 'publish',
    'posts_per_page' => 9,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
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

  $response = [
    'html' => '',
    'pagination' => '',
  ];

  if ($query->have_posts()) {
    ob_start();
    while ($query->have_posts()) {
      $query->the_post();
      get_template_part('template-parts/news/card');
    }
    wp_reset_postdata();
    $response['html'] = ob_get_clean();

    // Генерируем пагинацию
    if ($query->max_num_pages > 1) {
      ob_start();
      echo paginate_links([
        'total' => $query->max_num_pages,
        'current' => $paged,
        'prev_text' => '&larr; Назад',
        'next_text' => 'Вперед &rarr;',
        'mid_size' => 2,
      ]);
      $response['pagination'] = ob_get_clean();
    }
  } else {
    $response['html'] = '<div class="no-news"><p>Новостей пока нет.</p></div>';
  }

  wp_send_json_success($response);
}