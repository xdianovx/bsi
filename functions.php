<?php
/**
 * bsi functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package bsi
 */

if (!defined('_S_VERSION')) {
	// Replace the version number of the theme on each release.
	define('_S_VERSION', '1.0.0');
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function bsi_setup()
{
	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on bsi, use a find and replace
	 * to change 'bsi' to the name of your theme in all the template files.
	 */
	load_theme_textdomain('bsi', get_template_directory() . '/languages');

	// Add default posts and comments RSS feed links to head.
	add_theme_support('automatic-feed-links');

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support('title-tag');

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
	 */
	add_theme_support('post-thumbnails');

	function add_excerpt_to_pages()
	{
		add_post_type_support('page', 'excerpt');
	}
	add_action('init', 'add_excerpt_to_pages');

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__('Primary', 'bsi'),
		)
	);

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
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

	// Set up the WordPress core custom background feature.
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

	// Add theme support for selective refresh for widgets.
	add_theme_support('customize-selective-refresh-widgets');

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',

	);
}
add_action('after_setup_theme', 'bsi_setup');

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function bsi_content_width()
{
	$GLOBALS['content_width'] = apply_filters('bsi_content_width', 640);
}
add_action('after_setup_theme', 'bsi_content_width', 0);

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
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

/**
 * Enqueue scripts and styles.
 */
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

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
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

// Post Types
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

// Samo
require_once get_template_directory() . '/inc/samo/config.php';
require_once get_template_directory() . '/inc/samo/SamoClient.php';
require_once get_template_directory() . '/inc/samo/SamoParams.php';
require_once get_template_directory() . '/inc/samo/SamoEndpoints.php';
require_once get_template_directory() . '/inc/samo/SamoService.php';
require_once get_template_directory() . '/inc/samo/ajax/routes.php';

// custom fields
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
require get_template_directory() . '/custom-fields/education-fields.php';

// AJAX requests
require get_template_directory() . '/inc/requests/ajax.php';
require get_template_directory() . '/inc/requests/ajax-fit.php';
require get_template_directory() . '/inc/requests/samo.php';
require get_template_directory() . '/inc/requests/news.php';
require get_template_directory() . '/inc/requests/promo-filter.php';
require get_template_directory() . '/inc/requests/resort-hotels.php';
require get_template_directory() . '/inc/requests/country-tours.php';
require get_template_directory() . '/inc/requests/popular-hotels-section.php';
require get_template_directory() . '/inc/requests/projects.php';
require get_template_directory() . '/inc/requests/education-filter.php';

// Разрешаем query параметры для страниц с шаблоном "Образование"
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

	// Если есть query параметры фильтров, не возвращаем 404
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
	// Отключаем проверку обновлений
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
			[
				[
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'tour',
				],
			]

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

// /wp-admin/?sync_country_regions=1


add_action('wp_enqueue_scripts', function () {
	// твой основной бандл
	wp_enqueue_script('main', get_template_directory_uri() . '/assets/js/main.js', [], null, true);

	// ключ лучше хранить в wp-config.php или option
	$key = defined('YMAPS_KEY') ? YMAPS_KEY : '';

	wp_add_inline_script('main', 'window.YMAPS_KEY = ' . wp_json_encode($key) . ';', 'before');
});








