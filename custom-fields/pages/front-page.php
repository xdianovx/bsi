<?php

add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group')) {
    return;
  }

  acf_add_local_field_group([
    'key' => 'group_front_page',
    'title' => 'Главная страница',
    'fields' => [
      [
        'key' => 'field_homepage_education_items',
        'label' => 'Программы образования (слайдер)',
        'name' => 'homepage_education_items',
        'type' => 'relationship',
        'instructions' => 'Выберите и упорядочьте программы для отображения в слайдере на главной странице. Порядок слайдов соответствует порядку выбранных записей.',
        'post_type' => ['education'],
        'filters' => ['search'],
        'return_format' => 'id',
        'min' => 0,
        'max' => 0,
      ],
      [
        'key' => 'field_homepage_tour_items',
        'label' => 'Экскурсионные туры (слайдер)',
        'name' => 'homepage_tour_items',
        'type' => 'relationship',
        'instructions' => 'Выберите и упорядочьте туры для блока «Экскурсионные туры» на главной. Порядок слайдов = порядок выбранных записей. Это единственный способ управления блоком: если список пуст, секция на главной не выводится. Кнопки-страны над слайдером собираются из стран выбранных туров.',
        'post_type' => ['tour'],
        'filters' => ['search'],
        'return_format' => 'id',
        'min' => 0,
        'max' => 0,
      ],
      [
        'key' => 'field_homepage_event_items',
        'label' => 'Событийные туры (слайдер)',
        'name' => 'homepage_event_items',
        'type' => 'relationship',
        'instructions' => 'Выберите и упорядочьте событийные туры для блока «Событийные туры» на главной. Порядок слайдов = порядок выбранных записей. Если пусто — показываются свежие события.',
        'post_type' => ['event'],
        'filters' => ['search'],
        'return_format' => 'id',
        'min' => 0,
        'max' => 0,
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

add_filter('acf/fields/relationship/query/key=field_homepage_education_items', function (array $args): array {
  $args['posts_per_page'] = -1;
  return $args;
});

add_filter('acf/fields/relationship/query/key=field_homepage_tour_items', function (array $args): array {
  $args['posts_per_page'] = -1;
  return $args;
});

add_filter('acf/fields/relationship/query/key=field_homepage_event_items', function (array $args): array {
  $args['posts_per_page'] = -1;
  return $args;
});
