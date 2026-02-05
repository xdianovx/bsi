<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package bsi
 */

get_header();

// Получаем ссылки на страницы
$countries_page = get_page_by_path('strany');
$countries_url = $countries_page ? get_permalink($countries_page->ID) : home_url('/strany/');

$education_page = get_posts([
  'post_type' => 'page',
  'meta_key' => '_wp_page_template',
  'meta_value' => 'page-education.php',
  'posts_per_page' => 1,
  'post_status' => 'publish'
]);
$education_url = !empty($education_page) ? get_permalink($education_page[0]->ID) : home_url('/obrazovanie/');
?>

<main id="primary" class="site-main">
	<?php
	if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb(
			'<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
			'</p></div></div>'
		);
	}
	?>

	<section class="error-404 not-found">
		<div class="container">
			<div class="error-404__content">
				<h1 class="h1 error-404__title">404</h1>
				<p class="error-404__text">Страница не найдена</p>
				<p class="error-404__description">К сожалению, запрашиваемая страница не существует или была перемещена.</p>
				
				<div class="error-404__buttons">
					<a href="<?php echo esc_url($countries_url); ?>" class="btn btn-accent">
						Смотреть страны
					</a>
					<a href="<?php echo esc_url($education_url); ?>" class="btn btn-gray">
						На школы
					</a>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
