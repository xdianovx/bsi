<?php

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_main_banners',
        'title' => 'Баннеры главной страницы',
        'fields' => [
            [
                'key' => 'field_main_banners',
                'label' => 'Баннеры',
                'name' => 'banners',
                'type' => 'repeater',
                'layout' => 'block',
                'button_label' => 'Добавить баннер',
                'sub_fields' => [
                    [
                        'key' => 'field_banner_img',
                        'label' => 'Изображение',
                        'name' => 'img',
                        'type' => 'image',
                        'return_format' => 'url',
                        'preview_size' => 'medium',
                        'library' => 'all',
                        'wrapper' => ['width' => '50'],
                    ],
                    [
                        'key' => 'field_banner_mobilnyj_banner',
                        'label' => 'Мобильный баннер',
                        'name' => 'mobilnyj_banner',
                        'type' => 'image',
                        'return_format' => 'url',
                        'preview_size' => 'medium',
                        'library' => 'all',
                        'wrapper' => ['width' => '50'],
                    ],
                    [
                        'key' => 'field_banner_title',
                        'label' => 'Заголовок',
                        'name' => 'title',
                        'type' => 'text',
                        'wrapper' => ['width' => '50'],
                    ],
                    [
                        'key' => 'field_banner_text',
                        'label' => 'Текст',
                        'name' => 'text',
                        'type' => 'textarea',
                        'rows' => 4,
                        'new_lines' => 'br',
                        'wrapper' => ['width' => '50'],
                    ],
                    [
                        'key' => 'field_banner_url',
                        'label' => 'Ссылка',
                        'name' => 'url',
                        'type' => 'url',
                    ],
                    [
                        'key' => 'field_main_banner_bsi_active_from',
                        'label' => 'Показывать с',
                        'name' => 'bsi_active_from',
                        'type' => 'date_picker',
                        'instructions' => 'Пусто — без ограничения по началу.',
                        'display_format' => 'd.m.Y',
                        'return_format' => 'Ymd',
                        'first_day' => 1,
                        'wrapper' => ['width' => '50'],
                    ],
                    [
                        'key' => 'field_main_banner_bsi_active_until',
                        'label' => 'Показывать до',
                        'name' => 'bsi_active_until',
                        'type' => 'date_picker',
                        'instructions' => 'Пусто — без ограничения по окончанию.',
                        'display_format' => 'd.m.Y',
                        'return_format' => 'Ymd',
                        'first_day' => 1,
                        'wrapper' => ['width' => '50'],
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_type',
                    'operator' => '==',
                    'value' => 'front_page',
                ],
            ],
        ],
    ]);
});

