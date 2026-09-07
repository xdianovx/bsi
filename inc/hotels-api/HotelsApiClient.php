<?php

/**
 * Клиент публичного API BSIHOTELS (хаб отелей).
 *
 * Читает только: список отелей, карточку, справочники стран/городов/удобств.
 * Токена нет.
 *
 * Своего кеша нет и не будет: кеш держит хаб, его ответ всегда свежее нашей
 * копии. Единственная защита на нашей стороне — время ожидания: на холодных
 * данных хаб уходит в минуты, и страница страны не должна за него висеть.
 * Поэтому при рендере ждём секунды, а каталог догружается запросом, которому
 * ждать не жалко (см. inc/requests/ajax-hotels-api-catalog.php).
 */
class HotelsApiClient
{
  private string $baseUrl;
  private int $timeout;

  /**
   * Своего кеша нет: 0 — не кешировать.
   *
   * Хаб держит собственный кэш и отвечает на тёплых данных за десятки
   * миллисекунд, а цены обновляет по спросу. Второй слой кеша поверх только
   * добавлял бы ценам возраст и показывал бы не то, что в хабе.
   *
   * Значения меняются фильтром bsi_hotels_api_cache_ttl.
   */
  private const CACHE_LIST = 0;
  private const CACHE_HOTEL = 0;
  private const CACHE_DICT = 0;


  /** Версия ключа кеша: поднять, если поменялась форма ответа. */
  private const CACHE_VERSION = 'v1';

  public function __construct(array $config)
  {
    $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
    /**
     * Столько ждём хаб. На странице это секунды — дольше уже висящая страница,
     * а не медленный ответ; в AJAX-догрузке каталога ожидание никого не держит,
     * поэтому там фильтр поднимает предел.
     *
     * @param int $timeout секунды
     */
    $this->timeout = max(1, (int) apply_filters('bsi_hotels_api_timeout', (int) ($config['timeout'] ?? 2)));

    if ($this->baseUrl === '') {
      throw new RuntimeException('BSIHOTELS: не задан BSI_HOTELS_API_URL');
    }
  }

  /**
   * Список отелей. Параметры — как в /v1/hotels: country, city, q, page, limit,
   * sort, order, min_stars, has_photo и т.д.
   *
   * @return array{items: array, total: int, page: int, limit: int, pages: int, next_cursor: ?int}
   * @throws HotelsApiException
   */
  public function hotels(array $params = []): array
  {
    $data = $this->get('/v1/hotels', $params, self::CACHE_LIST);

    return [
      'items' => is_array($data['items'] ?? null) ? $data['items'] : [],
      'total' => (int) ($data['total'] ?? 0),
      'page' => (int) ($data['page'] ?? 1),
      'limit' => (int) ($data['limit'] ?? 0),
      'pages' => (int) ($data['pages'] ?? 0),
      'next_cursor' => isset($data['next_cursor']) ? (int) $data['next_cursor'] : null,
    ];
  }

  /**
   * Карточка отеля по слагу или числовому id.
   *
   * @throws HotelsApiException 404 приходит как HotelsApiException с кодом 404
   */
  public function hotel(string $idOrSlug): array
  {
    return $this->get('/v1/hotels/' . rawurlencode($idOrSlug), [], self::CACHE_HOTEL);
  }

  /**
   * Отели направления с координатами — для карты каталога.
   *
   * Без пагинации и без тяжёлых полей: одна выдача на всю страну или курорт.
   * Фильтры те же, что у /v1/hotels.
   *
   * @return array{items: array, total: int, returned: int, truncated: bool}
   * @throws HotelsApiException
   */
  public function hotelsMap(array $params = []): array
  {
    $data = $this->get('/v1/hotels/map', $params, self::CACHE_LIST);

    return [
      'items' => is_array($data['items'] ?? null) ? $data['items'] : [],
      'total' => (int) ($data['total'] ?? 0),
      'returned' => (int) ($data['returned'] ?? 0),
      'truncated' => (bool) ($data['truncated'] ?? false),
    ];
  }

  /** Страны, в которых есть отели. */
  public function countries(): array
  {
    $data = $this->get('/v1/countries', [], self::CACHE_DICT);
    return is_array($data['items'] ?? null) ? $data['items'] : [];
  }

  /** Города, в которых есть отели. $country — слаг страны. */
  public function cities(string $country = ''): array
  {
    $params = $country !== '' ? ['country' => $country] : [];
    $data = $this->get('/v1/cities', $params, self::CACHE_DICT);
    return is_array($data['items'] ?? null) ? $data['items'] : [];
  }

  /** Живо ли API. Без кеша и с коротким таймаутом — для диагностики. */
  public function isAlive(): bool
  {
    $response = wp_remote_get($this->baseUrl . '/healthz', ['timeout' => 3]);
    return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
  }

  /**
   * @throws HotelsApiException
   */
  private function get(string $path, array $params, int $ttl): array
  {
    $params = array_filter(
      $params,
      static fn($v) => $v !== null && $v !== '' && $v !== []
    );
    ksort($params);

    $url = $this->baseUrl . $path;
    if ($params) {
      $url .= '?' . http_build_query($params);
    }

    /**
     * Время жизни кеша конкретного запроса.
     *
     * @param int $ttl секунды; 0 — не кешировать
     * @param string $path путь запроса
     */
    $ttl = (int) apply_filters('bsi_hotels_api_cache_ttl', $ttl, $path, $params);

    $cacheKey = 'bsi_hotels_' . self::CACHE_VERSION . '_' . md5($url);

    if ($ttl > 0) {
      $cached = get_transient($cacheKey);
      if (is_array($cached)) {
        return $cached;
      }
    }

    $response = wp_remote_get($url, [
      'timeout' => $this->timeout,
      'headers' => ['Accept' => 'application/json'],
    ]);

    if (is_wp_error($response)) {
      throw new HotelsApiException($response->get_error_message(), 0);
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($code !== 200) {
      $message = is_array($data) && isset($data['error'])
        ? (string) $data['error']
        : 'HTTP ' . $code;
      throw new HotelsApiException($message, $code);
    }

    if (!is_array($data)) {
      throw new HotelsApiException('Некорректный JSON от API', 0);
    }

    if ($ttl > 0) {
      set_transient($cacheKey, $data, $ttl);
    }

    return $data;
  }
}

class HotelsApiException extends RuntimeException
{
}
