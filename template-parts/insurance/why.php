<?php
/**
 * Лента доверия «Почему через BSI Group» — общая для всего раздела.
 *
 * Стоит сразу под шапкой: отвечает на вопрос «кому я доверяю деньги»
 * до того, как пользователь начнёт разбираться в условиях полиса.
 * Оформлена одной белой панелью с колонками, а не отдельными карточками,
 * чтобы не спорить по весу с блоком «Что входит в полис» ниже.
 *
 * Данные лежат на странице с шаблоном page-insurance.php (ACF-поля insurance_why_*).
 *
 * @package bsi
 */

declare(strict_types=1);

$why_page_id = bsi_insurance_page_id();

if (!$why_page_id || !function_exists('have_rows') || !have_rows('insurance_why_items', $why_page_id)) {
	return;
}

$why_title = (string) get_field('insurance_why_title', $why_page_id);
$why_title = $why_title !== '' ? $why_title : 'Почему через BSI Group';
?>

<section class="insurance-why" aria-label="<?php echo esc_attr($why_title); ?>">
	<div class="container">
		<div class="insurance-why__panel">
			<?php while (have_rows('insurance_why_items', $why_page_id)):
				the_row();
				$icon = get_sub_field('icon');
				$value = (string) get_sub_field('value');
				$title = (string) get_sub_field('title');
				$descr = (string) get_sub_field('description');

				if (!$title && !$descr && $value === '') {
					continue;
				}
				?>
				<div class="insurance-why__item">
					<span class="insurance-why__icon">
						<?php get_template_part('template-parts/ui/icon', null, ['name' => $icon ?: 'shield-check', 'size' => 24]); ?>
					</span>

					<div class="insurance-why__body">
						<?php if ($value !== ''): ?>
							<span class="insurance-why__value numfont"><?php echo esc_html($value); ?></span>
						<?php endif; ?>

						<?php if ($title): ?>
							<h3 class="insurance-why__title"><?php echo esc_html($title); ?></h3>
						<?php endif; ?>

						<?php if ($descr): ?>
							<p class="insurance-why__desc"><?php echo wp_kses_post(nl2br($descr)); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
</section>
