<?php
add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_promo_fields',
    'title' => 'Данные акции',
    'fields' => [
      [
        'key' => 'field_promo_countries',
        'label' => 'Страны',
        'name' => 'promo_countries',
        'type' => 'post_object',
        'post_type' => ['country'],
        'multiple' => 1,
        'return_format' => 'id',
        'ui' => 1,
        'wrapper' => [
          'width' => '50',
        ],
      ],
      [
        'key' => 'field_promo_link',
        'label' => 'Ссылка на подробности',
        'name' => 'promo_link',
        'type' => 'url',
        'wrapper' => [
          'width' => '50',
        ],
      ],
      [
        'key' => 'field_promo_date_from',
        'label' => 'Дата начала проведения акции',
        'name' => 'promo_date_from',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Ymd',
        'required' => 0,
        'instructions' => 'Необязательно. Пустые «с» и «до» — акция без срока на сайте. Если начало в будущем, акция появится с этой даты.',
        'wrapper' => [
          'width' => '50',
        ],
      ],
      [
        'key' => 'field_promo_date_to',
        'label' => 'Дата окончания проведения акции',
        'name' => 'promo_date_to',
        'type' => 'date_picker',
        'display_format' => 'd.m.Y',
        'return_format' => 'Ymd',
        'required' => 0,
        'instructions' => 'Необязательно. Если пусто — без даты окончания на сайте (бессрочно). Если дата уже прошла — акция в списке архивных.',
        'wrapper' => [
          'width' => '50',
        ],
      ],
      [
        'key' => 'field_promo_booking_url',
        'label' => 'Внешняя ссылка на бронирование',
        'name' => 'promo_booking_url',
        'type' => 'url',
        'instructions' => 'Если указана, показывается кнопка вместо формы заявки (как у событийного тура).',
        'wrapper' => [
          'width' => '50',
        ],
      ],
      [
        'key' => 'field_promo_booking_cta_lead',
        'label' => 'Текст под заголовком блока «Узнать подробности»',
        'name' => 'promo_booking_cta_lead',
        'type' => 'textarea',
        'rows' => 3,
        'default_value' => BSI_PROMO_BOOKING_CTA_LEAD_DEFAULT,
        'wrapper' => [
          'width' => '100',
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'promo',
        ],
      ],
    ],
  ]);
});