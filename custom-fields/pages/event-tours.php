<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_event_tours_page_banners',
    'title' => 'Баннеры на странице каталога',
    'fields' => [
      [
        'key' => 'field_event_tours_page_banners',
        'label' => 'Баннеры',
        'name' => 'event_tours_page_banners',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить баннер',
        'sub_fields' => [
          [
            'key' => 'field_evt_banner_is_active',
            'label' => 'Показывать баннер',
            'name' => 'evt_banner_is_active',
            'type' => 'true_false',
            'ui' => 1,
            'default_value' => 1,
            'wrapper' => ['width' => '20'],
          ],
          [
            'key' => 'field_evt_banner_link',
            'label' => 'Ссылка',
            'name' => 'evt_banner_link',
            'type' => 'url',
            'wrapper' => ['width' => '40'],
          ],
          [
            'key' => 'field_evt_banner_target',
            'label' => 'Открывать в новом окне',
            'name' => 'evt_banner_target',
            'type' => 'true_false',
            'ui' => 1,
            'default_value' => 1,
            'wrapper' => ['width' => '40'],
          ],
          [
            'key' => 'field_evt_banner_image_desktop',
            'label' => 'Десктоп',
            'name' => 'evt_banner_image_desktop',
            'type' => 'image',
            'return_format' => 'array',
            'preview_size' => 'large',
            'library' => 'all',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_evt_banner_image_mobile',
            'label' => 'Мобильный',
            'name' => 'evt_banner_image_mobile',
            'type' => 'image',
            'return_format' => 'array',
            'preview_size' => 'medium',
            'library' => 'all',
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
          'value' => 'page-sobytiynye-tury.php',
        ],
      ],
    ],
  ]);
});
