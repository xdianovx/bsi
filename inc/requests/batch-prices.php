<?php
/**
 * AJAX endpoints для пакетной загрузки цен туров и отелей
 * 
 * @package BSI
 * @since 1.0.0
 */

require_once get_template_directory() . '/inc/services/PriceLoaderService.php';

/**
 * Получить цены туров пакетно
 * 
 * POST параметры:
 * - tour_ids: массив ID туров
 * - date_from: дата начала (необязательно)
 * - date_to: дата окончания (необязательно)
 * - adults: количество взрослых (по умолчанию 2)
 * - children: количество детей (по умолчанию 0)
 */
add_action('wp_ajax_get_batch_tour_prices', 'get_batch_tour_prices');
add_action('wp_ajax_nopriv_get_batch_tour_prices', 'get_batch_tour_prices');

function get_batch_tour_prices()
{
  // Получаем массив ID туров
  $tour_ids = isset($_POST['tour_ids']) ? $_POST['tour_ids'] : [];
  
  if (!is_array($tour_ids) || empty($tour_ids)) {
    wp_send_json_error(['message' => 'tour_ids parameter is required and must be an array']);
  }

  // Санитизация ID туров
  $tour_ids = array_map('absint', $tour_ids);
  $tour_ids = array_filter($tour_ids);

  if (empty($tour_ids)) {
    wp_send_json_error(['message' => 'No valid tour IDs provided']);
  }

  // Получаем дополнительные параметры
  $params = [];
  
  if (!empty($_POST['date_from'])) {
    $params['date_from'] = sanitize_text_field(wp_unslash($_POST['date_from']));
  }
  
  if (!empty($_POST['date_to'])) {
    $params['date_to'] = sanitize_text_field(wp_unslash($_POST['date_to']));
  }
  
  if (isset($_POST['adults'])) {
    $params['adults'] = absint($_POST['adults']);
  }
  
  if (isset($_POST['children'])) {
    $params['children'] = absint($_POST['children']);
  }

  // Загружаем цены
  $prices = PriceLoaderService::getBatchTourPrices($tour_ids, $params);

  // Формируем ответ
  $response = [];
  foreach ($tour_ids as $tour_id) {
    if (isset($prices[$tour_id])) {
      $response[$tour_id] = $prices[$tour_id];
    } else {
      // Если цену не удалось загрузить, возвращаем null
      $response[$tour_id] = null;
    }
  }

  wp_send_json_success([
    'prices' => $response,
    'total' => count($response),
    'cached' => count(array_filter($response)),
  ]);
}

/**
 * Получить цену одного тура
 * 
 * POST параметры:
 * - tour_id: ID тура
 * - date_from: дата начала (необязательно)
 * - date_to: дата окончания (необязательно)
 * - adults: количество взрослых (по умолчанию 2)
 * - children: количество детей (по умолчанию 0)
 */
add_action('wp_ajax_get_tour_price', 'get_tour_price_single');
add_action('wp_ajax_nopriv_get_tour_price', 'get_tour_price_single');

function get_tour_price_single()
{
  $tour_id = isset($_POST['tour_id']) ? absint($_POST['tour_id']) : 0;
  
  if (!$tour_id) {
    wp_send_json_error(['message' => 'tour_id parameter is required']);
  }

  // Получаем дополнительные параметры
  $params = [];
  
  if (!empty($_POST['date_from'])) {
    $params['date_from'] = sanitize_text_field(wp_unslash($_POST['date_from']));
  }
  
  if (!empty($_POST['date_to'])) {
    $params['date_to'] = sanitize_text_field(wp_unslash($_POST['date_to']));
  }
  
  if (isset($_POST['adults'])) {
    $params['adults'] = absint($_POST['adults']);
  }
  
  if (isset($_POST['children'])) {
    $params['children'] = absint($_POST['children']);
  }

  // Загружаем цену
  $price_data = PriceLoaderService::getTourPrice($tour_id, $params);

  if ($price_data === null) {
    wp_send_json_error(['message' => 'Failed to load tour price']);
  }

  wp_send_json_success($price_data);
}

/**
 * Очистить кэш цен
 * 
 * POST параметры:
 * - tour_id: ID конкретного тура (необязательно, если не указан - очистит весь кэш)
 */
add_action('wp_ajax_clear_tour_prices_cache', 'clear_tour_prices_cache');

function clear_tour_prices_cache()
{
  // Проверяем права доступа
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
  }

  $tour_id = isset($_POST['tour_id']) ? absint($_POST['tour_id']) : null;
  
  $cleared = PriceLoaderService::clearTourPricesCache($tour_id);

  wp_send_json_success([
    'message' => $tour_id ? 'Tour price cache cleared' : 'All tour prices cache cleared',
    'cleared' => $cleared,
  ]);
}
