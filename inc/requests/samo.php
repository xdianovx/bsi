<?php
// inc/samo/ajax/routes.php
add_action('wp_ajax_bsi_samo', 'samo_ajax');
add_action('wp_ajax_nopriv_bsi_samo', 'samo_ajax');

function samo_ajax()
{
  $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '';

  // helper чтобы красиво отдавать ошибки/успех
  $send = function ($resp) {
    if (is_array($resp) && isset($resp['ok']) && !$resp['ok']) {
      wp_send_json_error([
        'message' => $resp['error'] ?? 'SAMO error',
        'url' => $resp['url'] ?? '',
        'body' => $resp['body'] ?? '',
      ], 500);
    }

    // если SamoClient возвращает ['ok'=>true,'data'=>...]
    if (is_array($resp) && isset($resp['data'])) {
      wp_send_json_success($resp['data']);
    }

    wp_send_json_success($resp);
  };

  switch ($method) {
    // Туры (то что уже было)
    case 'townfroms':
      return $send(SamoService::endpoints()->searchTownFroms());

    case 'states':
      $town = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
      if (!$town)
        wp_send_json_error(['message' => 'TOWNFROMINC required'], 400);
      return $send(SamoService::endpoints()->searchStates(['TOWNFROMINC' => $town]));

    // Отели
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

    // Экскурсионные туры
    case 'excursion_hotels':
      $townFromInc = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
      $stateInc = isset($_POST['STATEINC']) ? (int) $_POST['STATEINC'] : 0;
      $tours = isset($_POST['TOURS']) ? (int) $_POST['TOURS'] : 0;

      if (!$townFromInc || !$stateInc || !$tours) {
        wp_send_json_error(['message' => 'TOWNFROMINC, STATEINC and TOURS required'], 400);
      }

      return $send(SamoService::endpoints()->searchExcursionHotels([
        'TOWNFROMINC' => $townFromInc,
        'STATEINC' => $stateInc,
        'TOURS' => $tours,
      ]));

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

      // Проверяем флаг принудительного обновления кэша
      $forceRefresh = isset($_POST['_force_refresh']) && $_POST['_force_refresh'];
      if ($forceRefresh) {
        $params['_force_refresh'] = true;
      }

      return $send(SamoService::endpoints()->searchExcursionNights($params));

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

      // Проверяем флаг принудительного обновления кэша
      $forceRefresh = isset($_POST['_force_refresh']) && $_POST['_force_refresh'];
      if ($forceRefresh) {
        $params['_force_refresh'] = true;
      }

      return $send(SamoService::endpoints()->searchExcursionPrices($params));

    default:
      wp_send_json_error(['message' => 'Unknown endpoint'], 400);
  }
}