<?php



add_action('acf/init', function () {

  acf_add_local_field_group([
    'key' => 'group_news_by_country',
    'title' => 'Новости по направлениям',
    'fields' => [
      [
        'key' => 'field_news_countries',
        'label' => 'Страны',
        'name' => 'news_countries',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'multiple' => 1,
        'allow_null' => 0,
        'ui' => 1,
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_taxonomy',
          'operator' => '==',
          'value' => 'news_type:novosti-po-napravleniyam	',
        ],
      ],
    ],
  ]);


});


add_filter('acf/fields/post_object/query/name=news_hotels', function ($args, $field, $post_id) {
  $selected_countries = null;

  if (isset($_POST['news_countries'])) {
    $selected_countries = $_POST['news_countries'];
  } else {
    $selected_countries = get_field('news_countries', $post_id);
  }

  if (empty($selected_countries)) {
    return $args;
  }

  if (!is_array($selected_countries)) {
    $selected_countries = [$selected_countries];
  }

  $args['meta_query'] = [
    [
      'key' => 'hotel_country',
      'value' => $selected_countries,
      'compare' => 'IN',
    ],
  ];

  return $args;
}, 10, 3);