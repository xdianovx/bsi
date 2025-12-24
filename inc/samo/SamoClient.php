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

  /**
   * Универсальный вызов API: action=SearchTour_ALL и т.д.
   */
  public function request(string $action, array $params = []): array
  {
    $query = array_merge([
      'samo_action' => $this->samoAction,
      'version' => $this->version,
      'type' => $this->type,
      'action' => $action,
      'oauth_token' => $this->token,
    ], $params);

    // удаляем пустые параметры (важно, Samo иногда ругается на пустые)
    $query = array_filter($query, static fn($v) => $v !== null && $v !== '');

    $url = $this->baseUrl . '?' . http_build_query($query);

    $res = wp_remote_get($url, [
      'timeout' => 25,
      'headers' => ['Accept' => 'application/json'],
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

    $json = json_decode($body, true);

    if ($code >= 400) {
      return [
        'ok' => false,
        'error' => 'HTTP ' . $code,
        'body' => $body,
        'url' => $url,
      ];
    }

    if (!is_array($json)) {
      return [
        'ok' => false,
        'error' => 'Invalid JSON response',
        'body' => $body,
        'url' => $url,
      ];
    }

    return [
      'ok' => true,
      'data' => $json,
      'url' => $url,
    ];
  }
}