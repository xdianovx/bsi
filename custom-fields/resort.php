<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_resort_term_content',
    'title' => 'Курорт — контент',

    // ✅ наверх
    'position' => 'acf_after_title',
    'menu_order' => 0,

    'fields' => [
      [
        'key' => 'field_resort_excerpt',
        'label' => 'Краткое описание',
        'name' => 'resort_excerpt',
        'type' => 'textarea',
        'rows' => 3,
        'new_lines' => 'br',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_resort_title_locative',
        'label' => 'Название в предложном падеже',
        'name' => 'resort_title_locative',
        'type' => 'text',
        'placeholder' => 'Лондоне',
        'instructions' => 'Для заголовка «Отдых в …». Пусто — подставится автоматически по окончанию названия; '
          . 'заполняй, когда автоподстановка ошибается.',
        'wrapper' => ['width' => '100'],
      ],
      [
        'key' => 'field_resort_gallery',
        'label' => 'Галерея курорта',
        'name' => 'resort_gallery',
        'type' => 'gallery',
        'return_format' => 'array',
        'preview_size' => 'medium',
        'insert' => 'append',
        'library' => 'all',
        'min' => 0,
        'max' => 30,
        'wrapper' => ['width' => '100'],
      ],

    ],

    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'resort',
        ],
      ],
    ],
  ]);

  /* Тексты подстраниц разделов — /country/{c}/{region}/{resort}/{section}/.
     Страницы виртуальные, в базе их нет, поэтому редактируемые части живут
     на терме курорта. Поля собираются из bsi_resort_sections(): появится
     новый раздел — появится и его вкладка. */
  $section_fields = [];

  foreach (bsi_resort_sections() as $slug => $config) {
    $key = 'resort_' . str_replace('-', '_', $slug);

    $section_fields[] = [
      'key' => 'field_tab_' . $key,
      'label' => $config['crumb'],
      'type' => 'tab',
      'placement' => 'top',
    ];

    $section_fields[] = [
      'key' => 'field_' . $key . '_h1',
      'label' => 'Заголовок H1',
      'name' => $key . '_h1',
      'type' => 'text',
      'instructions' => 'Пусто — соберётся сам: «' . sprintf($config['title'], 'Лондоне') . '».',
    ];

    $section_fields[] = [
      'key' => 'field_' . $key . '_intro',
      'label' => 'Текст над списком',
      'name' => $key . '_intro',
      'type' => 'wysiwyg',
      'media_upload' => 0,
      'toolbar' => 'basic',
      'tabs' => 'visual',
      'instructions' => 'Вводный абзац под заголовком. Необязательно.',
    ];

    $section_fields[] = [
      'key' => 'field_' . $key . '_text',
      'label' => 'Текст под списком',
      'name' => $key . '_text',
      'type' => 'wysiwyg',
      'media_upload' => 0,
      'toolbar' => 'basic',
      'tabs' => 'visual',
      'instructions' => 'Показывается после карточек и пагинации — место для SEO-текста.',
    ];

    $section_fields[] = [
      'key' => 'field_' . $key . '_seo_title',
      'label' => 'SEO: заголовок вкладки',
      'name' => $key . '_seo_title',
      'type' => 'text',
      'instructions' => 'Тег <title>. Пусто — соберётся из названия раздела и количества записей.',
    ];

    $section_fields[] = [
      'key' => 'field_' . $key . '_seo_desc',
      'label' => 'SEO: описание',
      'name' => $key . '_seo_desc',
      'type' => 'textarea',
      'rows' => 2,
      'instructions' => 'Meta description, до 170 символов.',
    ];
  }

  acf_add_local_field_group([
    'key' => 'group_resort_sections',
    'title' => 'Курорт — разделы',
    'menu_order' => 10,
    'fields' => $section_fields,
    'location' => [
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => 'resort',
        ],
      ],
    ],
  ]);
});