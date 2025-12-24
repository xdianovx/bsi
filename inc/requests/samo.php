<?php
// inc/samo/ajax/routes.php

add_action('wp_ajax_bsi_samo', 'bsi_samo_ajax');
add_action('wp_ajax_nopriv_bsi_samo', 'bsi_samo_ajax');

function bsi_samo_ajax()
{
  $method = isset($_POST['method']) ? sanitize_key($_POST['method']) : '';

  if (!$method) {
    wp_send_json_error(['message' => 'method required'], 400);
  }

  if (!class_exists('SamoService')) {
    wp_send_json_error(['message' => 'SamoService not loaded'], 500);
  }

  try {
    $api = SamoService::endpoints();

    switch ($method) {
      case 'townfroms': {
        $resp = $api->searchTownFroms(); // <- вернёт обёртку SamoClient
        break;
      }

      case 'states': {
        $town = isset($_POST['TOWNFROMINC']) ? (int) $_POST['TOWNFROMINC'] : 0;
        if (!$town)
          wp_send_json_error(['message' => 'TOWNFROMINC required'], 400);

        $resp = $api->searchStates(['TOWNFROMINC' => $town]);
        break;
      }

      default:
        wp_send_json_error(['message' => 'Unknown endpoint'], 400);
    }

    // 1) если SamoClient сказал ok=false — отдаём ошибку
    if (is_array($resp) && isset($resp['ok']) && !$resp['ok']) {
      wp_send_json_error([
        'message' => $resp['error'] ?? 'SAMO error',
        'url' => $resp['url'] ?? null,
        'body' => $resp['body'] ?? null,
      ], 500);
    }

    // 2) unwrap: в JS мы хотим получить “чистый JSON Самотура”
    $payload = $resp['data'] ?? $resp;

    // 3) если Самотур вернул error внутри JSON — тоже ошибка
    if (is_array($payload) && isset($payload['error'])) {
      wp_send_json_error(['message' => $payload['error'], 'raw' => $payload], 500);
    }

    wp_send_json_success($payload);

  } catch (Throwable $e) {
    wp_send_json_error(['message' => $e->getMessage()], 500);
  }
}