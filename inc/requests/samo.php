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

    default:
      wp_send_json_error(['message' => 'Unknown endpoint'], 400);
  }
}