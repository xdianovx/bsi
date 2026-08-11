<?php
/**
 * Auto-regenerate slug when the title of an unpublished post changes.
 *
 * Duplicated posts inherit the source slug (foo-1, foo-2, ...). WordPress never
 * refreshes post_name after the first save, so renaming the copy leaves the old
 * slug behind. While the post has not been published yet and its slug is still
 * the auto-generated one, rebuild it from the current title.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Post statuses that are still considered "not published".
 *
 * @return array<string>
 */
function bsi_auto_slug_unpublished_statuses(): array {
	return ['auto-draft', 'draft', 'pending'];
}

/**
 * Whether the stored slug was auto-generated from the given title.
 */
function bsi_auto_slug_is_generated(string $slug, string $title): bool {
	if ($slug === '') {
		return true;
	}

	$base = sanitize_title($title);
	if ($base === '') {
		return false;
	}

	return (bool) preg_match('/^' . preg_quote($base, '/') . '(-\d+)?$/', $slug);
}

/**
 * Rebuild post_name from the new title for unpublished posts.
 *
 * @param array<string, mixed> $data    Sanitized post data.
 * @param array<string, mixed> $postarr Raw post data.
 * @return array<string, mixed>
 */
function bsi_auto_slug_on_title_change(array $data, array $postarr): array {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $data;
	}

	if (($data['post_type'] ?? '') === 'revision' || empty($postarr['ID'])) {
		return $data;
	}

	$post_id = (int) $postarr['ID'];
	$old     = get_post($post_id);
	if (!$old instanceof \WP_Post) {
		return $data;
	}

	// Only while the post has never been published.
	if (!in_array($old->post_status, bsi_auto_slug_unpublished_statuses(), true)) {
		return $data;
	}

	$new_title = (string) ($data['post_title'] ?? '');
	if ($new_title === '' || $new_title === $old->post_title) {
		return $data;
	}

	// Respect a slug the editor typed by hand.
	if (!bsi_auto_slug_is_generated((string) $old->post_name, (string) $old->post_title)) {
		return $data;
	}

	$base = sanitize_title($new_title);
	if ($base === '') {
		return $data;
	}

	$data['post_name'] = wp_unique_post_slug(
		$base,
		$post_id,
		(string) ($data['post_status'] ?? $old->post_status),
		(string) ($data['post_type'] ?? $old->post_type),
		(int) ($data['post_parent'] ?? $old->post_parent)
	);

	return $data;
}

add_filter('wp_insert_post_data', 'bsi_auto_slug_on_title_change', 20, 2);
