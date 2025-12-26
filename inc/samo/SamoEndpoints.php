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
}


