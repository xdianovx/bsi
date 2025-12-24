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

    default:
      wp_send_json_error(['message' => 'Unknown endpoint'], 400);
  }
}