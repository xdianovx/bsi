<?php
/**
 * Price Loader Service
 * 
 * Сервис для загрузки цен туров и отелей из Samotour API с кэшированием.
 * Поддерживает как единичную, так и пакетную загрузку цен.
 * 
 * @package BSI
 * @since 1.0.0
 */

require_once get_template_directory() . '/inc/services/CacheService.php';
require_once get_template_directory() . '/inc/helpers.php';

if (!class_exists('SamoService')) {
  require_once get_template_directory() . '/inc/samo/SamoService.php';
}

class PriceLoaderService
{
  /**
   * Группа кэша для цен туров
   */
  const CACHE_GROUP_TOURS = 'tour_prices';

  /**
   * Группа кэша для цен отелей
   */
  const CACHE_GROUP_HOTELS = 'hotel_prices';

  /**
   * Время кэширования цен (3 часа)
   */
  const CACHE_EXPIRATION = 3 * HOUR_IN_SECONDS;

  /**
   * Получить цену тура только из кэша (без запроса к API).
   * Безопасно для использования в шаблонах — не блокирует рендер.
   * 
   * @param int $tour_id ID тура
   * @param array $params Дополнительные параметры
   * @return array|null Массив с ценой или null, если кэш пуст
   */
  public static function getCachedTourPrice(int $tour_id, array $params = []): ?array
  {
    if (!$tour_id) {
      return null;
    }

    $cache_key = self::buildTourCacheKey($tour_id, $params);
    $cached = CacheService::get($cache_key, self::CACHE_GROUP_TOURS);

    return $cached !== false ? $cached : null;
  }

  /**
   * Получить цену тура с кэшированием
   * 
   * @param int $tour_id ID тура
   * @param array $params Дополнительные параметры (date_from, date_to, adults, children)
   * @return array|null Массив с ценой или null, если не удалось загрузить
   */
  public static function getTourPrice(int $tour_id, array $params = []): ?array
  {
    if (!$tour_id) {
      return null;
    }

    $excursion_params = get_tour_excursion_params($tour_id);
    if (empty($excursion_params) || empty($excursion_params['TOURS'])) {
      // Fallback: проверяем статичное поле price_from
      if (function_exists('get_field')) {
        $static_price = get_field('price_from', $tour_id);
        $show_from_field = get_field('show_price_from', $tour_id);
        $show_from = $show_from_field !== false;
        
        if (!empty($static_price)) {
          $price_numeric = preg_replace('/[^\d]/', '', $static_price);
          if (!empty($price_numeric)) {
            return [
              'price' => (float) $price_numeric,
              'price_formatted' => number_format((float) $price_numeric, 0, '.', ' '),
              'show_from' => $show_from,
              'currency' => '₽',
            ];
          }
        }
      }
      
      return null;
    }

    $cache_key = self::buildTourCacheKey($tour_id, $params);

    $result = CacheService::remember(
      $cache_key,
      function () use ($tour_id, $excursion_params, $params) {
        return self::fetchTourPrice($tour_id, $excursion_params, $params);
      },
      self::CACHE_EXPIRATION,
      self::CACHE_GROUP_TOURS
    );

    return $result;
  }

  /**
   * Получить цены нескольких туров пакетно
   * 
   * @param array $tour_ids Массив ID туров
   * @param array $params Общие параметры для всех туров
   * @return array Ассоциативный массив [tour_id => price_data]
   */
  public static function getBatchTourPrices(array $tour_ids, array $params = []): array
  {
    if (empty($tour_ids)) {
      return [];
    }

    $results = [];

    foreach ($tour_ids as $tour_id) {
      $tour_id = (int) $tour_id;
      if (!$tour_id) {
        continue;
      }

      $price_data = self::getTourPrice($tour_id, $params);
      if ($price_data) {
        $results[$tour_id] = $price_data;
      }
    }

    return $results;
  }

  /**
   * Очистить кэш цен туров
   * 
   * @param int|null $tour_id ID конкретного тура или null для очистки всех
   * @return bool|int
   */
  public static function clearTourPricesCache(?int $tour_id = null)
  {
    if ($tour_id) {
      // Очищаем все вариации кэша для конкретного тура
      $base_key = 'tour_' . $tour_id;
      return CacheService::forget($base_key, self::CACHE_GROUP_TOURS);
    }

    // Очищаем весь кэш туров
    return CacheService::flush(self::CACHE_GROUP_TOURS);
  }

