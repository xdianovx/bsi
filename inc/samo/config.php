<?php

function samo_config(): array
{
  return [
    'base_url' => 'https://online.bsigroup.ru/export/default.php',
    'token' => 'ddcb768f480a4d769bc960c76ac29528',
    'samo_action' => defined('SAMO_ACTION') ? SAMO_ACTION : 'api',
    'version' => defined('SAMO_API_VERSION') ? SAMO_API_VERSION : '1.0',
    'type' => defined('SAMO_API_TYPE') ? SAMO_API_TYPE : 'json',
  ];
}