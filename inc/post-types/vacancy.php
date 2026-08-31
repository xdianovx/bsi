<?php
/**
 * CPT «Вакансии».
 *
 * URL-схема:
 *   /vakansii/           — обычная WP-страница с шаблоном page-vakansii.php (каталог).
 *   /vakansii/{slug}/    — single-vacancy.php (карточка вакансии).
 *
 * Поэтому `has_archive => false`: архив CPT конфликтовал бы со страницей-каталогом.
 *
 * Таксономий нет: вакансий единицы, группировать их нечем и незачем
 * (решение 2026-08-31).
 *
 * @package bsi
 */

declare(strict_types=1);

add_action('init', 'bsi_register_post_type_vacancy');

/**
 * Регистрация CPT vacancy.
 *
 * @return void
 */
function bsi_register_post_type_vacancy(): void
{
	$labels = [
		'name' => 'Вакансии',
		'singular_name' => 'Вакансия',
		'menu_name' => 'Вакансии',
		'add_new' => 'Добавить вакансию',
		'add_new_item' => 'Добавить вакансию',
		'edit_item' => 'Редактировать вакансию',
		'new_item' => 'Новая вакансия',
		'view_item' => 'Просмотр вакансии',
		'search_items' => 'Искать вакансии',
		'not_found' => 'Вакансии не найдены',
		'not_found_in_trash' => 'В корзине вакансий нет',
		'all_items' => 'Все вакансии',
	];

	register_post_type('vacancy', [
		'labels' => $labels,
		'public' => true,
		'hierarchical' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'menu_position' => 25,
		'menu_icon' => 'dashicons-businessperson',
		'supports' => ['title', 'editor', 'excerpt', 'revisions'],
		'has_archive' => false,
		'rewrite' => [
			'slug' => 'vakansii',
			'with_front' => false,
		],
		'publicly_queryable' => true,
		'query_var' => true,
	]);
}

/**
 * Крошки вакансии: Главная → Вакансии → {вакансия}.
 *
 * Архива у CPT нет, поэтому Yoast сам вставить каталог не может —
 * промежуточное звено добавляем вручную, как у news/education/promo.
 */
add_filter('wpseo_breadcrumb_links', function ($links) {
	if (!is_singular('vacancy')) {
		return $links;
	}

	$new_links = [
		['url' => home_url('/'), 'text' => 'Главная'],
	];

	$catalog = get_page_by_path('vakansii');
	if ($catalog) {
		$new_links[] = [
			'url' => get_permalink($catalog->ID),
			'text' => $catalog->post_title ?: 'Вакансии',
		];
	}

	$new_links[] = [
		'url' => get_permalink(),
		'text' => get_the_title(),
	];

	return $new_links;
});
