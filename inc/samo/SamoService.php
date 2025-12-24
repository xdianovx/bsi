<?php

final class SamoService
{
  private static ?SamoClient $client = null;
  private static ?SamoEndpoints $endpoints = null;

  public static function client(): SamoClient
  {
    if (self::$client)
      return self::$client;

    $config = [
      'base_url' => defined('SAMO_API_URL') ? SAMO_API_URL : '',
      'token' => defined('SAMO_OAUTH_TOKEN') ? SAMO_OAUTH_TOKEN : '',
      'samo_action' => defined('SAMO_ACTION') ? SAMO_ACTION : 'api',
      'version' => defined('SAMO_API_VERSION') ? SAMO_API_VERSION : '1.0',
      'type' => defined('SAMO_API_TYPE') ? SAMO_API_TYPE : 'json',
    ];

    self::$client = new SamoClient($config);
    return self::$client;
  }

  public static function endpoints(): SamoEndpoints
  {
    if (self::$endpoints)
      return self::$endpoints;

    self::$endpoints = new SamoEndpoints(self::client());
    return self::$endpoints;
  }
}