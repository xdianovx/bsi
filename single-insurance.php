<?php
/**
 * Template for displaying single insurance posts
 *
 * @package bsi
 */

declare(strict_types=1);

get_header();
?>

<main class="site-main">

	<?php if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
	} ?>

	<?php
	while (have_posts()):
		the_post();
		$insurance_id = get_the_ID();
		?>

		<section class="page-head archive-page-head">
			<div class="container">
				<div class="archive-page__top">
					<h1 class="h1 page-award__title archive-page__title">
						<?php the_title(); ?>
					</h1>

					<?php if (has_excerpt()): ?>
						<p class="page-award__excerpt archive-page__excerpt">
							<?= get_the_excerpt(); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</section>


		<?php
		/**
		 * Информационная секция (ACF поле insurance_info)
		 */
		if (function_exists('have_rows') && have_rows('insurance_info', $insurance_id)): ?>
			<section class="insurance-page__info-section">
				<div class="container">
					<div class="insurance-page__info">
						<div class="insurance-info-item__wrap">
							<?php while (have_rows('insurance_info', $insurance_id)):
								the_row(); ?>
								<?php
								$icon = get_sub_field('icon');
								$key = (string) get_sub_field('key');
								$value = (string) get_sub_field('value');
								?>

								<?php if ($key || $value): ?>
									<div class="insurance-info-item">
										<?php if ($key): ?>
											<div class="insurance-info-item__title">
												<?php if ($icon && !empty($icon['url'])): ?>
													<div class="insurance-info-item__icon">
														<img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($key); ?>">
													</div>
												<?php endif; ?>
												<p class="insurance-info-item__key"><?php echo esc_html($key); ?></p>
											</div>
										<?php endif; ?>

										<?php if ($value): ?>
											<p class="insurance-info-item__value">
												<?php echo wp_kses_post($value); ?>
											</p>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							<?php endwhile; ?>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		/**
		 * Контент из редактора
		 */
		if (get_the_content()): ?>
			<section class="insurance-content-section">
				<div class="container">
					<div class="editor-content read-content">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		/**
		 * Преимущества страхования (ACF поле insurance_benefits)
		 */
		if (function_exists('have_rows') && have_rows('insurance_benefits', $insurance_id)): ?>
			<section class="insurance-page-features__section">
				<div class="container">
					<h2 class="h2">Преимущества</h2>

					<div class="insurance-page-features__wrap">
						<?php while (have_rows('insurance_benefits', $insurance_id)):
							the_row(); ?>
							<?php
							$img = get_sub_field('image');
							$title = (string) get_sub_field('title');
							$desc = (string) get_sub_field('description');
							?>
							<div class="insurance-page-features__item">
								<div class="insurance-page-features__item__wrap">

									<?php if (!empty($img['url'])): ?>
										<div class="insurance-page-features__item-icon">
											<img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($title); ?>">
										</div>
									<?php endif; ?>

									<?php if (!empty($title)): ?>
										<div class="insurance-page-features__item-title">
											<?php echo esc_html($title); ?>
										</div>
									<?php endif; ?>

									<?php if (!empty($desc)): ?>
										<div class="insurance-page-features__item-description">
											<?php echo wp_kses_post(nl2br($desc)); ?>
										</div>
									<?php endif; ?>

								</div>
							</div>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		/**
		 * Условия страхования / Процедура (ACF поле insurance_conditions)
		 */
		if (function_exists('have_rows') && have_rows('insurance_conditions', $insurance_id)): ?>
			<section class="insurance-page-conditions__section">
				<div class="container">
					<h2 class="h2">Условия страхования</h2>

					<div class="insurance-page-conditions__wrap">
						<?php while (have_rows('insurance_conditions', $insurance_id)):
							the_row(); ?>
							<?php
							$img = get_sub_field('image');
							$num = get_sub_field('order');
							$title = (string) get_sub_field('title');
							$descr = (string) get_sub_field('description');
							?>
							<div class="insurance-page-conditions-item">
								<div class="insurance-page-conditions-item__top">
									<?php if (!empty($num) || $num === 0 || $num === '0'): ?>
										<div class="insurance-page-conditions-item__num numfont">
											<?php echo esc_html($num); ?>
										</div>
									<?php endif; ?>

									<?php if (!empty($title)): ?>
										<div class="insurance-page-conditions-item__title">
											<?php echo esc_html($title); ?>
										</div>
									<?php endif; ?>
								</div>

								<?php if (!empty($descr)): ?>
									<div class="insurance-page-conditions-item__description">
										<?php echo wp_kses_post($descr); ?>
									</div>
								<?php endif; ?>

								<?php if (!empty($img['url'])): ?>
									<div class="insurance-page-conditions-item__icon">
										<img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($title); ?>">
									</div>
								<?php endif; ?>
							</div>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		/**
		 * Контакты (ACF поле insurance_contacts)
		 */
		if (function_exists('have_rows') && have_rows('insurance_contacts', $insurance_id)): ?>
			<section class="insurance-page-contacts__section">
				<div class="container">
					<h2 class="h2">Контакты</h2>

					<div class="insurance-page-contacts__wrap">
						<?php while (have_rows('insurance_contacts', $insurance_id)):
							the_row(); ?>
							<?php
							$name = (string) get_sub_field('name');
							$direction = (string) get_sub_field('direction');
							$phone = (string) get_sub_field('phone');
							$phone_label = (string) get_sub_field('phone_label');
							$email = (string) get_sub_field('email');

							$tel = preg_replace('/[^0-9\+]/', '', $phone);
							?>

							<div class="insurance-contact-item">
								<div class="insurance-contact-item__inner">

									<?php if ($name): ?>
										<div class="insurance-contact-item__name"><?php echo esc_html($name); ?></div>
									<?php endif; ?>

									<?php if ($direction): ?>
										<div class="insurance-contact-item__direction"><?php echo esc_html($direction); ?></div>
									<?php endif; ?>

									<div class="insurance-contact-item__links">
										<?php if ($phone): ?>
											<a class="insurance-contact-item__phone insurance-contact-item__link numfont"
												href="<?php echo esc_url('tel:' . $tel); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
													fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
													stroke-linejoin="round" class="lucide lucide-phone-call-icon lucide-phone-call">
													<path d="M13 2a9 9 0 0 1 9 9" />
													<path d="M13 6a5 5 0 0 1 5 5" />
													<path
														d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />
												</svg>
												<span><?php echo esc_html($phone_label ?: $phone); ?></span>
											</a>
										<?php endif; ?>

										<?php if ($email): ?>
											<a class="insurance-contact-item__email insurance-contact-item__link numfont"
												href="<?php echo esc_url('mailto:' . $email); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
													fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
													stroke-linejoin="round" class="lucide lucide-mail-check-icon lucide-mail-check">
													<path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h8" />
													<path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
													<path d="m16 19 2 2 4-4" />
												</svg>
												<span><?php echo esc_html($email); ?></span>
											</a>
										<?php endif; ?>
									</div>

								</div>
							</div>

						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>


	<?php endwhile; ?>

</main>

<?php
get_footer();

