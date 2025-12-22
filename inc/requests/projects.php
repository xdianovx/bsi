<?php
/**
 * AJAX: Projects by country (GRID)
 * Возвращает HTML плитки проектов для архива.
 *
 * Ожидает:
 * - country_id (int) — ID страны, пусто/0 = все
 */

if (!defined('ABSPATH'))
  exit;

add_action('wp_ajax_projects_by_country', 'bsi_ajax_projects_by_country');
add_action('wp_ajax_nopriv_projects_by_country', 'bsi_ajax_projects_by_country');

function bsi_ajax_projects_by_country(): void
{
  $country_id = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;

  $args = [
    'post_type' => 'project',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
  ];

  // фильтр по стране (ACF project_country)
  if ($country_id > 0) {
    $args['meta_query'] = [
      [
        'key' => 'project_country',
        'value' => $country_id,
        'compare' => '=',
      ],
    ];
  }

  $q = new WP_Query($args);

  ob_start();

  if ($q->have_posts()) {
    while ($q->have_posts()) {
      $q->the_post();

      echo '<div class="projects-archive__item js-projects-item" data-country="' . esc_attr($country_id ?: 0) . '">';
      // card.php берёт текущий пост через loop (the_post уже установлен)
      get_template_part('template-parts/projects/card');
      echo '</div>';
    }
  }

  wp_reset_postdata();

  $html = ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    'found' => (int) $q->found_posts,
  ]);
}