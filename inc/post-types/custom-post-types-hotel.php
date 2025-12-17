<?php

add_action('init', 'register_post_types_hotel');

function register_post_types_hotel()
{
  register_post_type('hotel', [
    'label' => null,
    'labels' => [
      'name' => 'Отели',
      'singular_name' => 'Отель',
      'add_new' => 'Добавить отель',
      'add_new_item' => 'Новый отель',
      'edit_item' => 'Редактирование отеля',
      'new_item' => 'Новый отель',
      'view_item' => 'Смотреть отель',
      'search_items' => 'Искать отель',
      'not_found' => 'Отели не найдены',
      'not_found_in_trash' => 'Отелей не найдено в корзине',
      'parent_item_colon' => 'Родительский отель',
      'menu_name' => 'Отели',
    ],
    'description' => '',
    'public' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 25,
    'menu_icon' => 'dashicons-building',
    'hierarchical' => false,
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'taxonomies' => ['city'],
    'has_archive' => true,
    'rewrite' => ['slug' => 'hotels'],
    'query_var' => true,
  ]);
}

/* Таксономия: Удобства (amenity) */
add_action('init', function () {
  register_taxonomy('amenity', ['hotel'], [
    'labels' => [
      'name' => 'Удобства',
      'singular_name' => 'Удобство',
      'search_items' => 'Найти удобство',
      'all_items' => 'Все удобства',
      'edit_item' => 'Редактировать удобство',
      'update_item' => 'Обновить удобство',
      'add_new_item' => 'Добавить удобство',
      'new_item_name' => 'Новое удобство',
      'menu_name' => 'Удобства',
    ],
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'hierarchical' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);
}, 25);

// Rewrite rules и query_varsцц
add_action('init', function () {
  // Для архива отелей по стране
  add_rewrite_rule(
    '^country/([^/]+)/hotel/?$',
    'index.php?country_hotels=$matches[1]',
    'top'
  );

  // Для отдельных отелей
  add_rewrite_rule(
    '^country/([^/]+)/hotel/([^/]+)/?$',
    'index.php?post_type=hotel&name=$matches[2]',
    'top'
  );
});

// Добавляем query_var
add_filter('query_vars', function ($vars) {
  $vars[] = 'country_hotels';
  return $vars;
});

// Обработка шаблона для страницы отелей страны
add_action('template_include', function ($template) {
  if (get_query_var('country_hotels')) {
    $country_slug = get_query_var('country_hotels');
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      global $country_hotels_data;
      $country_hotels_data = [
        'country' => $country,
        'country_slug' => $country_slug
      ];

      $new_template = locate_template('country-hotels.php');
      if ($new_template) {
        return $new_template;
      }
    } else {
      global $wp_query;
      $wp_query->set_404();
      status_header(404);
      return get_404_template();
    }
  }
  return $template;
});

// Кастомные ссылки для отелей
add_filter('post_type_link', function ($post_link, $post) {
  if ($post->post_type === 'hotel' && $post->post_status === 'publish') {
    $country_id = get_field('hotel_country', $post->ID);
    if ($country_id) {
      $country = get_post($country_id);
      if ($country) {
        $post_link = home_url("/country/{$country->post_name}/hotel/{$post->post_name}/");
      }
    }
  }
  return $post_link;
}, 10, 2);

// Хлебные крошки для страницы отелей страны
add_filter('wpseo_breadcrumb_links', function ($links) {
  if (get_query_var('country_hotels')) {
    $country_slug = get_query_var('country_hotels');
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      $new_links = [];

      // Главная
      $new_links[] = [
        'url' => home_url('/'),
        'text' => 'Главная'
      ];

      // Страны (архив стран)
      $countries_archive = get_post_type_archive_link('country');
      if ($countries_archive) {
        $new_links[] = [
          'url' => $countries_archive,
          'text' => 'Страны'
        ];
      }

      // Страна
      $new_links[] = [
        'url' => get_permalink($country->ID),
        'text' => $country->post_title
      ];

      // Отели (текущая страница)
      $new_links[] = [
        'text' => 'Отели'
      ];

      return $new_links;
    }
  }
  return $links;
});

// Хлебные крошки для отдельных отелей
add_filter('wpseo_breadcrumb_links', function ($links) {
  if (is_singular('hotel')) {
    $country_id = get_field('hotel_country');
    if ($country_id) {
      $country = get_post($country_id);

      // Создаем полностью новые хлебные крошки
      $new_links = [];

      // Главная
      $new_links[] = [
        'url' => home_url('/'),
        'text' => 'Главная'
      ];

      // Страны (архив стран)
      $countries_archive = get_post_type_archive_link('country');
      if ($countries_archive) {
        $new_links[] = [
          'url' => $countries_archive,
          'text' => 'Страны'
        ];
      }

      // Страна
      $new_links[] = [
        'url' => get_permalink($country->ID),
        'text' => $country->post_title
      ];

      // Отели страны
      $new_links[] = [
        'url' => home_url("/country/{$country->post_name}/hotel/"),
        'text' => 'Отели'
      ];

      // Текущий отель
      $new_links[] = [
        'text' => get_the_title()
      ];

      return $new_links;
    }
  }
  return $links;
});



// register_activation_hook(__FILE__, function () {
//   flush_rewrite_rules();
// });