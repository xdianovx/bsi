<?php
/**
 * Duplicate post as draft — row action and handler.
 *
 * Adds "Копировать в черновик" to post/page/CPT list rows and creates
 * a draft copy with meta and taxonomies.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Meta keys to exclude from duplication (e.g. view counters).
 *
 * @return array<string>
 */
function bsi_duplicate_post_excluded_meta_keys(): array {
	return apply_filters('bsi_duplicate_post_excluded_meta_keys', [
		'news_views',
	]);
}

/**
 * Add "Копировать в черновик" to row actions for posts and pages.
 */
function bsi_duplicate_post_row_actions(array $actions, \WP_Post $post): array {
	if (!current_user_can('edit_post', $post->ID)) {
		return $actions;
	}

	$url = wp_nonce_url(
		add_query_arg(
			[
				'action' => 'bsi_duplicate_post_as_draft',
				'post'   => $post->ID,
			],
			admin_url('admin.php')
		),
		'bsi_duplicate_post_' . $post->ID,
		'_wpnonce'
	);

	$actions['bsi_duplicate'] = sprintf(
		'<a href="%s" aria-label="%s">%s</a>',
		esc_url($url),
		esc_attr__('Копировать в черновик', 'bsi'),
		esc_html__('Копировать в черновик', 'bsi')
	);

	return $actions;
}

add_filter('post_row_actions', 'bsi_duplicate_post_row_actions', 10, 2);
add_filter('page_row_actions', 'bsi_duplicate_post_row_actions', 10, 2);

/**
 * Handle duplicate post as draft request.
 */
function bsi_duplicate_post_as_draft_handler(): void {
	if (empty($_GET['post']) || !isset($_GET['_wpnonce'])) {
		wp_die(esc_html__('Не указана запись для копирования.', 'bsi'), '', ['response' => 400]);
	}

	$post_id = absint($_GET['post']);
	if (!$post_id) {
		wp_die(esc_html__('Неверный ID записи.', 'bsi'), '', ['response' => 400]);
	}

	if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bsi_duplicate_post_' . $post_id)) {
		wp_die(esc_html__('Проверка безопасности не пройдена.', 'bsi'), '', ['response' => 403]);
	}

	$post = get_post($post_id);
	if (!$post || !($post instanceof \WP_Post)) {
		wp_die(esc_html__('Запись не найдена.', 'bsi'), '', ['response' => 404]);
	}

	if (!current_user_can('edit_post', $post->ID)) {
		wp_die(esc_html__('У вас нет прав для копирования этой записи.', 'bsi'), '', ['response' => 403]);
	}

	$new_post_id = bsi_duplicate_post_create_draft($post);
	if (is_wp_error($new_post_id)) {
		wp_die(
			esc_html($new_post_id->get_error_message()),
			'',
			['response' => 500]
		);
	}

	wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
	exit;
}

add_action('admin_action_bsi_duplicate_post_as_draft', 'bsi_duplicate_post_as_draft_handler');

/**
 * Create a draft copy of a post with meta and taxonomies.
 *
 * @param \WP_Post $post Source post.
 * @return int|\WP_Error New post ID or error.
 */
function bsi_duplicate_post_create_draft(\WP_Post $post) {
	$pt = get_post_type_object($post->post_type);
	if (!$pt || !current_user_can($pt->cap->create_posts)) {
		return new \WP_Error('capability', __('У вас нет прав для создания записей этого типа.', 'bsi'));
	}

	$new_post_args = [
		'post_title'   => $post->post_title,
		'post_content' => $post->post_content,
		'post_excerpt' => $post->post_excerpt,
		'post_status'  => 'draft',
		'post_type'    => $post->post_type,
		'post_author'  => get_current_user_id(),
		'post_parent'  => $post->post_parent,
		'menu_order'   => $post->menu_order,
		'comment_status' => $post->comment_status,
		'ping_status'  => $post->ping_status,
	];

	$new_post_id = wp_insert_post($new_post_args, true);
	if (is_wp_error($new_post_id)) {
		return $new_post_id;
	}

	// Copy post meta.
	$excluded = bsi_duplicate_post_excluded_meta_keys();
	$meta     = get_post_custom($post->ID);

	foreach ($meta as $key => $values) {
		if (in_array($key, $excluded, true)) {
			continue;
		}
		foreach ((array) $values as $value) {
			$value = maybe_unserialize($value);
			add_post_meta($new_post_id, $key, $value);
		}
	}

	// Copy taxonomies for this post type.
	$taxonomies = get_object_taxonomies($post->post_type, 'objects');
	foreach ($taxonomies as $tax) {
		if ($tax->public === false) {
			continue;
		}
		$terms = get_the_terms($post->ID, $tax->name);
		if (!is_array($terms)) {
			continue;
		}
		$term_ids = array_filter(array_map(function (\WP_Term $t) {
			return $t->term_id;
		}, $terms));
		if ($term_ids !== []) {
			wp_set_object_terms($new_post_id, $term_ids, $tax->name);
		}
	}

	// Thumbnail.
	$thumb_id = (int) get_post_thumbnail_id($post->ID);
	if ($thumb_id) {
		set_post_thumbnail($new_post_id, $thumb_id);
	}

	return $new_post_id;
}
