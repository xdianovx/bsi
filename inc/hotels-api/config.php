<?php

/**
 * Конфиг клиента BSIHOTELS API.
 *
 * Источники по приоритету:
 *   1. Константы в wp-config.php (BSI_HOTELS_API_URL, BSI_HOTELS_API_TIMEOUT)
 *   2. Переменные окружения
 *   3. Файл .env в корне темы (для локальной разработки, см. .env.example)
 */

function bsi_hotels_api_env(string $key): ?string
{
  if (defined($key)) {
    $value = constant($key);
    return $value === '' ? null : (string) $value;
  }

  $fromEnv = getenv($key);
  if ($fromEnv !== false && $fromEnv !== '') {
    return (string) $fromEnv;
  }

  static $dotenv = null;
  if ($dotenv === null) {
    $dotenv = bsi_hotels_api_read_dotenv(get_template_directory() . '/.env');
  }

  return $dotenv[$key] ?? null;
}

/**
 * Минимальный парсер .env: KEY=VALUE, строки с # игнорируются.
 */
function bsi_hotels_api_read_dotenv(string $path): array
{
  if (!is_readable($path)) {
    return [];
  }

  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if ($lines === false) {
    return [];
  }

  $result = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
      continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $result[trim($key)] = trim(trim($value), "\"'");
  }

  return $result;
}

function bsi_hotels_api_config(): array
{
  return [
    'base_url' => bsi_hotels_api_env('BSI_HOTELS_API_URL') ?? '',
    'timeout' => (int) (bsi_hotels_api_env('BSI_HOTELS_API_TIMEOUT') ?? 15),
  ];
}
