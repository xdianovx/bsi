<?php

/**
 * Подборка отелей из хаба BSIHOTELS для главной страницы.
 * Список курируется руками: у хаба нет признака «популярный».
 */

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group') || !function_exists('acf_add_options_page')) {
    return;
  }

  acf_add_options_page([
    'page_title' => 'Подборка отелей',
    'menu_title' => 'Подборка отелей',
    'menu_slug' => 'hotels-api-selection',
    'capability' => 'manage_options',
    'icon_url' => 'dashicons-building',
    'position' => 31,
  ]);

  acf_add_local_field_group([
    'key' => 'group_hotels_api_selection',
    'title' => 'Отели на главной',
    'fields' => [
      [
        'key' => 'field_hotels_api_selection_title',
        'label' => 'Заголовок блока',
        'name' => 'hotels_api_selection_title',
        'type' => 'text',
        'default_value' => 'Популярные отели',
      ],
      [
        'key' => 'field_hotels_api_selection',
        'label' => 'Отели',
        'name' => 'hotels_api_selection',
        'type' => 'repeater',
        'layout' => 'table',
        'button_label' => 'Добавить отель',
        'instructions' => 'Порядок строк — порядок вывода. Отель, которого нет в хабе, просто не покажется.',
        'sub_fields' => [
          [
            'key' => 'field_hotels_api_selection_country',
            'label' => 'Страна',
            'name' => 'country',
            'type' => 'post_object',
            'post_type' => ['country'],
            'return_format' => 'id',
            'ui' => 1,
            'required' => 1,
            'instructions' => 'Нужна для адреса карточки отеля.',
          ],
          [
            'key' => 'field_hotels_api_selection_slug',
            'label' => 'Слаг отеля в хабе',
            'name' => 'slug',
            'type' => 'text',
            'required' => 1,
            'placeholder' => 'the-slate-phuket',
            'instructions' => 'Хвост адреса карточки: /country/{страна}/hotel/{слаг}/',
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'options_page',
          'operator' => '==',
          'value' => 'hotels-api-selection',
        ],
      ],
    ],
  ]);
});
