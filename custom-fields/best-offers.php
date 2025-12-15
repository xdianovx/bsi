<?php


add_action('acf/init', function () {
  acf_add_local_field_group([
    'key' => 'group_offer_collection',
    'title' => 'Содержимое подборки',
    'fields' => [
      [
        'key' => 'field_offer_sections',
        'label' => 'Блоки',
        'name' => 'offer_sections',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить блок',
        'sub_fields' => [

          [
            'key' => 'field_offer_section_items',
            'label' => 'Элементы',
            'name' => 'items',
            'type' => 'repeater',
            'layout' => 'row',
            'button_label' => 'Добавить элемент',
            'sub_fields' => [
              [
                'key' => 'field_offer_item_post',
                'label' => 'Сущность',
                'name' => 'post',
                'type' => 'post_object',
                'post_type' => ['hotel', 'news'],
                'return_format' => 'object',
                'ui' => 1,
                'wrapper' => ['width' => '40'],
              ],
              [
                'key' => 'field_offer_item_badges',
                'label' => 'Бейджи',
                'name' => 'badges',
                'type' => 'taxonomy',
                'taxonomy' => 'offer_badge',
                'field_type' => 'multi_select',
                'return_format' => 'object',
                'add_term' => 1,
                'save_terms' => 0,
                'load_terms' => 0,
                'wrapper' => ['width' => '30'],
              ],
              [
                'key' => 'field_offer_item_price',
                'label' => 'Цена (опционально)',
                'name' => 'price',
                'type' => 'text',
                'wrapper' => ['width' => '30'],
              ],
              [
                'key' => 'field_offer_item_title_override',
                'label' => 'Заголовок (если нужно переопределить)',
                'name' => 'title_override',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
              ],
              [
                'key' => 'field_offer_item_link_override',
                'label' => 'Ссылка (если нужно переопределить)',
                'name' => 'link_override',
                'type' => 'url',
                'wrapper' => ['width' => '50'],
              ],
              [
                'key' => 'field_offer_item_image_override',
                'label' => 'Картинка (если нужно переопределить)',
                'name' => 'image_override',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'wrapper' => ['width' => '50'],
              ],
              [
                'key' => 'field_offer_item_location_override',
                'label' => 'Локация (если нужно переопределить)',
                'name' => 'location_override',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
              ],
            ],
          ],
        ],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'offer_collection',
        ],
      ],
    ],
  ]);
});