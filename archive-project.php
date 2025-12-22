<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bsi
 */

get_header();
?>

<main>

	<section class="">
		<div class="container">


			<?php if (have_posts()): ?>
				<?php

				while (have_posts()):
					the_post();


					get_template_part('template-parts/content', get_post_type());

				endwhile;

				the_posts_navigation();

			else:

				get_template_part('template-parts/content', 'none');

			endif;
			?>

		</div>
	</section>
</main><!-- #main -->

<?php
get_footer();
