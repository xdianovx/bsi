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
          'width' => '25',
        ],
      ],
      [
        'key' => 'field_promo_date_from',
        'label' => 'Дата начала',
        'name' => 'promo_date_from',
        'type' => 'date_picker',
        'wrapper' => [
          'width' => '25',
        ],
      ],
      [
        'key' => 'field_promo_date_to',
        'label' => 'Дата окончания',
        'name' => 'promo_date_to',
        'type' => 'date_picker',
        'wrapper' => [
          'width' => '25',
        ],
      ],

      [
        'key' => 'field_promo_link',
        'label' => 'Ссылка на подробности',
        'name' => 'promo_link',
        'type' => 'url',
        'wrapper' => [
          'width' => '25',
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