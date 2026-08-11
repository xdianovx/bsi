<?php
/**
 * CPT: entry_rules (Правила въезда)
 * URL: /country/{country}/entry-rules/
 */

/**
 * Query vars
 */
add_filter('query_vars', function ($vars) {
  if (!in_array('country_entry_rules', $vars, true))
    $vars[] = 'country_entry_rules';
  return $vars;
});

/**
 * CPT регистрация
 */
add_action('init', function () {
  register_post_type('entry_rules', [
    'labels' => [
      'name' => 'Правила въезда',
      'singular_name' => 'Правила въезда',
      'add_new' => 'Добавить',
      'add_new_item' => 'Новые правила',
      'edit_item' => 'Редактировать',
      'new_item' => 'Новые правила',
      'view_item' => 'Смотреть',
      'search_items' => 'Искать',
      'not_found' => 'Не найдено',
      'not_found_in_trash' => 'Не найдено в корзине',
      'menu_name' => 'Правила въезда',
    ],
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_rest' => true,
    'menu_position' => 25,
    'menu_icon' => 'dashicons-clipboard',
    'supports' => ['title', 'editor', 'excerpt'],
    'has_archive' => false,
    'rewrite' => false,
    'query_var' => true,
  ]);
}, 10);

/**
 * ACF: привязка к стране (под заголовком)
 */
add_action('acf/init', function () {
  if (!function_exists('acf_add_local_field_group'))
    return;

  acf_add_local_field_group([
    'key' => 'group_entry_rules_country',
    'title' => 'Правила въезда — страна',
    'position' => 'acf_after_title',
    'menu_order' => 0,
    'fields' => [
      [
        'key' => 'field_entry_rules_country',
        'label' => 'Страна',
        'name' => 'entry_rules_country',
        'type' => 'post_object',
        'post_type' => ['country'],
        'return_format' => 'id',
        'ui' => 1,
        'ajax' => 1,
        'required' => 1,
        'wrapper' => ['width' => '50'],
      ],
    ],
    'location' => [
      [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => 'entry_rules',
        ],
      ],
    ],
  ]);
});

/**
 * Ограничим страны только верхним уровнем
 */
add_filter('acf/fields/post_object/query/key=field_entry_rules_country', function ($args) {
  $args['post_parent'] = 0;
  return $args;
}, 10, 1);

/**
 * Yoast breadcrumbs для правил въезда:
 * - country entry rules: Главная > Страны > {Страна} > Правила въезда
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
  $country_slug = get_query_var('country_entry_rules');
  if (empty($country_slug)) {
    return $links;
  }

  $country = get_page_by_path($country_slug, OBJECT, 'country');
  if (!$country) {
    return $links;
  }

  $countries_page = get_page_by_path('strany');
  $countries_url = $countries_page ? get_permalink($countries_page->ID) : get_post_type_archive_link('country');

  $new = [];
  $new[] = ['url' => home_url('/'), 'text' => 'Главная'];

  if ($countries_url) {
    $new[] = ['url' => $countries_url, 'text' => $countries_page ? ($countries_page->post_title ?: 'Страны') : 'Страны'];
  }

  $new[] = ['url' => get_permalink($country->ID), 'text' => $country->post_title];
  $new[] = ['text' => 'Правила въезда'];

  return $new;
});

/**
 * Своей single-страницы нет: ссылка и прямой заход ведут на раздел страны.
 * См. inc/helpers/country-section-singular.php.
 */
bsi_register_country_section_singular('entry_rules', 'entry_rules_country', 'pravila-vyezda');
