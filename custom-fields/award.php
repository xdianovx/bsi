<?php

add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_award_fields',
    'title' => 'Награда',
    'fields' => [

      [
        'key' => 'field_award_year',
        'label' => 'Год награды',
        'name' => 'award_year',
        'type' => 'number',
        'min' => 1989,
        'max' => date('Y') + 1,
        'step' => 1,
        'wrapper' => [
          'width' => '25',
        ],
      ],

      [
        'key' => 'field_award_issuer',
        'label' => 'Кем вручена',
        'name' => 'award_issuer',
        'type' => 'text',
        'wrapper' => [
          'width' => '25',
        ],
      ],

      [
        'key' => 'field_award_link',
        'label' => 'Ссылка на источник',
        'name' => 'award_link',
        'type' => 'url',
        'wrapper' => [
          'width' => '25',
        ],
      ],

      [
        'key' => 'field_award_file',
        'label' => 'Файл диплома / письма',
        'name' => 'award_file',
        'type' => 'file',
        'return_format' => 'array',
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
          'value' => 'award',
        ],
      ],
    ],
  ]);
});