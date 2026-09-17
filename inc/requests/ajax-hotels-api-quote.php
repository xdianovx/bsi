<?php

declare(strict_types=1);

/**
 * AJAX: цена конкретного заезда в отеле из хаба BSIHOTELS.
 *
 * Календарь на странице отеля показывает цены только за те дни, которые хаб
 * успел прогреть. Остальные дни не заняты — про них просто ещё не спрашивали,
 * поэтому клик по такому дню приходит сюда, а мы просим хаб посчитать заезд.
 * На холодной дате он ходит к поставщику: это секунды, но не пустой ответ.
 *
 * Браузер не может обратиться к хабу напрямую — он живёт во внутренней сети,
 * а его адрес лежит в конфиге темы.
 */

add_action('wp_ajax_bsi_hotels_api_quote', 'bsi_hotels_api_quote_ajax');
add_action('wp_ajax_nopriv_bsi_hotels_api_quote', 'bsi_hotels_api_quote_ajax');

function bsi_hotels_api_quote_ajax(): void
{
  $hotel_id = isset($_POST['hotel']) ? absint($_POST['hotel']) : 0;
  $check_in = sanitize_text_field((string) ($_POST['check_in'] ?? ''));
  $nights = isset($_POST['nights']) ? absint($_POST['nights']) : 0;

  if (!$hotel_id || $nights < 1 || $nights > 30 || !preg_match('~^\d{4}-\d{2}-\d{2}$~', $check_in)) {
    wp_send_json_error(['message' => 'Неверные параметры запроса'], 400);
  }

  /* Холодная дата уходит к поставщику, и ответ занимает секунды. Ждём дольше
     обычного, но с запасом до предела веб-сервера.

     Фильтр стоит до bsi_hotels_api(): таймаут читается в конструкторе клиента,
     а клиент живёт один на запрос — поставленный после него фильтр опоздал бы. */
  add_filter('bsi_hotels_api_timeout', static fn() => 20);

  $client = bsi_hotels_api();
  if (!$client) {
    wp_send_json_error(['message' => 'Хаб отелей не настроен'], 503);
  }

  try {
    $quote = $client->quote($hotel_id, $check_in, $nights);
  } catch (HotelsApiException $e) {
    // 501 — у поставщика нет тарифов на такую длительность, это не сбой.
    $code = $e->getCode() === 501 ? 200 : 502;

    wp_send_json_error([
      'message' => $e->getCode() === 501
        ? 'На такую длительность тарифов нет'
        : 'Не удалось получить цены',
    ], $code);
  }

  $rooms = [];
  foreach ((array) ($quote['quotes'] ?? []) as $row) {
    // Неполный расчёт значит «на часть ночей цены нет» — показывать нечего.
    if (empty($row['complete']) || empty($row['available'])) {
      continue;
    }

    $rooms[] = [
      'room' => (string) ($row['room_type_id'] ?? ''),
      'meal' => (string) ($row['meal']['code'] ?? ''),
      'mealLabel' => bsi_hotel_view_meal_label((string) ($row['meal']['code'] ?? '')),
      'placementLabel' => bsi_hotel_view_placement_label((string) ($row['placement'] ?? '')),
      'price' => bsi_hotel_view_price([
        'amount' => (float) ($row['total'] ?? 0),
        'currency' => (string) ($row['currency'] ?? ''),
      ]),
      'priceValue' => (float) ($row['total'] ?? 0),
      /* Подтверждение считается по самой неуверенной ночи заезда: одна ночь
         под запрос — всё проживание под запрос. */
      'confirmation' => (string) ($row['confirmation'] ?? ''),
      'confirmationLabel' => bsi_hotel_view_confirmation_label((string) ($row['confirmation'] ?? '')),
    ];
  }

  usort($rooms, static fn(array $a, array $b) => $a['priceValue'] <=> $b['priceValue']);

  wp_send_json_success([
    'quotes' => $rooms,
    'nights' => (int) ($quote['nights'] ?? $nights),
    'checkIn' => (string) ($quote['check_in'] ?? $check_in),
    'booking' => (string) ($quote['booking']['url'] ?? ''),
    'pricedBy' => (string) ($quote['priced_by'] ?? ''),
    /* true — хаб ходил к оператору, false — ответил из своего кеша.
       На экран не идёт, нужно для логов и замеров. */
    'refreshed' => !empty($quote['refreshed']),
  ]);
}
