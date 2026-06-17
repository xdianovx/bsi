<?php
/**
 * Полное отключение комментариев и пингов.
 *
 * Комментарии используются как вектор спама и попыток инъекций,
 * на проекте не нужны. Закрываем на всех уровнях: фронт, админка,
 * REST, фиды, прямой POST в wp-comments-post.php.
 *
 * @package bsi
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Снять поддержку комментариев и трекбеков со всех типов записей.
 */
function bsi_disable_comment_support() {
	foreach (get_post_types() as $post_type) {
		if (post_type_supports($post_type, 'comments')) {
			remove_post_type_support($post_type, 'comments');
			remove_post_type_support($post_type, 'trackbacks');
		}
	}
}
add_action('init', 'bsi_disable_comment_support', 100);

/**
 * Комментарии и пинги всегда закрыты на фронте.
 */
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

/**
 * Не отдавать существующие комментарии.
 */
add_filter('comments_array', '__return_empty_array', 20, 2);

/**
 * Заблокировать прямой POST в wp-comments-post.php.
 */
function bsi_block_comment_post() {
	wp_die(
		esc_html__('Комментарии отключены.', 'bsi'),
		'',
		array('response' => 403)
	);
}
add_action('pre_comment_on_post', 'bsi_block_comment_post');

/**
 * Убрать пункт «Комментарии» из админ-меню и из тулбара.
 */
function bsi_remove_comments_admin_menu() {
	remove_menu_page('edit-comments.php');
	remove_submenu_page('options-general.php', 'options-discussion.php');
}
add_action('admin_menu', 'bsi_remove_comments_admin_menu');

function bsi_remove_comments_admin_bar() {
	if (is_admin_bar_showing()) {
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
	}
}
add_action('init', 'bsi_remove_comments_admin_bar');

/**
 * Закрыть доступ к admin-странице комментариев напрямую (по URL).
 */
function bsi_block_comments_admin_page() {
	global $pagenow;
	if ('edit-comments.php' === $pagenow) {
		wp_safe_redirect(admin_url());
		exit;
	}
}
add_action('admin_init', 'bsi_block_comments_admin_page');

/**
 * Убрать виджет последних комментариев из консоли.
 */
function bsi_remove_comments_dashboard_widget() {
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}
add_action('wp_dashboard_setup', 'bsi_remove_comments_dashboard_widget');

/**
 * Скрыть метабоксы комментариев/обсуждения в редакторе записей.
 */
function bsi_remove_comment_meta_boxes() {
	foreach (get_post_types() as $post_type) {
		remove_meta_box('commentstatusdiv', $post_type, 'normal');
		remove_meta_box('commentsdiv', $post_type, 'normal');
		remove_meta_box('trackbacksdiv', $post_type, 'normal');
	}
}
add_action('admin_menu', 'bsi_remove_comment_meta_boxes');

/**
 * Убрать комментарии из REST API.
 */
add_filter('rest_endpoints', function ($endpoints) {
	foreach (array('/wp/v2/comments', '/wp/v2/comments/(?P<id>[\d]+)') as $route) {
		if (isset($endpoints[$route])) {
			unset($endpoints[$route]);
		}
	}
	return $endpoints;
});

/**
 * Отключить фиды комментариев (ссылки и сами фиды).
 */
function bsi_disable_comment_feed() {
	wp_die(
		esc_html__('Комментарии отключены.', 'bsi'),
		'',
		array('response' => 403)
	);
}
add_action('do_feed_rss2_comments', 'bsi_disable_comment_feed', 1);
add_action('do_feed_atom_comments', 'bsi_disable_comment_feed', 1);
remove_action('wp_head', 'feed_links_extra', 3);

/**
 * Не показывать аватары/поля комментариев в админ-баре и т.п.
 */
add_filter('comments_rewrite_rules', '__return_empty_array');
