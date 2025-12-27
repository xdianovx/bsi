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
                        'key' => 'field_banner_link',
                        'label' => 'Ссылка (акция, новость, тур и т.д.)',
                        'name' => 'link',
                        'type' => 'post_object',
                        'post_type' => ['promo', 'news', 'tour', 'hotel', 'service'],
                        'return_format' => 'object',
                        'ui' => 1,
                        'multiple' => 0,
                        'allow_null' => 1,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page',
                    'operator' => '==',
                    'value' => '50',
                ],
            ],
        ],
    ]);
});

