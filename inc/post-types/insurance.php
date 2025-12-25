<?php
/**
 * Register Insurance Custom Post Type
 *
 * @package bsi
 */

declare(strict_types=1);

add_action('init', 'register_post_type_insurance');

/**
 * Register Insurance post type
 *
 * @return void
 */
function register_post_type_insurance()
{
	$labels = [
		'name' => 'Страхование',
	'singular_name' => 'Страхование',
	'menu_name' => 'Страхование',
	'add_new' => 'Добавить страховку',
	'add_new_item' => 'Добавить страховку',
	'edit_item' => 'Редактировать страховку',
	'new_item' => 'Новая страховка',
	'view_item' => 'Просмотр страховки',
	'search_items' => 'Искать страховки',
	'not_found' => 'Страховки не найдены',
	'not_found_in_trash' => 'В корзине страховок нет',
	'all_items' => 'Все страховки',
	];

	register_post_type('insurance', [
		'labels' => $labels,
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'menu_position' => 23,
		'menu_icon' => 'dashicons-shield-alt',
		'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
		'has_archive' => false,
		'rewrite' => [
			'slug' => 'insurance',
			'with_front' => false,
		],
		'publicly_queryable' => true,
		'query_var' => true,
	]);
}

/**
 * Register Insurance Type Taxonomy
 *
 * @return void
 */
add_action('init', function () {
	register_taxonomy('insurance_type', ['insurance'], [
		'labels' => [
			'name' => 'Типы страхования',
			'singular_name' => 'Тип страхования',
			'search_items' => 'Найти тип',
			'all_items' => 'Все типы',
			'edit_item' => 'Редактировать тип',
			'update_item' => 'Обновить тип',
			'add_new_item' => 'Добавить тип',
			'new_item_name' => 'Новый тип',
			'menu_name' => 'Типы страхования',
		],
		'public' => true,
		'show_ui' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
		'hierarchical' => true,
		'rewrite' => [
			'slug' => 'insurance-type',
			'with_front' => false,
		],
		'query_var' => true,
	]);
}, 20);

/**
 * Ensure taxonomy is registered for insurance post type
 *
 * @return void
 */
add_action('init', function () {
	if (taxonomy_exists('insurance_type')) {
		register_taxonomy_for_object_type('insurance_type', 'insurance');
	}
}, 999);

/**
 * Custom breadcrumbs for single insurance posts
 * Path: Главная > Страхование > {Название страховки}
 *
 * @param array $links Breadcrumb links.
 * @return array Modified breadcrumb links.
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
	if (!is_singular('insurance')) {
		return $links;
	}

	$insurance_page = get_page_by_path('strahovanie');

	if (!$insurance_page) {
		return $links;
	}

	$new_links = [];
	$new_links[] = [
		'url' => home_url('/'),
		'text' => 'Главная',
	];
	$new_links[] = [
		'url' => get_permalink($insurance_page->ID),
		'text' => get_the_title($insurance_page->ID),
	];
	$new_links[] = [
		'text' => get_the_title(),
	];

	return $new_links;
}, 20);

