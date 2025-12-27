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

			<p class="single-news__excerpt">
				<?= get_the_excerpt() ?>
			</p>

			<div class="single-news__poster">
				<?php the_post_thumbnail() ?>
			</div>
		</div>
	</section>

	<section class="post-content-section">
		<div class="container">
			<div class="editor-content">
				<?php the_content() ?>
			</div>
		</div>
	</section>
	<div id="map" style="width: 600px; height: 400px"></div>

	<script>
		window.addEventListener('load', function () {
			async function initMap() {
				if (typeof ymaps3 === 'undefined') {
					console.log('Waiting for ymaps3...');
					setTimeout(initMap, 100);
					return;
				}

				await ymaps3.ready;

				const { YMap, YMapDefaultSchemeLayer } = ymaps3;

				const map = new YMap(
					document.getElementById('map'),
					{
						location: {
							center: [37.588144, 55.733842],
							zoom: 10
						}
					}
				);

				map.addChild(new YMapDefaultSchemeLayer());
			}

			initMap();
		});
	</script>

</main><!-- #main -->

<?php
// get_sidebar();
get_footer();
