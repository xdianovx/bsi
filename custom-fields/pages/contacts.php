<?php

add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_contacts_staff',
    'title' => 'Сотрудники',
    'fields' => [
      [
        'key' => 'field_staff_groups',
        'label' => 'Разделы сотрудников',
        'name' => 'staff_groups',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить раздел',
        'sub_fields' => [
          [
            'key' => 'field_staff_group_title',
            'label' => 'Название раздела',
            'name' => 'group_title',
            'type' => 'text',
          ],
          [
            'key' => 'field_staff_group_items',
            'label' => 'Сотрудники',
            'name' => 'group_items',
            'type' => 'repeater',
            'layout' => 'row',
            'button_label' => 'Добавить сотрудника',
            'sub_fields' => [
              [
                'key' => 'field_staff_name',
                'label' => 'ФИО',
                'name' => 'name',
                'type' => 'text',
              ],
              [
                'key' => 'field_staff_position',
                'label' => 'Должность',
                'name' => 'position',
                'type' => 'text',
              ],
              [
                'key' => 'field_staff_email',
                'label' => 'Email',
                'name' => 'email',
                'type' => 'email',
              ],
              [
                'key' => 'field_staff_phone_inner',
                'label' => 'Внутренний номер',
                'name' => 'phone_inner',
                'type' => 'text',
              ],
            ],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'page',
          'operator' => '==',
          'value' => '2130',
        ],
      ],
    ],
  ]);
});