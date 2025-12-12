<?php
add_action('init', 'register_post_types_country');

function register_post_types_country()
{
  register_post_type('country', [
    'label' => null,
    'labels' => [
      'name' => 'Страны', // основное название для типа записи
      'singular_name' => 'Страна', // название для одной записи этого типа
      'add_new' => 'Добавить страну', // для добавления новой записи
      'add_new_item' => 'Новая страна', // заголовка у вновь создаваемой записи в админ-панели.
      'edit_item' => 'Редактирование страны', // для редактирования типа записи
      'new_item' => 'Новое ____', // текст новой записи
      'view_item' => 'Смотреть страну', // для просмотра записи этого типа.
      'search_items' => 'Искать страну', // для поиска по этим типам записи
      'not_found' => 'Не найдено', // если в результате поиска ничего не было найдено
      'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
      'parent_item_colon' => 'Страна', // для родителей (у древовидных типов)
      'menu_name' => 'Страны', // название меню
    ],
    'description' => '',
    'public' => true,
    // 'publicly_queryable'  => null, // зависит от public
    // 'exclude_from_search' => null, // зависит от public
    // 'show_ui'             => null, // зависит от public
    // 'show_in_nav_menus'   => null, // зависит от public
    'show_in_menu' => null, // показывать ли в меню админки
    // 'show_in_admin_bar'   => null, // зависит от show_in_menu
    'show_in_rest' => null, // добавить в REST API. C WP 4.7
    'rest_base' => null, // $post_type. C WP 4.7
    'menu_position' => null,
    'menu_icon' => 'dashicons-admin-site',

    //'capability_type'   => 'post',
    //'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
    //'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
    'hierarchical' => true,
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'trackbacks', 'post-formats', 'page-attributes'], // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
    'taxonomies' => [],
    'has_archive' => true,
    'rewrite' => true,
    'query_var' => true,
  ]);

}




add_action('template_redirect', function () {
  if (is_post_type_archive('country')) {
    $countries_page = get_page_by_path('strany');
    if ($countries_page) {
      wp_redirect(get_permalink($countries_page), 301);
      exit;
    }
  }
});

// Страны, у которых есть хотя бы одна акция
function bsi_get_promo_countries()
{
  $result = [];

  $promos = get_posts([
    'post_type' => 'promo',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
  ]);

  if (empty($promos)) {
    return $result;
  }

  foreach ($promos as $promo_id) {
    $countries = get_field('promo_countries', $promo_id);

    if (empty($countries)) {
      continue;
    }

    if (!is_array($countries)) {
      $countries = [$countries];
    }

    foreach ($countries as $country_id) {
      $country_id = (int) $country_id;
      if (!$country_id) {
        continue;
      }

      if (empty($result[$country_id])) {
        $flag = get_field('flag', $country_id);
        $flag_url = '';

        if ($flag) {
          if (is_array($flag) && !empty($flag['url'])) {
            $flag_url = esc_url($flag['url']);
          } else {
            $flag_url = esc_url($flag);
          }
        }

        $result[$country_id] = [
          'id' => $country_id,
          'title' => get_the_title($country_id),
          'flag' => $flag_url,
          'count' => 0,
        ];
      }

      $result[$country_id]['count']++;
    }
  }

  // можно отсортировать по названию
  uasort($result, function ($a, $b) {
    return strcmp($a['title'], $b['title']);
  });

  return $result;
}