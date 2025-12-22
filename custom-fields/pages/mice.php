<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_mice_benefits',
    'title' => 'MICE — Преимущества',
    'fields' => [
      [
        'key' => 'field_mice_benefits',
        'label' => 'Преимущества',
        'name' => 'mice_benefits',
        'type' => 'repeater',
        'layout' => 'row',
        'button_label' => 'Добавить преимущество',
        'sub_fields' => [
          [
            'key' => 'field_mice_benefits_icon',
            'label' => 'Иконка',
            'name' => 'icon',
            'type' => 'image',
            'return_format' => 'array',
            'preview_size' => 'thumbnail',
            'library' => 'all',
            'wrapper' => ['width' => '20'],
          ],
          [
            'key' => 'field_mice_benefits_title',
            'label' => 'Заголовок',
            'name' => 'title',
            'type' => 'text',
            'wrapper' => ['width' => '30'],
          ],
          [
            'key' => 'field_mice_benefits_text',
            'label' => 'Описание',
            'name' => 'text',
            'type' => 'textarea',
            'rows' => 3,
            'new_lines' => 'br',
            'wrapper' => ['width' => '50'],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'page_template',
          'operator' => '==',
          'value' => 'page-mice.php', // если у тебя так называется шаблон
        ],
      ],
    ],
  ]);
});