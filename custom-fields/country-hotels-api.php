<?php

/**
 * Связь страны из WordPress со страной в хабе BSIHOTELS.
 * Слаги не совпадают (turcziya ↔ turkey), поэтому связь задаётся явно.
 */

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_country_hotels_api',
    'title' => 'Отели: связь с хабом BSIHOTELS',
    'menu_order' => 20,
    'fields' => [
      [
        'key' => 'field_country_hotels_api',
        'label' => 'Страна в хабе отелей',
        'name' => 'hotels_api_country',
        'type' => 'select',
        'instructions' => 'Каталог отелей этой страны берётся из хаба. Пусто — вкладка «Отели» покажет отели, заведённые в WordPress.',
        'allow_null' => 1,
        'ui' => 1,
        'return_format' => 'value',
        'choices' => [],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'country',
        ],
      ],
    ],
  ]);
});

/**
 * Живой список стран из хаба в выпадашке. API недоступен — остаётся
 * сохранённое значение, чтобы редактор не затёр связь пустым списком.
 */
add_filter('acf/load_field/key=field_country_hotels_api', function (array $field): array {
  $client = bsi_hotels_api();
  if (!$client) {
    return $field;
  }

  try {
    foreach ($client->countries() as $country) {
      $slug = (string) ($country['slug'] ?? '');
      if ($slug === '') {
        continue;
      }

      $field['choices'][$slug] = sprintf(
        '%s (%s) — отелей: %d',
        $country['name'] ?? $slug,
        $slug,
        (int) ($country['hotels'] ?? 0)
      );
    }
  } catch (Throwable $e) {
    $field['instructions'] .= ' Хаб сейчас недоступен: ' . esc_html($e->getMessage());
  }

  return $field;
});
