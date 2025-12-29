<?php

if (!defined('_S_VERSION')) {
	define('_S_VERSION', '1.0.0');
}

function bsi_setup()
{
	load_theme_textdomain('bsi', get_template_directory() . '/languages');

	add_theme_support('automatic-feed-links');

	add_theme_support('title-tag');

	add_theme_support('post-thumbnails');

	function add_excerpt_to_pages()
	{
		add_post_type_support('page', 'excerpt');
	}
	add_action('init', 'add_excerpt_to_pages');

	register_nav_menus(
		array(
			'menu-1' => esc_html__('Primary', 'bsi'),
		)
	);

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	add_theme_support(
		'custom-background',
		apply_filters(
			'bsi_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	add_theme_support('customize-selective-refresh-widgets');

	add_theme_support(
		'custom-logo',

	);
}
add_action('after_setup_theme', 'bsi_setup');

function bsi_content_width()
{
	$GLOBALS['content_width'] = apply_filters('bsi_content_width', 640);
}
add_action('after_setup_theme', 'bsi_content_width', 0);

function bsi_widgets_init()
{
	register_sidebar(
		array(
			'name' => esc_html__('Sidebar', 'bsi'),
			'id' => 'sidebar-1',
			'description' => esc_html__('Add widgets here.', 'bsi'),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget' => '</section>',
			'before_title' => '<h2 class="widget-title">',
			'after_title' => '</h2>',
		)
	);
}


add_action('widgets_init', 'bsi_widgets_init');

function bsi_scripts()
{
	wp_enqueue_style('bsi-style', get_stylesheet_uri(), array(), time());
	wp_enqueue_style('main', get_template_directory_uri() . '/dist/css/main.min.css', [], time());

	wp_style_add_data('bsi-style', 'rtl', 'replace');

	wp_enqueue_script('main', get_template_directory_uri() . '/dist/js/main.min.js', array(), time(), true);
	wp_localize_script('main', 'ajax', array(
		'url' => admin_url('admin-ajax.php'),
	));

	wp_enqueue_script('bsi-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true);

	if (is_singular() && comments_open() && get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}
}
add_action('wp_enqueue_scripts', 'bsi_scripts');

require get_template_directory() . '/inc/custom-header.php';

require get_template_directory() . '/inc/template-tags.php';

require get_template_directory() . '/inc/template-functions.php';

require get_template_directory() . '/inc/customizer.php';

if (defined('JETPACK__VERSION')) {
	require get_template_directory() . '/inc/jetpack.php';
}

add_filter('wpseo_breadcrumb_separator', function ($separator) {
	return '<span class="breadcrumb-separator"></span>';
});

function bsi_track_news_views()
{
	if (!is_singular('news')) {
		return;
	}

	$post_id = get_queried_object_id();
	if (!$post_id) {
		return;
	}

	$views = (int) get_post_meta($post_id, 'news_views', true);
	$views++;
	update_post_meta($post_id, 'news_views', $views);
}

add_action('wp', 'bsi_track_news_views');

require get_template_directory() . '/inc/helpers.php';
require get_template_directory() . '/inc/admin-menu-setup.php';
require get_template_directory() . '/inc/menus-functions.php';

require get_template_directory() . '/inc/post-types/country.php';
require get_template_directory() . '/inc/post-types/news.php';
require get_template_directory() . '/inc/post-types/custom-post-event.php';
require get_template_directory() . '/inc/post-types/custom-post-service.php';
require get_template_directory() . '/inc/post-types/custom-post-partner.php';
require get_template_directory() . '/inc/post-types/documentation.php';
require get_template_directory() . '/inc/post-types/custom-post-types-hotel.php';
require get_template_directory() . '/inc/post-types/education.php';
require get_template_directory() . '/inc/post-types/review.php';
require get_template_directory() . '/inc/post-types/promo.php';
require get_template_directory() . '/inc/post-types/awards.php';
require get_template_directory() . '/inc/post-types/banner.php';
require get_template_directory() . '/inc/post-types/visa.php';
require get_template_directory() . '/inc/post-types/best-offers.php';
require get_template_directory() . '/inc/post-types/tour.php';
require get_template_directory() . '/inc/post-types/tourist-memo.php';
require get_template_directory() . '/inc/post-types/entry-rules.php';
require get_template_directory() . '/inc/post-types/project.php';
require get_template_directory() . '/inc/post-types/insurance.php';

require_once get_template_directory() . '/inc/samo/config.php';
require_once get_template_directory() . '/inc/samo/SamoClient.php';
require_once get_template_directory() . '/inc/samo/SamoParams.php';
require_once get_template_directory() . '/inc/samo/SamoEndpoints.php';
require_once get_template_directory() . '/inc/samo/SamoService.php';
require_once get_template_directory() . '/inc/samo/ajax/routes.php';

require get_template_directory() . '/custom-fields/hotel-fields.php';
require get_template_directory() . '/custom-fields/review.php';
require get_template_directory() . '/custom-fields/news.php';
require get_template_directory() . '/custom-fields/promo.php';
require get_template_directory() . '/custom-fields/pages/contacts.php';
require get_template_directory() . '/custom-fields/award.php';
require get_template_directory() . '/custom-fields/banner.php';
require get_template_directory() . '/custom-fields/visa.php';
require get_template_directory() . '/custom-fields/best-offers.php';
require get_template_directory() . '/custom-fields/resort.php';
require get_template_directory() . '/custom-fields/pages/mice.php';
require get_template_directory() . '/custom-fields/pages/visa.php';
require get_template_directory() . '/custom-fields/pages/main-banners.php';
require get_template_directory() . '/custom-fields/education-fields.php';
require get_template_directory() . '/custom-fields/currency-settings.php';

require get_template_directory() . '/inc/requests/ajax.php';
require get_template_directory() . '/inc/requests/ajax-fit.php';
require get_template_directory() . '/inc/requests/samo.php';
require get_template_directory() . '/inc/requests/ajax-cbr-rates.php';
require get_template_directory() . '/inc/requests/news.php';
require get_template_directory() . '/inc/requests/promo-filter.php';
require get_template_directory() . '/inc/requests/resort-hotels.php';
require get_template_directory() . '/inc/requests/country-tours.php';
require get_template_directory() . '/inc/requests/popular-hotels-section.php';
require get_template_directory() . '/inc/requests/popular-tours-section.php';
require get_template_directory() . '/inc/requests/projects.php';
require get_template_directory() . '/inc/requests/education-filter.php';

add_action('template_redirect', function () {
	if (!is_page()) {
		return;
	}

	global $post;
	if (!$post) {
		return;
	}

	$template = get_page_template_slug($post->ID);
	if ($template !== 'page-education.php') {
		return;
	}

	$has_education_params = !empty($_GET['program']) || !empty($_GET['language']) ||
		!empty($_GET['type']) || !empty($_GET['accommodation']) ||
		!empty($_GET['country']) || !empty($_GET['age_min']) ||
		!empty($_GET['age_max']) || !empty($_GET['duration_min']) ||
		!empty($_GET['duration_max']) || !empty($_GET['date_from']) ||
		!empty($_GET['date_to']) || !empty($_GET['sort']);

	if ($has_education_params) {
		global $wp_query;
		$wp_query->is_404 = false;
		status_header(200);
	}
}, 1);




add_action('acf/init', function () {
	acf_update_setting('show_updates', false);
});

add_action('acf/init', function () {
	acf_add_local_field_group([
		'key' => 'group_best_offer',
		'title' => 'Лучшее предложение',
		'fields' => [
			[
				'key' => 'field_is_best_offer',
				'label' => 'Показывать как лучшее предложение',
				'name' => 'is_best_offer',
				'type' => 'true_false',
				'ui' => 1,
				'default_value' => 0,
			],
		],
		'location' => [
			[
				[
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'hotel',
				],
			],
		],
	]);
});



add_action('admin_init', function () {
	if (!current_user_can('manage_options'))
		return;
	if (empty($_GET['sync_country_regions']))
		return;

	$country_ids = get_posts([
		'post_type' => 'country',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post_parent' => 0,
		'fields' => 'ids',
	]);

	foreach ($country_ids as $country_id) {
		$post = get_post($country_id);
		if (!$post)
			continue;

		$slug = $post->post_name;
		$title = $post->post_title;

		$existing = term_exists($slug, 'region');

		if (!$existing) {
			$created = wp_insert_term($title, 'region', ['slug' => $slug, 'parent' => 0]);
			if (!is_wp_error($created) && !empty($created['term_id'])) {
				update_term_meta((int) $created['term_id'], 'country_post_id', (int) $country_id);
				update_post_meta((int) $country_id, 'region_country_term_id', (int) $created['term_id']);
			}
			continue;
		}

		$term_id = is_array($existing) ? (int) $existing['term_id'] : (int) $existing;
		wp_update_term($term_id, 'region', ['name' => $title, 'slug' => $slug, 'parent' => 0]);
		update_term_meta($term_id, 'country_post_id', (int) $country_id);
		update_post_meta((int) $country_id, 'region_country_term_id', (int) $term_id);
	}

	wp_die('OK');
});

add_action('wp_enqueue_scripts', function () {
	wp_enqueue_script('main', get_template_directory_uri() . '/assets/js/main.js', [], null, true);
});






