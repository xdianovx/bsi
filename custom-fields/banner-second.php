<?php

add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_banner_second_fields',
    'title' => 'Настройки баннера',
    'fields' => [
      [
        'key' => 'field_banner_second_is_active',
        'label' => 'Показывать баннер',
        'name' => 'banner_is_active',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 1,
        'wrapper' => ['width' => '20'],
      ],
      [
        'key' => 'field_banner_second_link',
        'label' => 'Ссылка',
        'name' => 'banner_link',
        'type' => 'url',
        'wrapper' => ['width' => '40'],
      ],
      [
        'key' => 'field_banner_second_target',
        'label' => 'Открывать в новом окне',
        'name' => 'banner_target',
        'type' => 'true_false',
        'ui' => 1,
        'default_value' => 1,
        'wrapper' => ['width' => '40'],
      ],
      [
        'key' => 'field_banner_second_image_desktop',
        'label' => 'Десктоп',
        'name' => 'banner_image_desktop',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'large',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_banner_second_image_mobile',
        'label' => 'Мобльный',
        'name' => 'banner_image_mobile',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'banner_second',
        ],
      ],
    ],
  ]);
});
