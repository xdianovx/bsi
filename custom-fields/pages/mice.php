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

  acf_add_local_field_group([
    'key' => 'group_mice_parent_reviews',
    'title' => 'MICE — Отзывы (общий блок для всех страниц MICE)',
    'fields' => [
      [
        'key' => 'field_mice_page_reviews_heading',
        'label' => 'Заголовок секции',
        'name' => 'mice_page_reviews_heading',
        'type' => 'text',
        'instructions' => 'Отзывы из этого блока показываются на странице MICE и на дочерних лендингах (page-bsimice.php, page-delovoy.php). Если оставить пустым — используются старые поля на лендингах.',
      ],
      [
        'key' => 'field_mice_page_reviews',
        'label' => 'Слайды отзывов',
        'name' => 'mice_page_reviews',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить отзыв',
        'instructions' => 'Заполненные отзывы будут показаны на странице MICE и на всех дочерних лендингах.',
        'sub_fields' => [
          [
            'key' => 'field_mice_page_review_quote',
            'label' => 'Текст',
            'name' => 'quote',
            'type' => 'textarea',
            'rows' => 5,
            'new_lines' => 'br',
          ],
          [
            'key' => 'field_mice_page_review_author',
            'label' => 'Имя',
            'name' => 'author_name',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_mice_page_review_role',
            'label' => 'Должность / компания',
            'name' => 'author_title',
            'type' => 'text',
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
          'value' => 'page-mice.php',
        ],
      ],
    ],
  ]);
});
