<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package bsi
 */

get_header();
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
					<a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-accent">
						На главную
					</a>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
