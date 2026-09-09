<?php
/**
 * Блок «Что делать при страховом случае» — общий для всего раздела.
 *
 * Данные лежат на странице с шаблоном page-insurance.php
 * (ACF-поля insurance_claim_*) и выводятся как в каталоге,
 * так и на страницах страховых продуктов.
 *
 * Функция bsi_insurance_page_id() — inc/helpers/insurance.php.
 *
 * @package bsi
 */

declare(strict_types=1);

$claim_page_id = bsi_insurance_page_id();

if (!$claim_page_id || !function_exists('have_rows')) {
	return;
}

$claim_intro = (string) get_field('insurance_claim_intro', $claim_page_id);
$claim_phone = (string) get_field('insurance_claim_phone', $claim_page_id);
$claim_phone_note = (string) get_field('insurance_claim_phone_note', $claim_page_id);
$has_claim_steps = have_rows('insurance_claim_steps', $claim_page_id);

if (!$has_claim_steps && $claim_intro === '' && $claim_phone === '') {
	return;
}

$claim_title = (string) get_field('insurance_claim_title', $claim_page_id);
$claim_title = $claim_title !== '' ? $claim_title : 'Что делать при страховом случае';
?>

<section class="insurance-claim" id="insurance-claim">
	<div class="container">
		<h2 class="h2"><?php echo esc_html($claim_title); ?></h2>

		<?php if ($claim_intro !== ''): ?>
			<p class="insurance-claim__intro"><?php echo wp_kses_post(nl2br($claim_intro)); ?></p>
		<?php endif; ?>

		<div class="insurance-claim__inner">
			<?php if ($has_claim_steps): ?>
				<ol class="insurance-claim__list">
					<?php while (have_rows('insurance_claim_steps', $claim_page_id)):
						the_row();
						$icon = get_sub_field('icon');
						$title = (string) get_sub_field('title');
						$descr = (string) get_sub_field('description');

						if (!$title && !$descr) {
							continue;
						}
						?>
						<li class="insurance-claim__step">
							<div class="insurance-claim__step-head">
								<span class="insurance-claim__step-icon">
									<?php get_template_part('template-parts/ui/icon', null, ['name' => $icon ?: 'phone-call', 'size' => 24]); ?>
								</span>

								<?php if ($title): ?>
									<h3 class="insurance-claim__step-title"><?php echo esc_html($title); ?></h3>
								<?php endif; ?>
							</div>

							<?php if ($descr): ?>
								<p class="insurance-claim__step-desc"><?php echo wp_kses_post(nl2br($descr)); ?></p>
							<?php endif; ?>
						</li>
					<?php endwhile; ?>
				</ol>
			<?php endif; ?>

			<?php if ($claim_phone !== ''): ?>
				<aside class="insurance-claim__contact">
					<span class="insurance-claim__contact-label">Сервисный центр</span>

					<a class="insurance-claim__contact-phone numfont"
						href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $claim_phone)); ?>">
						<?php echo esc_html($claim_phone); ?>
					</a>

					<?php if ($claim_phone_note !== ''): ?>
						<span class="insurance-claim__contact-note"><?php echo esc_html($claim_phone_note); ?></span>
					<?php endif; ?>
				</aside>
			<?php endif; ?>
		</div>
	</div>
</section>
