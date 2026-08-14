<?php
/**
 * Блок «Как оформить» — общий для всего раздела «Страхование».
 *
 * Данные лежат на странице с шаблоном page-insurance.php (ACF-поля
 * insurance_steps / insurance_steps_title) и выводятся как в каталоге,
 * так и на страницах страховых продуктов.
 *
 * Функция bsi_insurance_page_id() — inc/helpers/insurance.php.
 *
 * @package bsi
 */

declare(strict_types=1);

$steps_page_id = bsi_insurance_page_id();

if (!$steps_page_id || !function_exists('have_rows') || !have_rows('insurance_steps', $steps_page_id)) {
	return;
}

$steps_title = (string) get_field('insurance_steps_title', $steps_page_id);
$steps_title = $steps_title !== '' ? $steps_title : 'Как оформить';
?>

<section class="insurance-steps">
	<div class="container">
		<h2 class="h2"><?php echo esc_html($steps_title); ?></h2>

		<div class="insurance-steps__grid">
			<?php
			$step_index = 0;
			while (have_rows('insurance_steps', $steps_page_id)):
				the_row();
				$step_index++;
				$num = get_sub_field('order');
				$title = (string) get_sub_field('title');
				$descr = (string) get_sub_field('description');
				$num = ($num === null || $num === '') ? $step_index : $num;
				?>
				<div class="insurance-step">
					<div class="insurance-step__top">
						<span class="insurance-step__num numfont"><?php echo esc_html((string) $num); ?></span>

						<?php if ($title): ?>
							<h3 class="insurance-step__title"><?php echo esc_html($title); ?></h3>
						<?php endif; ?>
					</div>

					<?php if ($descr): ?>
						<p class="insurance-step__desc"><?php echo wp_kses_post(nl2br($descr)); ?></p>
					<?php endif; ?>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
</section>
