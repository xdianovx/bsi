<?php
/**
 * Keep post_name in sync with the post title.
 *
 * Duplicated posts inherit the source slug (foo-1, foo-2, ...) and WordPress
 * never refreshes post_name afterwards, so renaming the copy leaves the old
 * slug behind. Editors do not think about slugs, so the slug follows the title
 * on every rename — for drafts and for published posts alike. WordPress stores
 * _wp_old_slug on published posts, so the previous URL keeps redirecting.
 *
 * A slug the editor typed by hand is remembered in the _bsi_manual_slug meta
 * and is never touched again.
 *
 * Runs in two passes so Cyr-To-Lat (wp_insert_post_data, priority 10) can do
 * the transliteration: pass one empties post_name, pass two fills it in if no
 * other plugin did.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

const BSI_MANUAL_SLUG_META = '_bsi_manual_slug';

/**
 * Post types whose slug is never managed here.
 *
 * @return array<string>
 */
function bsi_auto_slug_ignored_post_types(): array {
	return ['revision', 'attachment', 'nav_menu_item', 'acf-field', 'acf-field-group', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation'];
}

/**
 * Slug WordPress would generate from the given title, transliterated the same
 * way the site does it (Cyr-To-Lat when active).
 */
function bsi_auto_slug_base(string $title): string {
	global $cyr_to_lat_plugin;

	if (is_object($cyr_to_lat_plugin) && method_exists($cyr_to_lat_plugin, 'sanitize_explicit_slug')) {
		return (string) $cyr_to_lat_plugin->sanitize_explicit_slug($title);
	}

	return sanitize_title($title);
}

/**
 * Whether "slug-3" is the uniqueness suffix WordPress added on top of another
 * post's slug — the signature of a duplicated post.
 */
function bsi_auto_slug_is_copy_of_existing(string $slug, int $post_id): bool {
	if (!preg_match('/^(.+)-\d+$/', $slug, $matches)) {
		return false;
	}

	global $wpdb;

	$owner = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND ID != %d LIMIT 1",
			$matches[1],
			$post_id
		)
	);

	return null !== $owner;
}

/**
 * Whether the stored slug looks auto-generated rather than hand-written.
 */
function bsi_auto_slug_is_generated(string $slug, string $title, int $post_id = 0): bool {
	if ($slug === '') {
		return true;
	}

	// Cyr-To-Lat transliterates in the admin, but posts created elsewhere may
	// carry the url-encoded slug WordPress builds on its own.
	$bases      = [bsi_auto_slug_base($title), sanitize_title($title)];
	$candidates = array_unique(array_filter(array_merge($bases, array_map('rawurldecode', $bases))));
	$decoded    = rawurldecode($slug);

	foreach ($candidates as $base) {
		$pattern = '/^' . preg_quote($base, '/') . '(-\d+)?$/';
		if (preg_match($pattern, $slug) || preg_match($pattern, $decoded)) {
			return true;
		}
	}

	// Copies keep the source slug even when the plugin prefixed their title.
	return bsi_auto_slug_is_copy_of_existing($slug, $post_id);
}

/**
 * Slugs queued for regeneration: post ID => slug to fall back to.
 *
 * @param int|null    $post_id Post to read/write, null to read the whole map.
 * @param string|null $slug    Fallback slug to store.
 * @return array<int, string>
 */
function bsi_auto_slug_queue(?int $post_id = null, ?string $slug = null): array {
	static $queue = [];

	if (null !== $post_id && null !== $slug) {
		$queue[$post_id] = $slug;
	}

	return $queue;
}

/**
 * Pass one: decide whether the slug must follow the new title and clear it.
 *
 * @param array<string, mixed> $data    Sanitized post data.
 * @param array<string, mixed> $postarr Raw post data.
 * @return array<string, mixed>
 */
function bsi_auto_slug_prepare(array $data, array $postarr): array {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $data;
	}

	$post_type = (string) ($data['post_type'] ?? '');
	if (in_array($post_type, bsi_auto_slug_ignored_post_types(), true) || empty($postarr['ID'])) {
		return $data;
	}

	$post_id = (int) $postarr['ID'];
	$old     = get_post($post_id);
	if (!$old instanceof \WP_Post || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return $data;
	}

	$new_slug = (string) ($data['post_name'] ?? '');

	// The editor typed a slug in this very save — remember it and stay away.
	if ($new_slug !== '' && $new_slug !== $old->post_name) {
		update_post_meta($post_id, BSI_MANUAL_SLUG_META, '1');

		return $data;
	}

	if (get_post_meta($post_id, BSI_MANUAL_SLUG_META, true)) {
		return $data;
	}

	$new_title = (string) ($data['post_title'] ?? '');
	if ($new_title === '' || $new_title === $old->post_title) {
		return $data;
	}

	// A slug that does not derive from the old title was written by hand.
	if (!bsi_auto_slug_is_generated((string) $old->post_name, (string) $old->post_title, $post_id)) {
		update_post_meta($post_id, BSI_MANUAL_SLUG_META, '1');

		return $data;
	}

	bsi_auto_slug_queue($post_id, (string) $old->post_name);
	$data['post_name'] = '';

	return $data;
}

/**
 * Pass two: build the slug if Cyr-To-Lat (or anything else) left it empty.
 *
 * @param array<string, mixed> $data    Sanitized post data.
 * @param array<string, mixed> $postarr Raw post data.
 * @return array<string, mixed>
 */
function bsi_auto_slug_finish(array $data, array $postarr): array {
	$post_id = (int) ($postarr['ID'] ?? 0);
	$queue   = bsi_auto_slug_queue();

	if (!$post_id || !array_key_exists($post_id, $queue)) {
		return $data;
	}

	if ((string) ($data['post_name'] ?? '') !== '') {
		return $data;
	}

	$base = bsi_auto_slug_base((string) ($data['post_title'] ?? ''));
	if ($base === '') {
		// Nothing usable — keep the slug the post already had.
		$data['post_name'] = $queue[$post_id];

		return $data;
	}

	$data['post_name'] = wp_unique_post_slug(
		$base,
		$post_id,
		(string) ($data['post_status'] ?? ''),
		(string) ($data['post_type'] ?? ''),
		(int) ($data['post_parent'] ?? 0)
	);

	return $data;
}

add_filter('wp_insert_post_data', 'bsi_auto_slug_prepare', 5, 2);
add_filter('wp_insert_post_data', 'bsi_auto_slug_finish', 20, 2);
