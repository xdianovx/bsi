<?php
get_header();


?>

<main class="site-main">

	<?php if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
	} ?>


	<div class="container">
		<h1 class="title-h1 h1"><?php post_type_archive_title(); ?></h1>

		<?php if (have_posts()): ?>
			<div class="service-archive-list">

				<?php while (have_posts()):
					the_post(); ?>
					<div id="service-item"
					 		<?php post_class(); ?>>
						<a href="<?php the_permalink(); ?>"
							 class="service-item__link">
							<h3><?php the_title(); ?></h3>
						</a>

					</div>
				<?php endwhile; ?>
			</div>

			<!-- навигация по страницам -->
			<?php the_posts_navigation(); ?>
		<?php else: ?>
			<p><?php esc_html_e('Услуги не найдены', 'bsi'); ?></p>
		<?php endif; ?>
	</div>
</main>

<?php get_footer(); ?>