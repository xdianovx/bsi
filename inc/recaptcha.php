<?php

declare(strict_types=1);

/**
 * reCAPTCHA v3 — хелпер для проверки токена на сервере.
 * Ключи задаются в wp-config.php: BSI_RECAPTCHA_SITE_KEY, BSI_RECAPTCHA_SECRET_KEY.
 */

/**
 * Возвращает Site Key для фронтенда или пустую строку, если не задан.
 */
function bsi_recaptcha_site_key(): string
{
  return defined('BSI_RECAPTCHA_SITE_KEY') && BSI_RECAPTCHA_SITE_KEY !== ''
    ? (string) BSI_RECAPTCHA_SITE_KEY
    : '';
}

/**
 * Проверяет, включена ли reCAPTCHA (заданы оба ключа).
 */
function bsi_recaptcha_enabled(): bool
{
  $secret = defined('BSI_RECAPTCHA_SECRET_KEY') ? (string) BSI_RECAPTCHA_SECRET_KEY : '';
  return $secret !== '' && bsi_recaptcha_site_key() !== '';
}

/**
 * Верифицирует токен reCAPTCHA v3 через Google API.
 *
 * @param string $token Токен из g-recaptcha-response (frontend).
 * @return array{success: bool, score: float, 'error-codes': array}
 */
function bsi_recaptcha_verify(string $token): array
{
  $secret = defined('BSI_RECAPTCHA_SECRET_KEY') ? (string) BSI_RECAPTCHA_SECRET_KEY : '';
  if ($secret === '') {
    return ['success' => true, 'score' => 1.0, 'error-codes' => []];
  }

  if ($token === '') {
    return ['success' => false, 'score' => 0.0, 'error-codes' => ['missing-input-response']];
  }

  $response = wp_remote_post(
    'https://www.google.com/recaptcha/api/siteverify',
    [
      'body' => [
        'secret'   => $secret,
        'response' => $token,
      ],
      'timeout' => 10,
    ]
  );

  if (is_wp_error($response)) {
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
      error_log('BSI reCAPTCHA: ' . $response->get_error_message());
    }
    return ['success' => false, 'score' => 0.0, 'error-codes' => ['request-failed']];
  }

  $code = wp_remote_retrieve_response_code($response);
  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);

  if ($code !== 200 || !is_array($data)) {
    return ['success' => false, 'score' => 0.0, 'error-codes' => ['invalid-json']];
  }

  $success = !empty($data['success']);
  $score   = isset($data['score']) ? (float) $data['score'] : 0.0;
  $errors  = isset($data['error-codes']) && is_array($data['error-codes']) ? $data['error-codes'] : [];

  return [
    'success'     => $success,
    'score'       => $score,
    'error-codes' => $errors,
  ];
}

/**
 * Проверяет токен и при неуспехе отправляет JSON error и завершает выполнение.
 * Порог score для v3: 0.5 (ниже — считаем ботом).
 *
 * @param string $token Токен из $_POST['recaptcha_token'].
 */
function bsi_recaptcha_verify_or_die(string $token): void
{
  if (!bsi_recaptcha_enabled()) {
    return;
  }

  $result = bsi_recaptcha_verify($token);

  if (!$result['success']) {
    wp_send_json_error([
      'message' => 'Ошибка проверки безопасности. Попробуйте ещё раз.',
      'errors'  => ['recaptcha' => 'Подтвердите, что вы не робот.'],
    ]);
  }

  $min_score = 0.5;
  if (($result['score'] ?? 0) < $min_score) {
    wp_send_json_error([
      'message' => 'Проверка не пройдена. Попробуйте ещё раз.',
      'errors'  => ['recaptcha' => 'Подтвердите, что вы не робот.'],
    ]);
  }
}
