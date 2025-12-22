<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_vizy_page_fields',
    'title' => 'Визы блоки',
    'fields' => [

      // Преимущества
      [
        'key' => 'field_vizy_benefits',
        'label' => 'Преимущества',
        'name' => 'vizy_benefits',
        'type' => 'repeater',
        'layout' => 'row',
        'button_label' => 'Добавить преимущество',
        'sub_fields' => [
          [
            'key' => 'field_vizy_benefit_image',
            'label' => 'Картинка',
            'name' => 'image',
            'type' => 'image',
            'return_format' => 'array',
            'preview_size' => 'medium',
            'library' => 'all',
          ],
          [
            'key' => 'field_vizy_benefit_title',
            'label' => 'Заголовок',
            'name' => 'title',
            'type' => 'text',
          ],
          [
            'key' => 'field_vizy_benefit_description',
            'label' => 'Описание',
            'name' => 'description',
            'type' => 'textarea',
            'new_lines' => 'br',
          ],
        ],
      ],

      // Процедура оформления
      [
        'key' => 'field_vizy_procedure',
        'label' => 'Процедура оформления',
        'name' => 'vizy_procedure',
        'type' => 'repeater',
        'layout' => 'row',
        'button_label' => 'Добавить шаг',
        'sub_fields' => [
          [
            'key' => 'field_vizy_proc_image',
            'label' => 'Картинка',
            'name' => 'image',
            'type' => 'image',
            'return_format' => 'array',
            'preview_size' => 'thumbnail',
            'library' => 'all',
          ],
          [
            'key' => 'field_vizy_proc_order',
            'label' => 'Порядковый номер',
            'name' => 'order',
            'type' => 'number',
            'default_value' => 0,
            'min' => 0,
          ],
          [
            'key' => 'field_vizy_proc_title',
            'label' => 'Заголовок',
            'name' => 'title',
            'type' => 'text',
          ],
          [
            'key' => 'field_vizy_proc_description',
            'label' => 'Описание',
            'name' => 'description',
            'type' => 'textarea',
            'new_lines' => 'br',
          ],
        ],
      ],

      // Контакты
      [
        'key' => 'field_vizy_contacts',
        'label' => 'Контакты',
        'name' => 'vizy_contacts',
        'type' => 'repeater',
        'layout' => 'row',
        'button_label' => 'Добавить контакт',
        'sub_fields' => [
          [
            'key' => 'field_vizy_contact_name',
            'label' => 'Имя',
            'name' => 'name',
            'type' => 'text',
          ],
          [
            'key' => 'field_vizy_contact_direction',
            'label' => 'Направление',
            'name' => 'direction',
            'type' => 'text',
          ],
          [
            'key' => 'field_vizy_contact_phone_text',
            'label' => 'Телефон (текст)',
            'name' => 'phone_text',
            'type' => 'text',
          ],
          [
            'key' => 'field_vizy_contact_phone',
            'label' => 'Телефон (номер)',
            'name' => 'phone',
            'type' => 'text',
          ],
          [
            'key' => 'field_vizy_contact_email',
            'label' => 'Почта',
            'name' => 'email',
            'type' => 'email',
          ],
        ],
      ],

    ],

    // Показываем только на странице “Vizы”
    'location' => [
      [
        [
          'param' => 'page_template',
          'operator' => '==',
          'value' => 'page-visa.php',
        ],
      ],
    ],
  ]);
});