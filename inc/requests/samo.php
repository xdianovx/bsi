<?php
require_once get_template_directory() . '/inc/services/CacheService.php';

add_action('wp_ajax_bsi_samo', 'samo_ajax');
add_action('wp_ajax_nopriv_bsi_samo', 'samo_ajax');

function samo_ajax()
{
  $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';

  $send = function ($resp) {
    if (is_array($resp) && isset($resp['ok']) && !$resp['ok']) {
      wp_send_json_error([
        'message' => $resp['error'] ?? 'SAMO error',
        'url' => $resp['url'] ?? '',
        'body' => $resp['body'] ?? '',
      ], 500);
    }

    if (is_array($resp) && isset($resp['data'])) {
      wp_send_json_success($resp['data']);
    }

    wp_send_json_success($resp);
  };

  switch ($method) {
    case 'townfroms':
      return $send(SamoService::endpoints()->searchTownFroms());

    case 'states':
      $town = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
      if (!$town)
        wp_send_json_error(['message' => 'TOWNFROMINC required'], 400);
      return $send(SamoService::endpoints()->searchStates(['TOWNFROMINC' => $town]));

    case 'hotel_states':
      return $send(SamoService::endpoints()->searchHotelStates([
        'STATEFROM' => 2,
      ]));

    case 'hotel_hotels':
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      if (!$stateInc)
        wp_send_json_error(['message' => 'STATEINC required'], 400);

      return $send(SamoService::endpoints()->searchHotelHotels([
        'STATEFROM' => 2,
        'STATEINC' => $stateInc,
      ]));

    case 'excursion_states':
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 1;

      return $send(SamoService::endpoints()->searchExcursionStates([
        'TOWNFROMINC' => $townFromInc,
      ]));

    case 'excursion_tours':
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 1;
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;

      if (!$stateInc) {
        wp_send_json_error(['message' => 'STATEINC required'], 400);
      }

      return $send(SamoService::endpoints()->searchExcursionTours([
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
      ]));

    case 'excursion_hotels':
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $tours = isset($_POST['TOURS']) ? (int) $_POST['TOURS'] : 0;

      if (!$townFromInc || !$stateInc || !$tours) {
        wp_send_json_error(['message' => 'TOWNFROMINC, STATEINC and TOURS required'], 400);
      }

      $params = [
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
        'TOURS' => $tours,
      ];
      
      // Строим ключ кеша на основе параметров
      $cache_key = 'excursion_hotels_' . md5(json_encode($params));
      
      // Кешируем запрос
      $result = CacheService::remember(
        $cache_key,
        function() use ($params) {
          return SamoService::endpoints()->searchExcursionHotels($params);
        },
        3 * HOUR_IN_SECONDS,
        'samotour'
      );
      
      return $send($result);

    case 'excursion_nights':
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $tours = isset($_POST['TOURS']) ? (int) $_POST['TOURS'] : 0;

      if (!$townFromInc || !$stateInc || !$tours) {
        wp_send_json_error(['message' => 'TOWNFROMINC, STATEINC and TOURS required'], 400);
      }

      $params = [
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
        'TOURS' => $tours,
      ];

      $forceRefresh = isset($_POST['_force_refresh']) && $_POST['_force_refresh'];
      
      // Строим ключ кеша на основе параметров
      $cache_key = 'excursion_nights_' . md5(json_encode($params));
      
      if ($forceRefresh) {
        // Очищаем кеш при force_refresh
        CacheService::forget($cache_key, 'samotour');
      }
      
      // Кешируем запрос
      $result = CacheService::remember(
        $cache_key,
        function() use ($params) {
          return SamoService::endpoints()->searchExcursionNights($params);
        },
        3 * HOUR_IN_SECONDS,
        'samotour'
      );
      
      return $send($result);

    case 'excursion_prices':
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $tours = isset($_POST['TOURS']) ? (int) $_POST['TOURS'] : 0;
      $checkinBeg = isset($_POST['CHECKIN_BEG']) ? sanitize_text_field($_POST['CHECKIN_BEG']) : '';
      $checkinEnd = isset($_POST['CHECKIN_END']) ? sanitize_text_field($_POST['CHECKIN_END']) : '';
      $nightsFrom = isset($_POST['NIGHTS_FROM']) ? (int) $_POST['NIGHTS_FROM'] : 0;
      $nightsTill = isset($_POST['NIGHTS_TILL']) ? (int) $_POST['NIGHTS_TILL'] : 0;
      $adult = isset($_POST['ADULT']) ? (int) $_POST['ADULT'] : 2;
      $child = isset($_POST['CHILD']) ? (int) $_POST['CHILD'] : 0;
      $currency = isset($_POST['CURRENCY']) ? (int) $_POST['CURRENCY'] : 1;

      if (!$townFromInc || !$stateInc || !$tours) {
        wp_send_json_error(['message' => 'TOWNFROMINC, STATEINC and TOURS required'], 400);
      }

      $params = [
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
        'TOURS' => $tours,
        'ADULT' => $adult,
        'CHILD' => $child,
        'CURRENCY' => $currency,
      ];

      if ($checkinBeg) {
        $params['CHECKIN_BEG'] = $checkinBeg;
      }
      if ($checkinEnd) {
        $params['CHECKIN_END'] = $checkinEnd;
      }
      if ($nightsFrom) {
        $params['NIGHTS_FROM'] = $nightsFrom;
      }
      if ($nightsTill) {
        $params['NIGHTS_TILL'] = $nightsTill;
      }

      $forceRefresh = isset($_POST['_force_refresh']) && $_POST['_force_refresh'];
      
      // Строим ключ кеша на основе параметров
      $cache_key = 'excursion_prices_' . md5(json_encode($params));
      
      if ($forceRefresh) {
        CacheService::forget($cache_key, 'samotour');
      }

      // Кешируем запрос
      $result = CacheService::remember(
        $cache_key,
        function() use ($params) {
          return SamoService::endpoints()->searchExcursionPrices($params);
        },
        3 * HOUR_IN_SECONDS,
        'samotour'
      );
      
      return $send($result);

    case 'excursion_all':
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $tours = isset($_POST['TOURS']) ? (int) $_POST['TOURS'] : 0;
      $forceRefresh = isset($_POST['_force_refresh']) && $_POST['_force_refresh'];

      if (!$townFromInc || !$stateInc || !$tours) {
        wp_send_json_error(['message' => 'TOWNFROMINC, STATEINC and TOURS required'], 400);
      }
      $params = [
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
        'TOURS' => $tours,
      ];
      
      // Строим ключ кеша на основе параметров
      $cache_key = 'excursion_all_' . md5(json_encode($params));
      
      if ($forceRefresh) {
        // Очищаем кеш при force_refresh
        CacheService::forget($cache_key, 'samotour');
      }
      
      // Кешируем запрос
      $result = CacheService::remember(
        $cache_key,
        function() use ($params) {
          return SamoService::endpoints()->searchExcursionAll($params);
        },
        3 * HOUR_IN_SECONDS,
        'samotour'
      );
      
      return $send($result);

    case 'tickets_transporttypes':
      $params = [];
      if (isset($_POST['WITH_CHARTER'])) {
        $params['WITH_CHARTER'] = (int) $_POST['WITH_CHARTER'];
      }
      if (isset($_POST['WITH_REGULAR'])) {
        $params['WITH_REGULAR'] = (int) $_POST['WITH_REGULAR'];
      }
      return $send(SamoService::endpoints()->ticketsTransportTypes($params));

    case 'tickets_sources':
      $params = [];
      if (isset($_POST['SUGGEST'])) {
        $params['SUGGEST'] = sanitize_text_field($_POST['SUGGEST']);
      }
      if (isset($_POST['WITH_CHARTER'])) {
        $params['WITH_CHARTER'] = (int) $_POST['WITH_CHARTER'];
      }
      if (isset($_POST['WITH_REGULAR'])) {
        $params['WITH_REGULAR'] = (int) $_POST['WITH_REGULAR'];
      }
      if (isset($_POST['TRANSPORTTYPE'])) {
        $params['TRANSPORTTYPE'] = (int) $_POST['TRANSPORTTYPE'];
      }
      return $send(SamoService::endpoints()->ticketsSources($params));

    case 'tickets_targets':
      $params = [];
      if (isset($_POST['SUGGEST'])) {
        $params['SUGGEST'] = sanitize_text_field($_POST['SUGGEST']);
      }
      if (isset($_POST['SOURCE'])) {
        $params['SOURCE'] = sanitize_text_field($_POST['SOURCE']);
      }
      if (isset($_POST['WITH_CHARTER'])) {
        $params['WITH_CHARTER'] = (int) $_POST['WITH_CHARTER'];
      }
      if (isset($_POST['WITH_REGULAR'])) {
        $params['WITH_REGULAR'] = (int) $_POST['WITH_REGULAR'];
      }
      if (isset($_POST['TRANSPORTTYPE'])) {
        $params['TRANSPORTTYPE'] = (int) $_POST['TRANSPORTTYPE'];
      }
      return $send(SamoService::endpoints()->ticketsTargets($params));

    case 'tickets_all':
      $params = [];
      if (isset($_POST['SOURCE'])) {
        $params['SOURCE'] = sanitize_text_field($_POST['SOURCE']);
      }
      if (isset($_POST['TARGET'])) {
        $params['TARGET'] = sanitize_text_field($_POST['TARGET']);
      }
      if (isset($_POST['FREIGHTBACK'])) {
        $params['FREIGHTBACK'] = (int) $_POST['FREIGHTBACK'];
      }
      if (isset($_POST['WITH_CHARTER'])) {
        $params['WITH_CHARTER'] = (int) $_POST['WITH_CHARTER'];
      }
      if (isset($_POST['WITH_REGULAR'])) {
        $params['WITH_REGULAR'] = (int) $_POST['WITH_REGULAR'];
      }
      if (isset($_POST['TRANSPORTTYPE'])) {
        $params['TRANSPORTTYPE'] = (int) $_POST['TRANSPORTTYPE'];
      }
      return $send(SamoService::endpoints()->ticketsAll($params));

    case 'crosstour_states':
      return $send(SamoService::endpoints()->searchCrosstourStates());

    case 'crosstour_tours':
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : BSI_CROSSTOUR_TOWNFROM;
      if (!$stateInc) {
        wp_send_json_error(['message' => 'STATEINC required'], 400);
      }
      return $send(SamoService::endpoints()->searchCrosstourTours([
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
      ]));

    case 'crosstour_hotels':
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : BSI_CROSSTOUR_TOWNFROM;
      if (!$stateInc) {
        wp_send_json_error(['message' => 'STATEINC required'], 400);
      }
      return $send(SamoService::endpoints()->searchCrosstourHotels([
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
      ]));

    case 'crosstour_nights':
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : BSI_CROSSTOUR_TOWNFROM;
      if (!$stateInc) {
        wp_send_json_error(['message' => 'STATEINC required'], 400);
      }
      return $send(SamoService::endpoints()->searchCrosstourNights([
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
      ]));

    case 'crosstour_all':
    case 'crosstour_prices':
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $tours = isset($_POST['TOURS']) ? (int) $_POST['TOURS'] : 0;
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : BSI_CROSSTOUR_TOWNFROM;
      if (!$stateInc || !$tours) {
        wp_send_json_error(['message' => 'STATEINC and TOURS required'], 400);
      }
      $params = [
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
        'TOURS' => $tours,
        'ADULT' => isset($_POST['ADULT']) ? (int) $_POST['ADULT'] : 2,
        'CHILD' => isset($_POST['CHILD']) ? (int) $_POST['CHILD'] : 0,
        'CURRENCY' => isset($_POST['CURRENCY']) ? (int) $_POST['CURRENCY'] : 1,
        'TOWNS_ANY' => 1,
        'STARS_ANY' => 1,
        'HOTELS_ANY' => 1,
        'MEALS_ANY' => 1,
        'ROOMS_ANY' => 1,
        'FREIGHT' => 1,
      ];
      foreach (['CHECKIN_BEG', 'CHECKIN_END'] as $k) {
        if (!empty($_POST[$k])) {
          $params[$k] = sanitize_text_field(wp_unslash($_POST[$k]));
        }
      }
      foreach (['NIGHTS_FROM', 'NIGHTS_TILL'] as $k) {
        if (!empty($_POST[$k])) {
          $params[$k] = (int) $_POST[$k];
        }
      }
      $result = $method === 'crosstour_prices'
        ? SamoService::endpoints()->searchCrosstourPrices($params)
        : SamoService::endpoints()->searchCrosstourAll($params);
      return $send($result);

    case 'crosstour_event':
      $eventId = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
      if (!$eventId) {
        wp_send_json_error(['message' => 'event_id required'], 400);
      }
      $force = isset($_POST['_force_refresh']) && $_POST['_force_refresh'];

      // ВРЕМЕННАЯ ДИАГНОСТИКА: ?debug=1 в запросе → вернём цепочку разбора в _debug.
      $debug = null;
      if (!empty($_POST['debug'])) {
        $bu = function_exists('get_field') ? (string) get_field('tour_booking_url', $eventId) : '';
        $src = function_exists('get_field') ? (string) get_field('event_data_source', $eventId) : '';
        $enabled = function_exists('bsi_crosstour_event_enabled') ? bsi_crosstour_event_enabled($eventId) : null;
        $ref = function_exists('bsi_crosstour_event_ref') ? bsi_crosstour_event_ref($eventId) : null;
        $exc_params = function_exists('get_tour_excursion_params') ? get_tour_excursion_params($eventId) : null;
        $pl = (class_exists('PriceLoaderService')) ? PriceLoaderService::getTourPrice($eventId) : null;
        $debug = [
          'event_id' => $eventId,
          'event_data_source' => $src,
          'enabled' => $enabled,
          'tour_booking_url' => $bu,
          'ref' => $ref,
          'excursion_params' => $exc_params,
          'priceLoader_getTourPrice' => $pl,
        ];
      }

      $data = function_exists('bsi_crosstour_event_data')
        ? bsi_crosstour_event_data($eventId, $force)
        : null;
      if ($data === null) {
        wp_send_json_success(['samo' => false, '_debug' => $debug]);
      }
      wp_send_json_success(array_merge(['samo' => true, '_debug' => $debug], $data));

    case 'crosstour_batch':
      $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
      $ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, 50);
      $prices = [];
      $debug_batch = (current_user_can('manage_options') && !empty($_POST['debug'])) ? [] : null;
      foreach ($ids as $id) {
        if (!$id || !function_exists('bsi_crosstour_event_data')) {
          continue;
        }
        $data = bsi_crosstour_event_data($id);
        if ($debug_batch !== null) {
          $debug_batch[$id] = [
            'samo'      => $data !== null ? 'ok' : 'null',
            'price_rub' => $data['offer']['price_rub'] ?? 'missing',
            'nights'    => $data['offer']['nights'] ?? 'missing',
            'ref_state' => $data['ref']['STATEINC'] ?? null,
            'ref_tour'  => $data['ref']['TOURINC'] ?? null,
            'ref_town'  => $data['ref']['TOWNFROMINC'] ?? null,
          ];
        }
        if ($data && !empty($data['offer']) && !empty($data['offer']['price_rub'])) {
          $prices[$id] = [
            'price_rub' => (int) $data['offer']['price_rub'],
            'price_original' => $data['offer']['price_original'] ?? null,
            'price_currency' => $data['offer']['price_currency'] ?? null,
          ];
        }
      }
      wp_send_json_success(['prices' => $prices, '_debug' => $debug_batch]);

    default:
      wp_send_json_error(['message' => 'Unknown endpoint'], 400);
  }
}