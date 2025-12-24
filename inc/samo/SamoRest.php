<?php

class SamoRest
{
  public function request(string $method, array $params = []): array
  {
    // Базовые параметры
    $baseParams = [
      'samo_action' => SAMO_ACTION,
      'version' => SAMO_API_VERSION,
      'type' => SAMO_API_TYPE,
      'action' => $method, // SearchTour_ALL, SearchTour_PRICES ...
      'oauth_token' => SAMO_OAUTH_TOKEN,
    ];

    $query = array_merge($baseParams, $params);

    $url = SAMO_API_URL . '?' . http_build_query($query);

    $response = wp_remote_get($url, [
      'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
      return [
        'error' => true,
        'message' => $response->get_error_message(),
      ];
    }

    $body = wp_remote_retrieve_body($response);

    if (!$body) {
      return [
        'error' => true,
        'message' => 'Пустой ответ самотура',
      ];
    }

    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return [
        'error' => true,
        'message' => 'Кривой JSON',
      ];
    }

    return $data;
  }
}