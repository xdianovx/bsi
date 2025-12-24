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
}