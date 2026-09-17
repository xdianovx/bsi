<?php

/**
 * Связь курорта из WordPress с городом в хабе BSIHOTELS.
 * Страна берётся из самого города, отдельно указывать её не нужно.
 */

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_resort_hotels_api',
    'title' => 'Отели: связь с хабом BSIHOTELS',
    'menu_order' => 20,
    'fields' => [
      [
        'key' => 'field_resort_hotels_api',
        'label' => 'Курорт в хабе отелей',
        'name' => 'hotels_api_city',
        'type' => 'select',
        'instructions' => 'Отели курорта берутся из хаба. Пусто — на странице останутся отели, заведённые в WordPress.',
        'allow_null' => 1,
        'ui' => 1,
        'return_format' => 'value',
        'choices' => [],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'resort',
        ],
      ],
    ],
  ]);
});

/**
 * Живой список курортов хаба в выпадашке: название, страна и число отелей —
 * иначе «Санья» и «Санья бэй» не различить.
 */
add_filter('acf/load_field/key=field_resort_hotels_api', function (array $field): array {
  $client = bsi_hotels_api();
  if (!$client) {
    return $field;
  }

  try {
    $cities = $client->cities();
  } catch (Throwable $e) {
    $field['instructions'] .= ' Хаб сейчас недоступен: ' . esc_html($e->getMessage());

    return $field;
  }

  usort($cities, static function ($a, $b) {
    return [$a['country']['name'] ?? '', $a['name'] ?? '']
      <=> [$b['country']['name'] ?? '', $b['name'] ?? ''];
  });

  foreach ($cities as $city) {
    $slug = (string) ($city['slug'] ?? '');
    if ($slug === '') {
      continue;
    }

    $field['choices'][$slug] = sprintf(
      '%s — %s (отелей: %d)',
      $city['country']['name'] ?? '',
      $city['name'] ?? $slug,
      (int) ($city['hotels'] ?? 0)
    );
  }

  return $field;
});
