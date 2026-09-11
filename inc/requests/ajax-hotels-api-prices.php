<?php

/**
 * Догрузка цен каталога отелей.
 *
 * Хаб помечает отель `prices_status`:
 *   fresh    — поставщика спрашивали в пределах часа, цена окончательная;
 *   updating — отель в очереди на обновление или грузится прямо сейчас;
 *   stale    — цены старые и никто их не грузит (поставщик ответил ошибкой).
 *
 * Ждать имеет смысл только `updating`. Очередь у хаба одна, отель занимает
 * 5-10 секунд, и при длинной очереди своей цены он может ждать пару минут —
 * поэтому фронт опрашивает долго, но дёшево: `/v1/hotels/prices` отдаёт цены
 * названных отелей и ничего больше, ответ в двести раз легче страницы каталога.
 *
 * Клиент — js/modules/ajax/hotels-prices.js.
 */

add_action('wp_ajax_bsi_hotels_api_prices', 'bsi_hotels_api_prices_ajax');
add_action('wp_ajax_nopriv_bsi_hotels_api_prices', 'bsi_hotels_api_prices_ajax');

function bsi_hotels_api_prices_ajax(): void
{
  $raw = (array) ($_POST['id'] ?? []);
  $ids = array_slice(
    array_values(array_unique(array_filter(array_map('absint', $raw)))),
    0,
    100
  );

  if (!$ids) {
    wp_send_json_error(['message' => 'Не переданы отели'], 400);
  }

  /* Список отобран по мгновенному подтверждению — цены считаем по тем же
     ночам, иначе в карточку приедет цена варианта под запрос. */
  $instant = !empty($_POST['instant']);

  $client = bsi_hotels_api();
  if (!$client) {
    wp_send_json_error(['message' => 'Хаб отелей не настроен'], 503);
  }

  try {
    $items = $client->prices($ids, $instant);
  } catch (HotelsApiException $e) {
    wp_send_json_error(['message' => 'Не удалось обновить цены'], 502);
  }

  $prices = [];

  /* Сколько карточек за раз дозапрашиваем по прайсу: каждая — отдельный запрос
     карточки отеля, и пачку надо держать в разумных рамках. */
  $price_list_budget = 12;

  foreach ($items as $id => $item) {
    $status = (string) ($item['prices_status'] ?? '');
    $price = bsi_hotels_api_format_price($item['price_from'] ?? null);

    $price_list = '';

    /* Купить нечего, но цена может быть в тарифах: оператор держит
       стоп-продажу на всё прогретое окно. Тогда показываем цену прайса с
       меткой «Мало мест» — за окном места обычно находятся. Пока отель в
       очереди на обновление, ждём: цена вот-вот приедет нормальным путём. */
    if ($price === '' && $status !== 'updating' && $price_list_budget > 0) {
      $price_list_budget--;
      $price_list = bsi_hotels_api_format_price($client->priceListFrom((int) $id));
    }

    $prices[$id] = [
      'price' => $price,
      'priceList' => $price_list,
      'instantPrice' => bsi_hotels_api_format_price($item['instant_price_from'] ?? null),
      'status' => $status,
      /* Ждать дальше или остановиться — решает фронт по этому флагу,
         чтобы правила статусов жили в одном месте. */
      'waiting' => $status === 'updating',
    ];
  }

  wp_send_json_success(['prices' => $prices]);
}
