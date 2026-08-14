<?php
/**
 * Страница страхового продукта — мини-лендинг.
 *
 * Порядок секций: шапка с ключевыми параметрами → преимущества →
 * что покрывает / не покрывает → как оформить → условия и правила (аккордеон) →
 * документы → вопросы → форма консультации.
 *
 * @package bsi
 */

declare(strict_types=1);

get_header();
?>

<main class="site-main insurance-single">

	<?php if (function_exists('yoast_breadcrumb')) {
		yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
	} ?>

	<?php
	while (have_posts()):
		the_post();
		$insurance_id = get_the_ID();

		$insurance_types = wp_get_object_terms($insurance_id, 'insurance_type', ['orderby' => 'name']);
		if (is_wp_error($insurance_types)) {
			$insurance_types = [];
		}

		$hero_note = (string) get_field('insurance_hero_note', $insurance_id);
		$has_info = function_exists('have_rows') && have_rows('insurance_info', $insurance_id);
		?>

		<section class="insurance-hero">
			<div class="container">
				<div class="insurance-hero__inner">

					<div class="insurance-hero__main">
						<div class="insurance-hero__head">
						<?php if (!empty($insurance_types)): ?>
							<div class="insurance-hero__badges">
								<?php foreach ($insurance_types as $type): ?>
									<span class="insurance-badge"><?php echo esc_html($type->name); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h1 class="h1 insurance-hero__title"><?php the_title(); ?></h1>

						<?php if (has_excerpt()): ?>
							<p class="insurance-hero__excerpt"><?php echo esc_html(wp_strip_all_tags(get_the_excerpt())); ?></p>
						<?php endif; ?>

						<div class="insurance-hero__actions">
								<a href="#insurance-consultation" class="btn btn-accent">Получить консультацию</a>
							</div>
						</div>

						<?php if (has_post_thumbnail($insurance_id)): ?>
							<div class="insurance-hero__media">
								<?php echo get_the_post_thumbnail($insurance_id, 'medium_large', [
									'alt' => esc_attr(get_the_title($insurance_id)),
									'loading' => 'eager',
								]); ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ($has_info): ?>
						<div class="insurance-facts">
							<?php while (have_rows('insurance_info', $insurance_id)):
								the_row();
								$icon = get_sub_field('icon');
								$key = (string) get_sub_field('key');
								$value = (string) get_sub_field('value');

								if ($key === '' && $value === '') {
									continue;
								}
								?>
								<div class="insurance-fact">
									<div class="insurance-fact__head">
										<?php if ($icon): ?>
											<span class="insurance-fact__icon">
												<?php get_template_part('template-parts/ui/icon', null, ['name' => $icon, 'size' => 20]); ?>
											</span>
										<?php endif; ?>

										<?php if ($key !== ''): ?>
											<span class="insurance-fact__key"><?php echo esc_html($key); ?></span>
										<?php endif; ?>
									</div>

									<?php if ($value !== ''): ?>
										<span class="insurance-fact__value numfont"><?php echo esc_html($value); ?></span>
									<?php endif; ?>
								</div>
							<?php endwhile; ?>
						</div>

						<?php if ($hero_note): ?>
							<p class="insurance-hero__note"><?php echo esc_html($hero_note); ?></p>
						<?php endif; ?>
					<?php endif; ?>

				</div>
			</div>
		</section>

		<?php if (function_exists('have_rows') && have_rows('insurance_benefits', $insurance_id)): ?>
			<section class="insurance-benefits">
				<div class="container">
					<h2 class="h2">Преимущества</h2>

					<div class="insurance-benefits__grid">
						<?php while (have_rows('insurance_benefits', $insurance_id)):
							the_row();
							$icon = get_sub_field('icon');
							$title = (string) get_sub_field('title');
							$desc = (string) get_sub_field('description');

							if (!$title && !$desc) {
								continue;
							}
							?>
							<div class="insurance-benefit">
								<div class="insurance-benefit__head">
									<div class="insurance-benefit__icon">
										<?php get_template_part('template-parts/ui/icon', null, ['name' => $icon ?: 'shield-check', 'size' => 24]); ?>
									</div>

									<?php if ($title): ?>
										<h3 class="insurance-benefit__title"><?php echo esc_html($title); ?></h3>
									<?php endif; ?>
								</div>

								<?php if ($desc): ?>
									<p class="insurance-benefit__desc"><?php echo esc_html($desc); ?></p>
								<?php endif; ?>
							</div>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$has_coverage = function_exists('have_rows') && have_rows('insurance_coverage', $insurance_id);
		$has_exclusions = function_exists('have_rows') && have_rows('insurance_exclusions', $insurance_id);

		if ($has_coverage || $has_exclusions): ?>
			<section class="insurance-coverage">
				<div class="container">
					<div class="insurance-coverage__grid">

						<?php if ($has_coverage): ?>
							<div class="insurance-coverage__col">
								<h2 class="h2">Что покрывает полис</h2>
								<ul class="insurance-list insurance-list--yes">
									<?php while (have_rows('insurance_coverage', $insurance_id)):
										the_row();
										$title = (string) get_sub_field('title');

										if (!$title) {
											continue;
										}
										?>
										<li class="insurance-list__item">
											<span class="insurance-list__marker">
												<?php get_template_part('template-parts/ui/icon', null, ['name' => 'circle-check', 'size' => 20]); ?>
											</span>
											<span class="insurance-list__text"><?php echo esc_html($title); ?></span>
										</li>
									<?php endwhile; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php if ($has_exclusions): ?>
							<div class="insurance-coverage__col">
								<h2 class="h2">Что не покрывает</h2>
								<ul class="insurance-list insurance-list--no">
									<?php while (have_rows('insurance_exclusions', $insurance_id)):
										the_row();
										$title = (string) get_sub_field('title');

										if (!$title) {
											continue;
										}
										?>
										<li class="insurance-list__item">
											<span class="insurance-list__marker">
												<?php get_template_part('template-parts/ui/icon', null, ['name' => 'ban', 'size' => 20]); ?>
											</span>
											<span class="insurance-list__text"><?php echo esc_html($title); ?></span>
										</li>
									<?php endwhile; ?>
								</ul>
							</div>
						<?php endif; ?>

					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php get_template_part('template-parts/insurance/steps'); ?>

		<?php
		/**
		 * Условия и правила — аккордеон.
		 * Последним блоком добавляется содержимое редактора (полный текст правил),
		 * чтобы длинные юридические тексты не разрывали страницу.
		 */
		$rules_rows = function_exists('have_rows') && have_rows('insurance_rules', $insurance_id);
		$editor_content = trim((string) get_the_content());
		$rules_url = (string) get_field('insurance_rules_url', $insurance_id);
		$content_title = (string) get_field('insurance_content_title', $insurance_id);
		$content_title = $content_title ?: 'Полный текст правил страхования';

		if ($rules_rows || $editor_content): ?>
			<section class="insurance-rules">
				<div class="container">
					<h2 class="h2">Условия и правила</h2>

					<div class="accordion insurance-rules__list">
						<?php
						$rule_index = 0;

						if ($rules_rows):
							while (have_rows('insurance_rules', $insurance_id)):
								the_row();
								$title = (string) get_sub_field('title');
								$content = (string) get_sub_field('content');

								if (!$title && !$content) {
									continue;
								}

								$rule_index++;
								$panel_id = 'insurance-rule-' . $rule_index;
								?>
								<div class="accordion__item insurance-rules__item">
									<button class="accordion__btn insurance-rules__btn" type="button" aria-expanded="false"
										aria-controls="<?php echo esc_attr($panel_id); ?>">
										<span class="insurance-rules__question"><?php echo esc_html($title); ?></span>
										<span class="accordion__icon insurance-rules__icon" aria-hidden="true">
											<img src="<?php echo esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
										</span>
									</button>

									<div class="accordion__panel insurance-rules__panel" id="<?php echo esc_attr($panel_id); ?>" hidden
										aria-hidden="true">
										<div class="editor-content read-content editor-content--numbered">
											<?php echo wp_kses_post($content); ?>
										</div>
									</div>
								</div>
							<?php endwhile;
						endif; ?>

						<?php if ($editor_content):
							$rule_index++;
							$panel_id = 'insurance-rule-' . $rule_index;
							$content_classes = 'editor-content read-content editor-content--numbered';
							if (stripos($editor_content, '<h2') === false) {
								$content_classes .= ' editor-content--numbered-flat';
							}
							?>
							<div class="accordion__item insurance-rules__item">
								<button class="accordion__btn insurance-rules__btn" type="button" aria-expanded="false"
									aria-controls="<?php echo esc_attr($panel_id); ?>">
									<span class="insurance-rules__question"><?php echo esc_html($content_title); ?></span>
									<span class="accordion__icon insurance-rules__icon" aria-hidden="true">
										<img src="<?php echo esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
									</span>
								</button>

								<div class="accordion__panel insurance-rules__panel" id="<?php echo esc_attr($panel_id); ?>" hidden
									aria-hidden="true">
									<div class="<?php echo esc_attr($content_classes); ?>">
										<?php the_content(); ?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<?php if ($rules_url): ?>
						<p class="insurance-rules__link">
							<a href="<?php echo esc_url($rules_url); ?>">Полные правила страхования</a>
						</p>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if (function_exists('have_rows') && have_rows('insurance_docs', $insurance_id)): ?>
			<section class="insurance-docs">
				<div class="container">
					<h2 class="h2">Документы</h2>

					<ul class="insurance-docs__list">
						<?php while (have_rows('insurance_docs', $insurance_id)):
							the_row();
							$title = (string) get_sub_field('title');
							$file = get_sub_field('file');

							if (empty($file['url'])) {
								continue;
							}

							// Формат берём из расширения файла: subtype у ACF бывает вида «vnd.openxml…».
							$extension = strtoupper((string) pathinfo((string) $file['url'], PATHINFO_EXTENSION));
							$filesize = isset($file['filesize']) ? size_format((int) $file['filesize']) : '';
							?>
							<li class="insurance-doc">
								<span class="insurance-doc__title">
									<?php echo esc_html($title ?: ($file['title'] ?? 'Документ')); ?>
								</span>

								<div class="insurance-doc__actions">
									<a class="insurance-doc__link" href="<?php echo esc_url($file['url']); ?>" target="_blank"
										rel="noopener" download>Скачать</a>

									<?php if ($extension): ?>
										<span class="insurance-doc__meta"><?php echo esc_html($extension); ?></span>
									<?php endif; ?>

									<?php if ($filesize): ?>
										<span class="insurance-doc__meta numfont"><?php echo esc_html($filesize); ?></span>
									<?php endif; ?>
								</div>
							</li>
						<?php endwhile; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<?php get_template_part('template-parts/insurance/faq'); ?>

		<?php get_template_part('template-parts/insurance/consultation-form', null, [
			'insurance_title' => get_the_title($insurance_id),
		]); ?>

	<?php endwhile; ?>

</main>

<?php
get_footer();
