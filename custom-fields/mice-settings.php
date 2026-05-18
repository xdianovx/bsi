<?php

/**
 * Подменю ACF после пункта MICE (inc/admin-menu-setup.php), иначе на части окружений parent_slug ещё нет в момент acf/init.
 */
add_action(
  'admin_menu',
  static function () {
    if (!function_exists('acf_add_options_sub_page')) {
      return;
    }
    acf_add_options_sub_page([
      'page_title'  => 'Настройки MICE',
      'menu_title'  => 'Настройки MICE',
      'menu_slug'   => 'mice-settings',
      'parent_slug' => 'mice-pages',
      'capability'  => 'manage_options',
    ]);
  },
  99
);

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key'    => 'group_mice_settings',
    'title'  => 'MICE — Настройки CTA',
    'fields' => [
      [
        'key'   => 'field_mice_cta_title',
        'label' => 'Заголовок CTA',
        'name'  => 'mice_cta_title',
        'type'  => 'text',
      ],
      [
        'key'   => 'field_mice_cta_description',
        'label' => 'Описание CTA',
        'name'  => 'mice_cta_description',
        'type'  => 'textarea',
        'rows'  => 3,
      ],
      [
        'key' => 'field_mice_reviews_slider_heading',
        'label' => 'Слайдер отзывов — заголовок',
        'name' => 'mice_reviews_slider_heading',
        'type' => 'text',
        'instructions' => 'Заголовок блока отзывов на родительской странице MICE. Слайды берутся с лендингов (MICE + деловой туризм); если там пусто — из полей ниже.',
      ],
      [
        'key' => 'field_mice_reviews_slider',
        'label' => 'Слайдер отзывов — слайды',
        'name' => 'mice_reviews_slider',
        'type' => 'repeater',
        'layout' => 'block',
        'button_label' => 'Добавить отзыв',
        'instructions' => 'Запасной набор слайдов, если на лендингах нет отзывов в ACF.',
        'sub_fields' => [
          [
            'key' => 'field_mice_review_quote',
            'label' => 'Текст',
            'name' => 'quote',
            'type' => 'textarea',
            'rows' => 5,
            'new_lines' => 'br',
          ],
          [
            'key' => 'field_mice_review_author',
            'label' => 'Имя',
            'name' => 'author_name',
            'type' => 'text',
            'wrapper' => ['width' => '50'],
          ],
          [
            'key' => 'field_mice_review_role',
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
          'param'    => 'options_page',
          'operator' => '==',
          'value'    => 'mice-settings',
        ],
      ],
    ],
  ]);
});
