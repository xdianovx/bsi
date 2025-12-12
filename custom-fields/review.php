<?php

add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_review_main',
    'title' => 'Данные отзыва',
    'fields' => [


      [
        'key' => 'field_review_company_logo',
        'label' => 'Логотип компании',
        'name' => 'review_company_logo',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_review_company',
        'label' => 'Компания',
        'name' => 'review_company',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_review_author_photo',
        'label' => 'Фото автора',
        'name' => 'review_author_photo',
        'type' => 'image',
        'return_format' => 'array',
        'preview_size' => 'thumbnail',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_review_author_name',
        'label' => 'Имя',
        'name' => 'review_author_name',
        'type' => 'text',

        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_review_author_position',
        'label' => 'Должность',
        'name' => 'review_author_position',
        'type' => 'text',
        'wrapper' => ['width' => '50'],
      ],


      // Блок: компания


      // Блок: детали отзыва
      [
        'key' => 'field_review_date',
        'label' => 'Дата отзыва / проекта',
        'name' => 'review_date',
        'type' => 'date_picker',
        'display_format' => 'j F Y',
        'return_format' => 'Y-m-d',
        'first_day' => 1,
        'wrapper' => ['width' => '50'],
      ],
      [
        'key' => 'field_review_text',
        'label' => 'Текст отзыва',
        'name' => 'review_text',
        'type' => 'textarea',
        'rows' => 5,
        'new_lines' => 'br',
        'wrapper' => ['width' => '100'],
      ],

      // Дополнительно: благодарственное письмо
      [
        'key' => 'field_review_thankyou_letter',
        'label' => 'Благодарственное письмо (скан/файл)',
        'name' => 'review_thankyou_letter',
        'type' => 'file',
        'return_format' => 'array',
        'library' => 'all',
        'wrapper' => ['width' => '50'],
      ],

    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'review',
        ],
      ],
    ],
  ]);
});