<?php
/**
 * AJAX обработчик для загрузки типов виз по стране
 */

add_action('wp_ajax_get_visa_types', 'get_visa_types_by_country');
add_action('wp_ajax_nopriv_get_visa_types', 'get_visa_types_by_country');

function get_visa_types_by_country()
{
  $country_id = isset($_POST['country_id']) ? intval($_POST['country_id']) : 0;

  if (!$country_id) {
    wp_send_json_error([
      'message' => 'Не указана страна',
    ]);
    return;
  }

  // Получаем визы для страны
  $visa_ids = get_posts([
    'post_type' => 'visa',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_query' => [
      [
        'key' => 'visa_country',
        'value' => $country_id,
        'compare' => '=',
      ],
    ],
  ]);

  if (empty($visa_ids)) {
    wp_send_json_success([
      'types' => [],
    ]);
    return;
  }

  // Получаем типы виз для этих виз
  $types = wp_get_object_terms($visa_ids, 'visa_type', [
    'orderby' => 'name',
    'order' => 'ASC',
  ]);

  if (is_wp_error($types)) {
    wp_send_json_error([
      'message' => 'Ошибка при загрузке типов виз',
    ]);
    return;
  }

  // Формируем массив для ответа
  $types_data = [];
  foreach ($types as $type) {
    $types_data[] = [
      'id' => $type->term_id,
      'name' => $type->name,
    ];
  }

  wp_send_json_success([
    'types' => $types_data,
  ]);
}
