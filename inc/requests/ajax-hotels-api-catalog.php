<?php

declare(strict_types=1);

/**
 * AJAX: страница каталога отелей из хаба BSIHOTELS.
 *
 * Переключение страницы и курорта перерисовывает только каталог, без
 * перезагрузки страны целиком. Разметку отдаёт тот же партиал, что и первый
 * заход — template-parts/pages/country/hotels-api-list.php.
 */

add_action('wp_ajax_bsi_hotels_api_catalog', 'bsi_hotels_api_catalog_ajax');
add_action('wp_ajax_nopriv_bsi_hotels_api_catalog', 'bsi_hotels_api_catalog_ajax');

function bsi_hotels_api_catalog_ajax(): void
{
  $country_id = isset($_POST['country_id']) ? absint($_POST['country_id']) : 0;
  $country = $country_id ? get_post($country_id) : null;

  if (!$country instanceof WP_Post || $country->post_type !== 'country') {
    wp_send_json_error(['message' => 'Страна не найдена'], 400);
  }

  $paged = max(1, isset($_POST['page']) ? absint($_POST['page']) : 1);
  $resort = sanitize_title((string) ($_POST['resort'] ?? ''));

  /**
   * Ждать хаб одним длинным запросом нельзя: веб-сервер рвёт соединение
   * на тридцатой секунде и отдаёт 500. Поэтому ждём заведомо меньше лимита,
   * а если хаб не успел — честно говорим об этом клиенту, и тот спрашивает
   * снова. Холодная выдача у хаба доходит до двух минут, за несколько таких
   * подходов она успевает прогреться.
   */
  add_filter('bsi_hotels_api_timeout', static fn() => 20);

  $catalog = bsi_hotels_api_catalog_query($country, $paged, $resort);

  ob_start();
  get_template_part('template-parts/pages/country/hotels-api-list', null, [
    'country' => $country,
    'catalog' => $catalog,
    'paged' => $paged,
  ]);
  $html = (string) ob_get_clean();

  wp_send_json_success([
    'html' => $html,
    // Хаб не успел — в разметке заглушки, клиент переспросит.
    'pending' => $catalog['error'] !== '',
    'total' => (int) ($catalog['list']['total'] ?? 0),
    'pages' => (int) ($catalog['list']['pages'] ?? 0),
  ]);
}
