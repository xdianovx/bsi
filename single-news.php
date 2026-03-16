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

<main id="primary" class="site-main">

	<?php
	if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb(
			'<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
			'</p></div></div>'
		);
	}
	?>

	<section>
		<div class="container">
			<?php the_title('<h1 class="h1 single-news__title">', '</h1>'); ?>

			<!-- <p class="single-news__excerpt">
				<?= get_the_excerpt() ?>
			</p> -->

			<?php if (get_field('news_use_poster')): ?>
				<div class="single-news__poster">
					<?php the_post_thumbnail() ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="post-content-section">
		<div class="container">
			<div class="editor-content">
				<?php the_content() ?>
			</div>
		</div>
	</section>

	<?php
	$news_gallery = get_field('news_gallery');
	if (!empty($news_gallery)):
	?>
	<section class="single-news__gallery-section">
		<div class="container">
			<?php get_template_part('template-parts/sections/gallery', null, [
				'gallery' => $news_gallery,
				'id'      => 'news_gallery_' . get_the_ID(),
			]); ?>
		</div>
	</section>
	<?php endif; ?>

	<?= get_template_part('template-parts/sections/subscribe') ?>

	<?php
	$news_countries = get_field('news_countries');
	if (!empty($news_countries)):
		$country_ids = array_map('intval', (array) $news_countries);
		get_template_part('template-parts/news/news-slider', null, [
			'filter_countries' => $country_ids,
			'exclude_post'     => get_the_ID(),
			'title'            => 'Похожие новости',
		]);
	endif;
	?>

</main><!-- #main -->

<?php
// get_sidebar();
get_footer();
