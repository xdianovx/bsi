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

	<?php
	/*
	 * Оглавление по H2 (галка ACF `bsi_page_toc`, inc/helpers/page-toc.php).
	 * Контент прогоняется здесь, а не в content-page.php: оглавление
	 * стоит в разметке раньше текста, а id заголовкам нужны в обоих местах.
	 */
	$page_toc = ['html' => '', 'items' => []];
	if (have_posts() && bsi_page_toc_enabled((int) get_queried_object_id())) {
		the_post();
		$page_toc = bsi_page_toc_build((string) apply_filters('the_content', get_the_content()));
		if (count($page_toc['items']) < BSI_PAGE_TOC_MIN_ITEMS) {
			$page_toc['items'] = [];
		}
		rewind_posts();
	}
	set_query_var('page_toc_html', $page_toc['items'] ? $page_toc['html'] : '');
	set_query_var('page_toc_items', $page_toc['items']);
	?>

	<section>
		<div class="container">
			<?php if ($page_toc['items']): ?>
				<div class="page-toc-layout">
					<aside class="page-toc-aside">
						<?php get_template_part('template-parts/ui/page-toc'); ?>
					</aside>
					<div class="editor-content page-toc-content">
						<?php
						while (have_posts()):
							the_post();
							get_template_part('template-parts/content', 'page');
						endwhile;
						?>
					</div>
				</div>
			<?php else: ?>
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
			<?php endif; ?>
		</div>
	</section>


</main><!-- #main -->

<?php
// get_sidebar();
get_footer();
