<?php
get_header();
?>

<main class="site-main">

	<?php if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
	} ?>

	<div class="container">
		<?php
		echo 'Archive link: ' . get_post_type_archive_link('review');

		?>

		<?php if (have_posts()): ?>
			<?php while (have_posts()):
				the_post(); ?>

				<article id="post-<?php the_ID(); ?>"
						 		<?php post_class('review-page'); ?>>

					<header class="review-page__header">
						<h1 class="review-page__title"><?php the_title(); ?></h1>

						<div class="review-page__meta">
							<time class="review-page__date"
										datetime="<?php echo get_the_date('c'); ?>">
								<?php echo get_the_date('j F Y'); ?>
							</time>
						</div>
					</header>

					<div class="review-page__body">
						<div class="review-page__content">
							<?php the_content(); ?>
						</div>

						<?php
						// если планируешь ACF (рейтинг, источник и т.п.), можно вывести здесь
						?>
					</div>

				</article>

			<?php endwhile; ?>
		<?php else: ?>
			<div class="review-page review-page--empty">
				<p>Отзыв не найден.</p>
			</div>
		<?php endif; ?>

	</div>
</main>

<?php
get_footer();