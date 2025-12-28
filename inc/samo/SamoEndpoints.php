<?php

class SamoEndpoints
{
  public function __construct(private SamoClient $client)
  {
  }

  public function searchTourAll(array $params): array
  {
    return $this->client->request('SearchTour_ALL', $params);
  }

  public function searchTourPrices(array $params): array
  {
    return $this->client->request('SearchTour_PRICES', $params);
  }

  public function searchTownFroms(): array
  {
    return $this->client->request('SearchTour_TOWNFROMS');
  }

  public function searchStates(array $params): array
  {
    return $this->client->request('SearchTour_STATES', $params);
  }

  public function searchHotelStates(array $params): array
  {
    return $this->client->request('SearchHotel_STATES', $params);
  }

  public function searchHotelHotels(array $params): array
  {
    return $this->client->request('SearchHotel_HOTELS', $params);
  }

  /**
   * Получение списка стран для экскурсионного тура
   *
   * @param array $params Параметры: TOWNFROMINC
   * @return array
   */
  public function searchExcursionStates(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_STATES', $params);
  }

  /**
   * Получение списка туров для экскурсионного тура по стране
   *
   * @param array $params Параметры: TOWNFROMINC, STATEINC
   * @return array
   */
  public function searchExcursionTours(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_TOURS', $params);
  }

  /**
   * Поиск отелей для экскурсионного тура
   *
   * @param array $params Параметры: TOWNFROMINC, STATEINC, TOURS
   * @return array
   */
  public function searchExcursionHotels(array $params): array
  {
    $params['type'] = 'api'; // API возвращает JSON для экскурсий
    return $this->client->request('SearchExcursion_HOTELS', $params);
  }

  /**
   * Поиск доступных ночей для экскурсионного тура
   *
   * @param array $params Параметры: TOWNFROMINC, STATEINC, TOURS
   * @return array
   */
  public function searchExcursionNights(array $params): array
  {
    $params['type'] = 'api'; // API возвращает JSON для экскурсий
    return $this->client->request('SearchExcursion_NIGHTS', $params);
  }

  /**
   * Поиск цен для экскурсионного тура
   *
   * @param array $params Параметры: TOWNFROMINC, STATEINC, TOURS, CHECKIN_BEG, CHECKIN_END, NIGHTS_FROM, NIGHTS_TILL, ADULT, CHILD, CURRENCY
   * @return array
   */
  public function searchExcursionPrices(array $params): array
  {
    $params['type'] = 'api'; // API возвращает JSON для экскурсий
    return $this->client->request('SearchExcursion_PRICES', $params);
  }

  /**
   * Получение всех данных для экскурсионного тура (включая доступные даты)
   *
   * @param array $params Параметры: TOWNFROMINC, STATEINC, TOURS
   * @return array
   */
  public function searchExcursionAll(array $params): array
  {
    $params['type'] = 'api'; // API возвращает JSON для экскурсий
    return $this->client->request('SearchExcursion_ALL', $params);
  }

  /**
   * Получение типов транспорта для авиабилетов
   *
   * @param array $params Параметры: WITH_CHARTER (по умолчанию 1), WITH_REGULAR (по умолчанию 1)
   * @return array
   */
  public function ticketsTransportTypes(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_TRANSPORTTYPES', array_merge($defaultParams, $params));
  }

  /**
   * Получение аэропортов отправления для авиабилетов
   *
   * @param array $params Параметры: SUGGEST, WITH_CHARTER, WITH_REGULAR, TRANSPORTTYPE
   * @return array
   */
  public function ticketsSources(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_SOURCES', array_merge($defaultParams, $params));
  }

  /**
   * Получение аэропортов прибытия для авиабилетов
   *
   * @param array $params Параметры: SUGGEST, WITH_CHARTER, WITH_REGULAR, TRANSPORTTYPE, SOURCE
   * @return array
   */
  public function ticketsTargets(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_TARGETS', array_merge($defaultParams, $params));
  }

  /**
   * Получение всех данных для фильтров авиабилетов
   *
   * @param array $params Параметры: SOURCE, TARGET, FREIGHTBACK, WITH_CHARTER, WITH_REGULAR, TRANSPORTTYPE
   * @return array
   */
  public function ticketsAll(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_ALL', array_merge($defaultParams, $params));
  }
}


