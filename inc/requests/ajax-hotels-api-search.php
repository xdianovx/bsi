<?php

declare(strict_types=1);

/**
 * AJAX: свободные заезды отеля из хаба BSIHOTELS на диапазон дат.
 *
 * Календарь на странице отеля подписывает дни ценой. Ночные ставки, которые
 * приезжают с рендером, знают только прогретые дни — остальные стоят пустыми.
 * Здесь спрашиваем хаб про весь показанный месяц сразу: он идёт к оператору
 * вживую (секунды на первый запрос) и помнит ответ час, поэтому возврат к тому
 * же месяцу бесплатен.
 *
 * Браузер не может обратиться к хабу напрямую — тот живёт во внутренней сети,
 * а его адрес лежит в конфиге темы.
 *
 * Клиент — js/modules/hotel-offers.js.
 */

add_action('wp_ajax_bsi_hotels_api_search', 'bsi_hotels_api_search_ajax');
add_action('wp_ajax_nopriv_bsi_hotels_api_search', 'bsi_hotels_api_search_ajax');

function bsi_hotels_api_search_ajax(): void
{
  $hotel_id = isset($_POST['hotel']) ? absint($_POST['hotel']) : 0;
  $from = sanitize_text_field((string) ($_POST['check_in_from'] ?? ''));
  $to = sanitize_text_field((string) ($_POST['check_in_to'] ?? ''));
  $nights = isset($_POST['nights']) ? absint($_POST['nights']) : 0;

  $date = static fn(string $value): bool => (bool) preg_match('~^\d{4}-\d{2}-\d{2}$~', $value);

  if (!$hotel_id || $nights < 1 || $nights > 30 || !$date($from) || !$date($to)) {
    wp_send_json_error(['message' => 'Неверные параметры запроса'], 400);
  }

  /* Те же границы, что у хаба: диапазон не шире 31 дня и не в прошлом. Держим
     их и у себя, чтобы заведомо негодный запрос не занимал очередь к оператору. */
  $today = current_time('Y-m-d');
  if ($from < $today) {
    $from = $today;
  }
  if ($to < $from) {
    $to = $from;
  }

  $span = (strtotime($to) - strtotime($from)) / DAY_IN_SECONDS;
  if ($span > 30) {
    $to = gmdate('Y-m-d', strtotime($from) + 30 * DAY_IN_SECONDS);
  }

  /* Холодный диапазон уходит к оператору и занимает секунды. Ждём дольше
     обычного, но с запасом до предела веб-сервера.

     Фильтр стоит до bsi_hotels_api(): таймаут читается в конструкторе клиента,
     а клиент живёт один на запрос — поставленный после него фильтр опоздал бы. */
  add_filter('bsi_hotels_api_timeout', static fn() => 20);

  $client = bsi_hotels_api();
  if (!$client) {
    wp_send_json_error(['message' => 'Хаб отелей не настроен'], 503);
  }

  try {
    $search = $client->search($hotel_id, [
      'check_in_from' => $from,
      'check_in_to' => $to,
      'nights_from' => $nights,
      'nights_to' => $nights,
    ]);
  } catch (HotelsApiException $e) {
    wp_send_json_error([
      'message' => $e->getCode() === 404
        ? 'Отеля нет у поставщика'
        : 'Не удалось получить цены',
    ], 502);
  }

  /* Календарю нужна одна строка на день: сумма за весь заезд и признак
     мгновенного подтверждения. Дней без предложений в ответе хаба нет —
     их календарь и гасит. */
  $days = [];
  foreach ($search['calendar'] as $day) {
    $check_in = (string) ($day['check_in'] ?? '');
    $price = bsi_hotel_view_price_value($day['price_from'] ?? null);

    if ($check_in === '' || !$price) {
      continue;
    }

    $days[$check_in] = [
      'price' => bsi_hotel_view_price($price),
      'instant' => bsi_hotel_view_price_value($day['instant_price_from'] ?? null) !== null,
    ];
  }

  wp_send_json_success([
    'days' => $days,
    'nights' => $nights,
    'from' => $from,
    'to' => $to,
    /* true — хаб ходил к оператору, false — ответил из своего кеша.
       На экран не идёт, нужно для логов и замеров. */
    'refreshed' => $search['refreshed'],
  ]);
}
