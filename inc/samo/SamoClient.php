<?php

class SamoClient
{
  private string $baseUrl;
  private string $token;
  private string $samoAction;
  private string $version;
  private string $type;

  public function __construct(array $config)
  {
    $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
    $this->token = (string) ($config['token'] ?? '');
    $this->samoAction = (string) ($config['samo_action'] ?? 'api');
    $this->version = (string) ($config['version'] ?? '1.0');
    $this->type = (string) ($config['type'] ?? 'json');

    if (!$this->baseUrl) {
      throw new Exception('SAMO: base_url is empty');
    }
    if (!$this->token) {
      throw new Exception('SAMO: token is empty');
    }
  }

  public function request(string $action, array $params = []): array
  {
    $responseType = $params['type'] ?? $this->type;
    unset($params['type']);

    $forceRefresh = isset($params['_force_refresh']) && $params['_force_refresh'];
    unset($params['_force_refresh']);

    $query = array_merge([
      'samo_action' => $this->samoAction,
      'version' => $this->version,
      'type' => $responseType,
      'action' => $action,
      'oauth_token' => $this->token,
    ], $params);

    $query = array_filter($query, static fn($v) => $v !== null && $v !== '');

    $cacheKey = 'samo_' . md5($action . '_' . serialize($query));

    $cacheExpiration = $this->getCacheExpiration($action);

    if (!$forceRefresh) {
      $cached = get_transient($cacheKey);
      if ($cached !== false) {
        return $cached;
      }
    }

    $url = $this->baseUrl . '?' . http_build_query($query);

    $acceptHeader = $responseType === 'xml' ? 'application/xml' : 'application/json';

    $res = wp_remote_get($url, [
      'timeout' => 25,
      'headers' => ['Accept' => $acceptHeader],
    ]);

    if (is_wp_error($res)) {
      return [
        'ok' => false,
        'error' => $res->get_error_message(),
        'url' => $url,
      ];
    }
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);

    if ($code >= 400) {
      return [
        'ok' => false,
        'error' => 'HTTP ' . $code,
        'body' => $body,
        'url' => $url,
      ];
    }

    if ($responseType === 'xml') {
      $data = $this->parseXmlResponse($body);
      if ($data === null) {
        return [
          'ok' => false,
          'error' => 'Invalid XML response',
          'body' => $body,
          'url' => $url,
        ];
      }
      $result = [
        'ok' => true,
        'data' => $data,
        'url' => $url,
      ];

      set_transient($cacheKey, $result, $cacheExpiration);

      return $result;
    }

    $json = json_decode($body, true);

    if (!is_array($json)) {
      return [
        'ok' => false,
        'error' => 'Invalid JSON response',
        'body' => $body,
        'url' => $url,
      ];
    }

    $result = [
      'ok' => true,
      'data' => $json,
      'url' => $url,
    ];

    set_transient($cacheKey, $result, $cacheExpiration);

    return $result;
  }

  private function getCacheExpiration(string $action): int
  {
    if (strpos($action, 'PRICES') !== false) {
      return 30 * MINUTE_IN_SECONDS;
    }

    if (strpos($action, 'NIGHTS') !== false) {
      return HOUR_IN_SECONDS;
    }

    if (strpos($action, 'HOTELS') !== false) {
      return HOUR_IN_SECONDS;
    }

    return HOUR_IN_SECONDS;
  }

  private function parseXmlResponse(string $xml): ?array
  {
    if (empty($xml)) {
      return null;
    }

    libxml_use_internal_errors(true);
    $xmlObject = simplexml_load_string($xml);
    libxml_clear_errors();

    if ($xmlObject === false) {
      return null;
    }

    return $this->xmlToArray($xmlObject);
  }

  private function xmlToArray(\SimpleXMLElement $xml): array
  {
    $array = [];

    foreach ($xml->attributes() as $key => $value) {
      $array['@' . $key] = (string) $value;
    }

    foreach ($xml->children() as $key => $child) {
      $value = $this->xmlToArray($child);

      if (isset($array[$key])) {
        if (!is_array($array[$key]) || !isset($array[$key][0])) {
          $array[$key] = [$array[$key]];
        }
        $array[$key][] = $value;
      } else {
        $array[$key] = $value;
      }
    }

    if (empty($array)) {
      return (string) $xml;
    }

    return $array;
  }
}