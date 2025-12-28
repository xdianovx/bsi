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

  public function searchExcursionStates(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_STATES', $params);
  }

  public function searchExcursionTours(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_TOURS', $params);
  }

  public function searchExcursionHotels(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_HOTELS', $params);
  }

  public function searchExcursionNights(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_NIGHTS', $params);
  }

  public function searchExcursionPrices(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_PRICES', $params);
  }

  public function searchExcursionAll(array $params): array
  {
    $params['type'] = 'api';
    return $this->client->request('SearchExcursion_ALL', $params);
  }

  public function ticketsTransportTypes(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_TRANSPORTTYPES', array_merge($defaultParams, $params));
  }

  public function ticketsSources(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_SOURCES', array_merge($defaultParams, $params));
  }

  public function ticketsTargets(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_TARGETS', array_merge($defaultParams, $params));
  }

  public function ticketsAll(array $params = []): array
  {
    $defaultParams = [
      'WITH_CHARTER' => 1,
      'WITH_REGULAR' => 1,
    ];
    return $this->client->request('Tickets_ALL', array_merge($defaultParams, $params));
  }
}


