<?php

function samo_config(): array
{
  return [
    'base_url' => SAMO_API_URL,
    'token' => SAMO_OAUTH_TOKEN,
    'samo_action' => defined('SAMO_ACTION') ? SAMO_ACTION : 'api',
    'version' => defined('SAMO_API_VERSION') ? SAMO_API_VERSION : '1.0',
    'type' => defined('SAMO_API_TYPE') ? SAMO_API_TYPE : 'json',
  ];
}