<?php
/**
 * Template Name: Страхование
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

	<section class="page-head archive-page-head">
		<div class="container">
			<div class="archive-page__top">
				<h1 class="h1 page-award__title archive-page__title">
					<?php the_title(); ?>
				</h1>

				<?php if (has_excerpt()): ?>
					<p class="page-award__excerpt archive-page__excerpt"><?= the_excerpt(); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	/**
	 * Получаем все страховки
	 */
	$insurance_posts = get_posts([
		'post_type' => 'insurance',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'menu_order',
		'order' => 'ASC',
	]);
	?>

	<?php if (!empty($insurance_posts)): ?>
		<section class="insurance-section">
			<div class="container">
				<div class="insurance-grid">
					<?php foreach ($insurance_posts as $insurance_post): ?>
						<?php
						$insurance_id = (int) $insurance_post->ID;
						$insurance_title = get_the_title($insurance_id);
						$insurance_excerpt = get_the_excerpt($insurance_id);
						$insurance_url = get_permalink($insurance_id);

						// Получаем изображение записи (Featured Image)
						$insurance_thumbnail = '';
						if (has_post_thumbnail($insurance_id)) {
							$insurance_thumbnail = get_the_post_thumbnail_url($insurance_id, 'medium_large');
						}

						// Получаем типы страхования
						$insurance_types = wp_get_object_terms($insurance_id, 'insurance_type', [
							'orderby' => 'name',
							'order' => 'ASC',
						]);

						if (is_wp_error($insurance_types) || empty($insurance_types)) {
							$insurance_types = [];
						}
						?>

						<div class="insurance_item">
							<?php if ($insurance_thumbnail): ?>
								<div class="insurance_item_img">
									<a href="<?php echo esc_url($insurance_url); ?>">
										<img src="<?php echo esc_url($insurance_thumbnail); ?>"
											alt="<?php echo esc_attr($insurance_title); ?>" loading="lazy">
									</a>
								</div>
							<?php endif; ?>

							<div class="insurance_item_content">
								<?php if (!empty($insurance_types) && is_array($insurance_types)): ?>
									<div class="insurance_item_types">
										<?php foreach ($insurance_types as $type): ?>
											<?php if (isset($type->name) && !empty($type->name)): ?>
												<span class="insurance_item_age"><?php echo esc_html($type->name); ?></span>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if ($insurance_title): ?>
									<h3 class="insurance_item_title">
										<a href="<?php echo esc_url($insurance_url); ?>">
											<?php echo esc_html($insurance_title); ?>
										</a>
									</h3>
								<?php endif; ?>

								<?php if ($insurance_excerpt): ?>
									<p class="insurance_item_desc"><?php echo esc_html(wp_strip_all_tags($insurance_excerpt)); ?>
									</p>
								<?php endif; ?>

								<div class="insurance_item_btn">
									<a href="<?php echo esc_url($insurance_url); ?>" class="btn btn-accent">
										Подробнее
									</a>
								</div>
							</div>
						</div>

					<?php endforeach; ?>
				</div>
			</div>
		</section>

	<?php endif; ?>

	<?php

	if (function_exists('have_rows') && have_rows('insurance_benefits')): ?>
		<section class="visa-page-features__section">
			<div class="container">
				<h2 class="h2">Наши преимущества</h2>

				<div class="visa-page-features__wrap">
					<?php while (have_rows('insurance_benefits')):
						the_row(); ?>
						<?php
						$img = get_sub_field('image');
						$title = (string) get_sub_field('title');
						$desc = (string) get_sub_field('description');
						?>
						<div class="visa-page-features__item">
							<div class="visa-page-features__item__wrap">

								<?php if (!empty($img['url'])): ?>
									<div class="visa-page-features__item-icon">
										<img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($title); ?>">
									</div>
								<?php endif; ?>

								<?php if (!empty($title)): ?>
									<div class="visa-page-features__item-title">
										<?php echo esc_html($title); ?>
									</div>
								<?php endif; ?>

								<?php if (!empty($desc)): ?>
									<div class="visa-page-features__item-description">
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
	 * Контент страницы
	 */
		if (have_posts()):
		while (have_posts()):
			the_post();
			if (get_the_content()): ?>
				<section class="insurance-content-section">
					<div class="container">
						<div class="editor-content read-content">
							<?php the_content(); ?>
						</div>
					</div>
				</section>
			<?php endif;
		endwhile;
	endif;
	?>

	<?php
	/**
	 * Форма консультации (ACF поле или стандартная форма)
	 */
	if (function_exists('have_rows') && have_rows('insurance_contacts')): ?>
		<section class="visa-page-contacts__section">
			<div class="container">
				<h2 class="h2">Контакты</h2>

				<div class="visa-page-contacts__wrap">
					<?php while (have_rows('insurance_contacts')):
						the_row(); ?>
						<?php
						$name = (string) get_sub_field('name');
						$direction = (string) get_sub_field('direction');
						$phone = (string) get_sub_field('phone');
						$phone_label = (string) get_sub_field('phone_label');
						$email = (string) get_sub_field('email');

						$tel = preg_replace('/[^0-9\+]/', '', $phone);
						?>

						<div class="visa-contact-item">
							<div class="visa-contact-item__inner">

								<?php if ($name): ?>
									<div class="visa-contact-item__name"><?php echo esc_html($name); ?></div>
								<?php endif; ?>

								<?php if ($direction): ?>
									<div class="visa-contact-item__direction"><?php echo esc_html($direction); ?></div>
								<?php endif; ?>

								<div class="visa-contact-item__links">
									<?php if ($phone): ?>
										<a class="visa-contact-item__phone visa-contact-item__link numfont"
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
										<a class="visa-contact-item__email visa-contact-item__link numfont"
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

	<section class="visa-page-consultation__section">
		<div class="container">
			<h2 class="h2">Бесплатная консультация</h2>
			<p class="visa-consultation-form__descr">Оставьте заявку и проконсультируем вас по вопросам страхования</p>
			<form action="" class="visa-consultation-form">

				<div class="form-row form-row-2">

					<div class="input-item white">
						<label for="insurance_type">Тип страхования</label>
						<input type="text" name="insurance_type" id="insurance_type" placeholder="Тип страхования">

						<div class="error-message" data-field="insurance_type">
						</div>
					</div>

					<div class="input-item white">
						<label for="insurance_name">Имя</label>
						<input type="text" name="name" id="insurance_name" placeholder="Имя">

						<div class="error-message" data-field="name">
						</div>
					</div>

					<div class="input-item white">
						<label for="insurance_phone">Телефон</label>
						<input type="tel" name="tel" id="insurance_phone" placeholder="+7 (___) ___-__-__">

						<div class="error-message" data-field="tel">
						</div>
					</div>

					<div class="input-item white">
						<label for="insurance_date">Дата поездки</label>
						<input type="text" name="date" id="insurance_date" placeholder="Дата поездки">

						<div class="error-message" data-field="date">
						</div>
					</div>
				</div>

				<div class="visa-consultation-form__bottom">
					<div id="form-status"></div>

					<button type="submit" class="btn btn-accent fit-form__btn-submit">
						Отправить
					</button>

					<p class="form-policy fit-form__policy">
						Нажимая на кнопку "Отправить", вы соглашаетесь с <a
							href="<?php echo esc_url(home_url('/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/')); ?>"
							class="policy-link">
							нашей политикой обработки персональных данных
						</a>
					</p>
				</div>
			</form>
		</div>
	</section>

</main>

<?php
get_footer();

