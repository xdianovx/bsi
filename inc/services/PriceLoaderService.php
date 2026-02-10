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

    // Получаем параметры экскурсии из ACF поля tour_booking_url
    $excursion_params = get_tour_excursion_params($tour_id);
    if (empty($excursion_params) || empty($excursion_params['TOURS'])) {
      error_log("PriceLoaderService: tour {$tour_id} has no tour_booking_url or invalid URL, trying static price_from field");
      
      // Fallback: проверяем статичное поле price_from
      if (function_exists('get_field')) {
        $static_price = get_field('price_from', $tour_id);
        $show_from_field = get_field('show_price_from', $tour_id);
        $show_from = $show_from_field !== false;
        
        if (!empty($static_price)) {
          // Парсим статичную цену (может быть в формате "от 45 000 ₽" или просто "45000")
          $price_numeric = preg_replace('/[^\d]/', '', $static_price);
          if (!empty($price_numeric)) {
            error_log("PriceLoaderService: tour {$tour_id} using static price: {$price_numeric}");
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

    // Строим ключ кэша на основе параметров
    $cache_key = self::buildTourCacheKey($tour_id, $params);

    // Пытаемся получить из кэша
    error_log("PriceLoaderService: getTourPrice - tour_id: {$tour_id}, cache_key: {$cache_key}");
    
    $result = CacheService::remember(
      $cache_key,
      function () use ($tour_id, $excursion_params, $params) {
        error_log("PriceLoaderService: Cache MISS - fetching from API for tour {$tour_id}");
        return self::fetchTourPrice($tour_id, $excursion_params, $params);
      },
      self::CACHE_EXPIRATION,
      self::CACHE_GROUP_TOURS
    );
    
    error_log("PriceLoaderService: getTourPrice result: " . ($result ? json_encode($result) : 'NULL'));
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

      // Запрашиваем цены из Samotour
      error_log("PriceLoaderService: Запрос к Samotour для tour {$tour_id}, params: " . print_r($api_params, true));
      
      $result = SamoService::endpoints()->searchExcursionPrices($api_params);

      error_log("PriceLoaderService: Samotour ПОЛНЫЙ ответ для tour {$tour_id}: " . print_r($result, true));

      if (!isset($result['data']['SearchExcursion_PRICES']['prices'])) {
        error_log("PriceLoaderService: No prices in response for tour {$tour_id}");
        
        // Проверяем есть ли ошибка от Samotour
        if (isset($result['data']['SearchExcursion_PRICES']['error'])) {
          error_log("PriceLoaderService: Samotour error code: " . $result['data']['SearchExcursion_PRICES']['error']);
        }
        
        return null;
      }

      $prices = $result['data']['SearchExcursion_PRICES']['prices'];
      if (!is_array($prices)) {
        $prices = [$prices];
      }

      error_log("PriceLoaderService: Found " . count($prices) . " prices for tour {$tour_id}");
      if (!empty($prices)) {
        error_log("PriceLoaderService: First price item: " . print_r($prices[0], true));
      }

      // Находим минимальную цену с приоритетной логикой парсинга
      $min_price = null;
      foreach ($prices as $price_item) {
        // Приоритетная логика: convertedPriceNumber > convertedPrice > price
        $price_value = $price_item['convertedPriceNumber'] 
          ?? $price_item['convertedPrice'] 
          ?? $price_item['price'] 
          ?? null;
        
        error_log("PriceLoaderService: Price item - convertedPriceNumber: " . ($price_item['convertedPriceNumber'] ?? 'NULL') . 
                  ", convertedPrice: " . ($price_item['convertedPrice'] ?? 'NULL') . 
                  ", price: " . ($price_item['price'] ?? 'NULL') . 
                  ", chosen: " . ($price_value ?? 'NULL'));
        
        if ($price_value === null) {
          continue;
        }

        $price = (float) $price_value;
        if ($min_price === null || $price < $min_price) {
          $min_price = $price;
        }
      }
      
      error_log("PriceLoaderService: Minimum price for tour {$tour_id}: " . ($min_price ?? 'NULL'));

      if ($min_price === null) {
        return null;
      }

      // Получаем настройку show_price_from из ACF
      $show_from = true;
      if (function_exists('get_field')) {
        $show_from_field = get_field('show_price_from', $tour_id);
        $show_from = $show_from_field !== false;
      }

      return [
        'price' => $min_price,
        'price_formatted' => number_format($min_price, 0, '.', ' '),
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
