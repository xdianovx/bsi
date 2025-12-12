<?php
get_header();

$alphabet = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'];

$countries = get_posts([
	'post_type' => 'country',
	'posts_per_page' => -1,
	'orderby' => 'title',
	'order' => 'ASC',
	'post_parent' => 0
]);

$grouped_countries = [];

foreach ($countries as $country) {
	$title = $country->post_title;
	$first_letter = mb_substr($title, 0, 1, 'UTF-8');
	$first_letter_upper = mb_strtoupper($first_letter, 'UTF-8');

	if (in_array($first_letter_upper, $alphabet)) {
		if (!isset($grouped_countries[$first_letter_upper])) {
			$grouped_countries[$first_letter_upper] = [];
		}
		$grouped_countries[$first_letter_upper][] = $country;
	}
}
?>

<main class="site-main">

	<?php if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
	} ?>


	<div class="container">
		<div class="countries-letter__wrap">
			<?php foreach ($alphabet as $letter): ?>
				<?php if (isset($grouped_countries[$letter]) && !empty($grouped_countries[$letter])): ?>

					<div class="countries-letter__group">
						<p class="countries-letter__letter"><?php echo $letter; ?></p>
						<div class="countries-letter__list">

							<?php foreach ($grouped_countries[$letter] as $country): ?>

								<div class="countries-letter__item">
									<a href="<?php echo get_permalink($country->ID); ?>"
										 class="countries-letter__link">


										<?php if (get_field('flag', $country->ID)): ?>
											<img src="<?php echo get_field('flag', $country->ID); ?>"
													 alt="<?php echo $country->post_title; ?>"
													 class="countries-letter__flag">
										<?php endif; ?>

										<div class="countries-letter__info">
											<p class="countries-letter__name"> <?php echo $country->post_title; ?></p>

											<div class="countries-letter__visa">
												<?php if (get_field('is_visa', $country->ID)): ?>
													Требуется виза
												<?php else: ?>
													Виза не нужна
												<?php endif; ?>
											</div>

										</div>


									</a>
								</div>

							<?php endforeach; ?>
						</div>
					</div>


				<?php endif; ?>
			<?php endforeach; ?>

		</div>


	</div>
</main>

<?php get_footer(); ?>