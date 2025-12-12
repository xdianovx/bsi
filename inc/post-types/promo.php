<?php

add_action('init', 'bsi_register_promo_cpt');

function bsi_register_promo_cpt()
{
  $labels = [
    'name' => 'Акции',
    'singular_name' => 'Акция',
    'menu_name' => 'Акции',
    'add_new' => 'Добавить акцию',
    'add_new_item' => 'Добавить акцию',
    'edit_item' => 'Редактировать акцию',
    'new_item' => 'Новая акция',
    'view_item' => 'Просмотр акции',
    'search_items' => 'Искать акции',
    'not_found' => 'Акции не найдены',
    'not_found_in_trash' => 'В корзине акций нет',
    'all_items' => 'Все акции',
  ];

  $args = [
    'labels' => $labels,
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_position' => 20,
    'menu_icon' => 'dashicons-megaphone',
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
    'rewrite' => [
      'slug' => 'akcii',
      'with_front' => false,
    ],
    'has_archive' => false,
    'publicly_queryable' => true,
    'show_in_rest' => true,
  ];

  register_post_type('promo', $args);
}

add_action('init', 'bsi_register_promo_type_taxonomy');

function bsi_register_promo_type_taxonomy()
{
  register_taxonomy('promo_type', ['promo'], [
    'labels' => [
      'name' => 'Типы акций',
      'singular_name' => 'Тип акции',
      'search_items' => 'Найти тип',
      'all_items' => 'Все типы',
      'edit_item' => 'Редактировать тип',
      'update_item' => 'Обновить тип',
      'add_new_item' => 'Добавить тип акции',
      'new_item_name' => 'Новый тип акции',
      'menu_name' => 'Типы акций',
    ],
    'public' => true,
    'hierarchical' => false,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_rest' => true,
    'rewrite' => [
      'slug' => 'tip-akcii',
      'with_front' => false,
    ],
  ]);
}

add_filter('acf/fields/post_object/query/name=promo_countries', function ($args, $field, $post_id) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 3);

add_action('init', function () {
  add_rewrite_rule(
    '^country/([^/]+)/promo/?$',
    'index.php?country_promos=$matches[1]',
    'top'
  );

  add_rewrite_rule(
    '^country/([^/]+)/promo/([^/]+)/?$',
    'index.php?post_type=promo&name=$matches[2]',
    'top'
  );
});

add_filter('query_vars', function ($vars) {
  $vars[] = 'country_promos';
  return $vars;
});

add_action('template_include', function ($template) {
  $country_slug = get_query_var('country_promos');

  if ($country_slug) {
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      global $country_promos_data;
      $country_promos_data = [
        'country' => $country,
        'country_slug' => $country_slug,
      ];

      $new_template = locate_template('country-promo.php');
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

add_filter('post_type_link', function ($post_link, $post) {
  if ($post->post_type === 'promo' && $post->post_status === 'publish') {
    $countries = get_field('promo_countries', $post->ID);

    if (is_array($countries) && !empty($countries)) {
      $country_id = reset($countries);
    } else {
      $country_id = $countries;
    }

    if ($country_id) {
      $country = get_post($country_id);
      if ($country) {
        $post_link = home_url("/country/{$country->post_name}/promo/{$post->post_name}/");
      }
    }
  }

  return $post_link;
}, 10, 2);

add_filter('wpseo_breadcrumb_links', function ($links) {
  $country_slug = get_query_var('country_promos');

  if ($country_slug && !is_singular('promo')) {
    $country = get_page_by_path($country_slug, OBJECT, 'country');

    if ($country) {
      $new_links = [];

      $new_links[] = [
        'url' => home_url('/'),
        'text' => 'Главная',
      ];

      $countries_archive = get_post_type_archive_link('country');
      if ($countries_archive) {
        $new_links[] = [
          'url' => $countries_archive,
          'text' => 'Страны',
        ];
      }

      $new_links[] = [
        'url' => get_permalink($country->ID),
        'text' => $country->post_title,
      ];

      $new_links[] = [
        'text' => 'Акции',
      ];

      return $new_links;
    }
  }

  return $links;
});

add_filter('wpseo_breadcrumb_links', function ($links) {
  if (is_singular('promo')) {
    $countries = get_field('promo_countries');

    if (is_array($countries) && !empty($countries)) {
      $country_id = reset($countries);
    } else {
      $country_id = $countries;
    }

    if ($country_id) {
      $country = get_post($country_id);

      if ($country) {
        $new_links = [];

        $new_links[] = [
          'url' => home_url('/'),
          'text' => 'Главная',
        ];

        $countries_archive = get_post_type_archive_link('country');
        if ($countries_archive) {
          $new_links[] = [
            'url' => $countries_archive,
            'text' => 'Страны',
          ];
        }

        $new_links[] = [
          'url' => get_permalink($country->ID),
          'text' => $country->post_title,
        ];

        $new_links[] = [
          'url' => home_url("/country/{$country->post_name}/promo/"),
          'text' => 'Акции',
        ];

        $new_links[] = [
          'text' => get_the_title(),
        ];

        return $new_links;
      }
    }
  }

  return $links;
});