<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package asd
 */

get_header();
?>

<main id="primary"
			class="site-main">

	<?php
	if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb(
			'<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
			'</p></div></div>'
		);
	}
	?>

	<?php
	/*
	 * Заголовок выводит template-parts/content-page.php.
	 * Раньше здесь стоял ещё один the_title() внутри HTML-комментария —
	 * PHP в комментарии всё равно выполняется, поэтому в разметке
	 * оказывалось два H1, и краулеры считали это ошибкой структуры.
	 */
	?>

	<section>
		<div class="container">
			<div class="editor-content">
				<?php
				while (have_posts()):
					the_post();

					get_template_part('template-parts/content', 'page');

					// If comments are open or we have at least one comment, load up the comment template.
					if (comments_open() || get_comments_number()):
						comments_template();
					endif;

				endwhile;
				?>
			</div>
		</div>
	</section>


</main><!-- #main -->

<?php
// get_sidebar();
get_footer();
