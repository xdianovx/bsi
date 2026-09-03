<?php
/**
 * Turn off the slug-guessing redirects.
 *
 * WordPress ships two of them and both send a visitor to a post they did not
 * ask for:
 *
 * - wp_old_slug_redirect() — every rename leaves a _wp_old_slug row behind, so
 *   an old URL keeps 301-ing to whatever the post is called today.
 * - redirect_guess_404_permalink() — on a 404 WordPress picks the post whose
 *   slug starts with the requested one and redirects there.
 *
 * With several posts sharing a title (the same tour in a 4-day and a 5-day
 * version, say) the guess lands on the wrong one. Each post is reachable at its
 * own slug and nothing else; an unknown URL returns 404.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Drop the old-slug redirect.
 */
function bsi_disable_old_slug_redirect(): void {
	remove_action('template_redirect', 'wp_old_slug_redirect');
}
add_action('init', 'bsi_disable_old_slug_redirect');

add_filter('do_redirect_guess_404_permalink', '__return_false');