  /**
   * Загрузить цену тура из Samotour API
   * 
   * @param int $tour_id ID тура
   * @param array $excursion_params Параметры экскурсии (TOWNFROMINC, STATEINC, TOURS)
   * @param array $params Дополнительные параметры
   * @return array|null
   */
  private static function fetchTourPrice(int $tour_id, array $excursion_params, array $params = []): ?array
  {
    try {
      // Подготавливаем параметры для API
      $api_params = [
        'TOWNFROMINC' => $excursion_params['TOWNFROMINC'] ?? 1,
        'STATEINC' => $excursion_params['STATEINC'] ?? 0,
        'TOURS' => $excursion_params['TOURS'] ?? 0,
        'ADULT' => $params['adults'] ?? 2,
        'CHILD' => $params['children'] ?? 0,
        'CURRENCY' => 1, // RUB
        'NIGHTS_FROM' => 1,  // Минимум 1 ночь
        'NIGHTS_TILL' => 30, // Максимум 30 ночей
      ];

      // Если даты указаны явно, используем их
      if (!empty($params['date_from']) && !empty($params['date_to'])) {
        $api_params['CHECKIN_BEG'] = $params['date_from'];
        $api_params['CHECKIN_END'] = $params['date_to'];
      } else {
        // Иначе используем диапазон 3 месяца вперед
        $api_params['CHECKIN_BEG'] = date('Ymd');  // Сегодня
        $api_params['CHECKIN_END'] = date('Ymd', strtotime('+3 months'));  // +3 месяца
      }

      $result = SamoService::endpoints()->searchExcursionPrices($api_params);

      $samo_data = $result['data']['SearchExcursion_PRICES'] ?? null;
      if (!$samo_data || !is_array($samo_data)) {
        return null;
      }

      // Обрабатываем оба формата ответа SAMO:
      // 1) {"SearchExcursion_PRICES": {"prices": [...]}}  — вложенный ключ prices
      // 2) {"SearchExcursion_PRICES": [{...}, {...}]}      — массив напрямую
      if (isset($samo_data['prices'])) {
        $prices = is_array($samo_data['prices']) ? $samo_data['prices'] : [$samo_data['prices']];
      } elseif (isset($samo_data[0])) {
        $prices = $samo_data;
      } else {
        return null;
      }

      // Находим минимальную цену: convertedPriceNumber > convertedPrice > price
      $min_price = null;
      foreach ($prices as $price_item) {
        $price_value = $price_item['convertedPriceNumber'] 
          ?? $price_item['convertedPrice'] 
          ?? $price_item['price'] 
          ?? null;

        if ($price_value === null) {
          continue;
        }

        $price = (float) $price_value;
        if ($min_price === null || $price < $min_price) {
          $min_price = $price;
        }
      }

      if ($min_price === null) {
        return null;
      }

      // Сохраняем цену за 1 чел (цена из API обычно за 2 чел)
      $price_per_person = round($min_price / 2);

      // Получаем настройку show_price_from из ACF
      $show_from = true;
      if (function_exists('get_field')) {
        $show_from_field = get_field('show_price_from', $tour_id);
        $show_from = $show_from_field !== false;
      }

      return [
        'price' => $price_per_person,
        'price_formatted' => number_format($price_per_person, 0, '.', ' '),
        'show_from' => $show_from,
        'currency' => '₽',
      ];

    } catch (Exception $e) {
      // В случае ошибки возвращаем null
      return null;
    }
  }

  /**
   * Получить ближайшие доступные даты для тура
   * 
   * @param array $excursion_params Параметры экскурсии
   * @return array|null Массив с date_from и date_to или null
   */
  private static function getAvailableDates(array $excursion_params): ?array
  {
    try {
      $result = SamoService::endpoints()->searchExcursionAll([
        'TOWNFROMINC' => $excursion_params['TOWNFROMINC'] ?? 1,
        'STATEINC' => $excursion_params['STATEINC'] ?? 0,
        'TOURS' => $excursion_params['TOURS'] ?? 0,
      ]);

      if (!isset($result['data']['SearchExcursion_ALL']['CHECKIN_BEG'])) {
        return null;
      }

      $checkInBeg = $result['data']['SearchExcursion_ALL']['CHECKIN_BEG'];
      if (empty($checkInBeg['validDates']) || empty($checkInBeg['startDate'])) {
        return null;
      }

      $validDates = $checkInBeg['validDates'];
      $startDate = $checkInBeg['startDate'];

      // Парсим стартовую дату (YYYYMMDD)
      $year = (int) substr($startDate, 0, 4);
      $month = (int) substr($startDate, 4, 2);
      $day = (int) substr($startDate, 6, 2);
      $timestamp = mktime(0, 0, 0, $month, $day, $year);

      // Находим первую доступную дату
      for ($i = 0; $i < strlen($validDates); $i++) {
        if ($validDates[$i] === '1') {
          $date_timestamp = $timestamp + ($i * 24 * 60 * 60);
          $date_from = date('Ymd', $date_timestamp);
          // Берем диапазон 7 дней
          $date_to = date('Ymd', $date_timestamp + (7 * 24 * 60 * 60));
          
          return [
            'date_from' => $date_from,
            'date_to' => $date_to,
          ];
        }
      }

      return null;

    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Построить ключ кэша для цены тура
   * 
   * @param int $tour_id ID тура
   * @param array $params Параметры
   * @return string
   */
  private static function buildTourCacheKey(int $tour_id, array $params): string
  {
    $key_parts = ['tour', $tour_id];

    if (!empty($params['date_from'])) {
      $key_parts[] = $params['date_from'];
    }
    if (!empty($params['date_to'])) {
      $key_parts[] = $params['date_to'];
    }
    if (!empty($params['adults'])) {
      $key_parts[] = 'a' . $params['adults'];
    }
    if (!empty($params['children'])) {
      $key_parts[] = 'c' . $params['children'];
    }

    return implode('_', $key_parts);
  }
}
