<?php

/**
 * Трансферы страны: пункт в сайд-меню + опциональная страница /country/{slug}/transfery/.
 * Режим и ссылка на поиск услуг в Само задаются в карточке страны.
 */

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_country_transfers',
    'title' => 'Трансферы',
    'menu_order' => 25,
    'fields' => [
      [
        'key' => 'field_country_transfers_mode',
        'label' => 'Пункт «Трансферы» в меню страны',
        'name' => 'transfers_mode',
        'type' => 'radio',
        'choices' => [
          '' => 'Не показывать',
          'link' => 'Ссылка на Само (откроется в новой вкладке)',
          'page' => 'Страница на сайте (текст + кнопка на Само)',
        ],
        'default_value' => '',
        'layout' => 'vertical',
        'return_format' => 'value',
      ],
      [
        'key' => 'field_country_transfers_samo_url',
        'label' => 'Ссылка на трансферы в Само',
        'name' => 'transfers_samo_url',
        'type' => 'url',
        'instructions' => 'Откройте online.bsigroup.ru → «Услуги» или «Трансферы», выберите страну, нажмите «Искать» и скопируйте адрес. Прошедшие даты в ссылке сайт сам сдвигает вперёд.',
        'conditional_logic' => [
          [['field' => 'field_country_transfers_mode', 'operator' => '==', 'value' => 'link']],
          [['field' => 'field_country_transfers_mode', 'operator' => '==', 'value' => 'page']],
        ],
      ],
      [
        'key' => 'field_country_transfers_content',
        'label' => 'Текст страницы',
        'name' => 'transfers_content',
        'type' => 'wysiwyg',
        'instructions' => 'Аэропорты, время в пути, как проходит встреча. Кнопка на Само добавится под текстом автоматически.',
        'tabs' => 'all',
        'toolbar' => 'full',
        'media_upload' => 1,
        'conditional_logic' => [
          [['field' => 'field_country_transfers_mode', 'operator' => '==', 'value' => 'page']],
        ],
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
