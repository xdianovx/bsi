<?php
/**
 * Cache Service
 * 
 * Универсальный сервис для работы с кэшированием через WordPress Transients API.
 * Используется для кэширования данных из внешних API (Samotour и др.)
 * 
 * @package BSI
 * @since 1.0.0
 */

class CacheService
{
  /**
   * Префикс для всех ключей кэша
   */
  const PREFIX = 'bsi_cache_';

  /**
   * Время кэширования по умолчанию (3 часа)
   */
  const DEFAULT_EXPIRATION = 3 * HOUR_IN_SECONDS;

  /**
   * Получить значение из кэша
   * 
   * @param string $key Ключ кэша
   * @param string $group Группа кэша (например, 'tour_prices', 'hotel_prices')
   * @return mixed|false Значение из кэша или false, если не найдено/протухло
   */
  public static function get(string $key, string $group = 'default')
  {
    $cache_key = self::buildKey($key, $group);
    return get_transient($cache_key);
  }

  /**
   * Сохранить значение в кэш
   * 
   * @param string $key Ключ кэша
   * @param mixed $value Значение для сохранения
   * @param int $expiration Время жизни в секундах (по умолчанию 3 часа)
   * @param string $group Группа кэша
   * @return bool Успешность операции
   */
  public static function set(string $key, $value, int $expiration = self::DEFAULT_EXPIRATION, string $group = 'default'): bool
  {
    $cache_key = self::buildKey($key, $group);
    return set_transient($cache_key, $value, $expiration);
  }

  /**
   * Получить из кэша или выполнить callback и сохранить результат
   * 
   * @param string $key Ключ кэша
   * @param callable $callback Функция для получения данных, если кэш пуст
   * @param int $expiration Время жизни в секундах
   * @param string $group Группа кэша
   * @return mixed Значение из кэша или результат callback
   */
  public static function remember(string $key, callable $callback, int $expiration = self::DEFAULT_EXPIRATION, string $group = 'default')
  {
    $cached = self::get($key, $group);

    if ($cached !== false) {
      return $cached;
    }

    $value = $callback();

    if ($value !== null && $value !== false) {
      self::set($key, $value, $expiration, $group);
    }

    return $value;
  }

  /**
   * Удалить значение из кэша
   * 
   * @param string $key Ключ кэша
   * @param string $group Группа кэша
   * @return bool Успешность операции
   */
  public static function forget(string $key, string $group = 'default'): bool
  {
    $cache_key = self::buildKey($key, $group);
    return delete_transient($cache_key);
  }

  /**
   * Очистить все значения в группе
   * 
   * @param string $group Группа кэша для очистки
   * @return int Количество удаленных записей
   */
  public static function flush(string $group): int
  {
    global $wpdb;

    $pattern = self::PREFIX . $group . '_%';
    $transient_pattern = '_transient_' . $pattern;
    $timeout_pattern = '_transient_timeout_' . $pattern;

    $deleted = $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $transient_pattern,
        $timeout_pattern
      )
    );

    return (int) $deleted;
  }

  /**
   * Построить полный ключ кэша
   * 
   * @param string $key Базовый ключ
   * @param string $group Группа кэша
   * @return string Полный ключ с префиксом и группой
   */
  private static function buildKey(string $key, string $group): string
  {
    return self::PREFIX . $group . '_' . $key;
  }

  /**
   * Получить информацию о кэше (для отладки)
   * 
   * @param string $group Группа кэша
   * @return array Статистика по кэшу группы
   */
  public static function getStats(string $group = ''): array
  {
    global $wpdb;

    $pattern = self::PREFIX . ($group ? $group . '_%' : '%');
    $transient_pattern = '_transient_' . $pattern;

    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
        $transient_pattern
      ),
      ARRAY_A
    );

    $stats = [
      'total' => count($results),
      'group' => $group ?: 'all',
      'items' => [],
    ];

    foreach ($results as $row) {
      $key = str_replace('_transient_' . self::PREFIX, '', $row['option_name']);
      $stats['items'][$key] = strlen($row['option_value']);
    }

    return $stats;
  }
}
