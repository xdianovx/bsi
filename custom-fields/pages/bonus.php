<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_bonus_page',
    'title' => 'Страница Bonus — Настройки',
    'fields' => [
      // Бегущие строки
      [
        'key' => 'field_bonus_marquee_icon',
        'label' => 'SVG иконка для бегущей строки',
        'name' => 'bonus_marquee_icon',
        'type' => 'textarea',
        'instructions' => 'Вставьте SVG код иконки (будет использоваться для всех элементов бегущей строки)',
        'rows' => 4,
        'default_value' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7.94 8.945H16.56M7.94 8.945H3M7.94 8.945L11.59 18.996M7.94 8.945L10.34 5.012L10.89 4M16.56 8.945H21.5M16.56 8.945L12.91 18.996M16.56 8.945L14.16 5.012L13.594 4M3 8.945C3 9.365 3.079 9.785 3.236 10.181C3.448 10.716 3.878 11.209 4.736 12.194L8.203 16.17C9.6 17.772 10.298 18.574 11.126 18.868C11.2787 18.922 11.4333 18.9647 11.59 18.996M3 8.945C3 8.525 3.079 8.105 3.236 7.708C3.448 7.173 3.878 6.681 4.736 5.695C5.203 5.161 5.436 4.894 5.706 4.687C6.10742 4.38107 6.5726 4.1695 7.067 4.068C7.401 4 7.755 4 8.464 4H10.89M11.59 18.996C12.0258 19.0822 12.4742 19.0822 12.91 18.996M10.89 4H13.594M21.5 8.945C21.5 9.365 21.421 9.785 21.264 10.181C21.052 10.716 20.622 11.209 19.764 12.194L16.297 16.17C14.9 17.772 14.202 18.574 13.374 18.868C13.2226 18.9216 13.0675 18.9643 12.91 18.996M21.5 8.945C21.5 8.525 21.421 8.105 21.264 7.708C21.052 7.173 20.622 6.681 19.764 5.695C19.297 5.161 19.064 4.894 18.794 4.687C18.3926 4.38107 17.9274 4.1695 17.433 4.068C17.099 4 16.745 4 16.036 4H13.594" stroke="#EE3145" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>',
      ],
      [
        'key' => 'field_bonus_marquee_items',
        'label' => 'Элементы бегущей строки',
        'name' => 'bonus_marquee_items',
        'type' => 'repeater',
        'layout' => 'row',
        'button_label' => 'Добавить элемент',
        'sub_fields' => [
          [
            'key' => 'field_marquee_text',
            'label' => 'Текст',
            'name' => 'text',
            'type' => 'text',
            'required' => 1,
          ],
        ],
      ],
      // Уровни бонусов
      [
        'key' => 'field_bonus_level_description_icon',
        'label' => 'SVG иконка для описаний уровней',
        'name' => 'bonus_level_description_icon',
        'type' => 'textarea',
        'instructions' => 'Вставьте SVG код иконки (будет использоваться для всех описаний во всех уровнях)',
        'rows' => 4,
        'default_value' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7.94 8.945H16.56M7.94 8.945H3M7.94 8.945L11.59 18.996M7.94 8.945L10.34 5.012L10.89 4M16.56 8.945H21.5M16.56 8.945L12.91 18.996M16.56 8.945L14.16 5.012L13.594 4M3 8.945C3 9.365 3.079 9.785 3.236 10.181C3.448 10.716 3.878 11.209 4.736 12.194L8.203 16.17C9.6 17.772 10.298 18.574 11.126 18.868C11.2787 18.922 11.4333 18.9647 11.59 18.996M3 8.945C3 8.525 3.079 8.105 3.236 7.708C3.448 7.173 3.878 6.681 4.736 5.695C5.203 5.161 5.436 4.894 5.706 4.687C6.10742 4.38107 6.5726 4.1695 7.067 4.068C7.401 4 7.755 4 8.464 4H10.89M11.59 18.996C12.0258 19.0822 12.4742 19.0822 12.91 18.996M10.89 4H13.594M21.5 8.945C21.5 9.365 21.421 9.785 21.264 10.181C21.052 10.716 20.622 11.209 19.764 12.194L16.297 16.17C14.9 17.772 14.202 18.574 13.374 18.868C13.2226 18.9216 13.0675 18.9643 12.91 18.996M21.5 8.945C21.5 8.525 21.421 8.105 21.264 7.708C21.052 7.173 20.622 6.681 19.764 5.695C19.297 5.161 19.064 4.894 18.794 4.687C18.3926 4.38107 17.9274 4.1695 17.433 4.068C17.099 4 16.745 4 16.036 4H13.594" stroke="#EE3145" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>',
      ],
      [
        'key' => 'field_bonus_levels',
        'label' => 'Уровни бонусов',
        'name' => 'bonus_levels',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить уровень',
        'sub_fields' => [
          [
            'key' => 'field_level_star_image',
            'label' => 'Изображение звезды',
            'name' => 'star_image',
            'type' => 'image',
            'return_format' => 'array',
            'preview_size' => 'thumbnail',
            'library' => 'all',
            'required' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_level_name',
            'label' => 'Название уровня',
            'name' => 'level_name',
            'type' => 'text',
            'placeholder' => 'Например: ЗВЕЗДА',
            'required' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_level_number',
            'label' => 'Номер уровня',
            'name' => 'level_number',
            'type' => 'text',
            'placeholder' => 'Например: (1 уровень)',
            'required' => 1,
            'wrapper' => ['width' => '33'],
          ],
          [
            'key' => 'field_level_info',
            'label' => 'Информация об обороте',
            'name' => 'level_info',
            'type' => 'text',
            'placeholder' => 'Например: Оборот до 5 млн руб за полгода',
            'required' => 1,
          ],
          [
            'key' => 'field_level_descriptions',
            'label' => 'Описания уровня',
            'name' => 'level_descriptions',
            'type' => 'repeater',
            'layout' => 'row',
            'button_label' => 'Добавить описание',
            'sub_fields' => [
              [
                'key' => 'field_description_title',
                'label' => 'Заголовок',
                'name' => 'title',
                'type' => 'text',
                'placeholder' => 'Например: Комиссия 8%',
                'required' => 1,
                'wrapper' => ['width' => '50'],
              ],
              [
                'key' => 'field_description_text',
                'label' => 'Описание',
                'name' => 'text',
                'type' => 'textarea',
                'placeholder' => 'Например: с экскурсионных туров и образования за рубежом',
                'rows' => 2,
                'required' => 1,
                'wrapper' => ['width' => '50'],
              ],
            ],
          ],
        ],
      ],
      // Важная информация
      [
        'key' => 'field_bonus_info_items',
        'label' => 'Важная информация',
        'name' => 'bonus_info_items',
        'type' => 'repeater',
        'layout' => 'row',
        'button_label' => 'Добавить пункт',
        'sub_fields' => [
          [
            'key' => 'field_info_text',
            'label' => 'Текст пункта',
            'name' => 'text',
            'type' => 'textarea',
            'rows' => 3,
            'required' => 1,
            'new_lines' => 'br',
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'page_template',
          'operator' => '==',
          'value' => 'page-bonus.php',
        ],
      ],
    ],
  ]);
});
