<?php

/**
 * Поля типа визы.
 *
 * Часть визовых направлений ведётся на отдельных поддоменах (например
 * «Золотая виза» ОАЭ → goldenvisa.bsigroup.ru). Такой тип помечается
 * внешней ссылкой, и все ссылки этого типа уходят на поддомен.
 * См. wiki/docs/visa-external-projects.md
 */

add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_visa_type_fields',
    'title' => 'Настройки типа визы',
    'fields' => [

      [
        'key' => 'field_visa_type_external_url',
        'label' => 'Внешняя ссылка',
        'name' => 'visa_type_external_url',
        'type' => 'url',
        'instructions' => 'Если заполнено, все ссылки этого типа ведут на указанный сайт и открываются в новой вкладке. UTM-метки добавляются автоматически.',
      ],

      [
        'key' => 'field_visa_type_menu_label',
        'label' => 'Название в меню страны',
        'name' => 'visa_type_menu_label',
        'type' => 'text',
        'instructions' => 'Только для типов с внешней ссылкой. Например «Золотая виза». Если пусто — используется название типа.',
      ],

      [
        'key' => 'field_visa_type_utm_campaign',
        'label' => 'utm_campaign',
        'name' => 'visa_type_utm_campaign',
        'type' => 'text',
        'instructions' => 'Если пусто — используется слаг типа.',
      ],

    ],
    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'visa_type',
        ],
      ],
    ],
  ]);
});
