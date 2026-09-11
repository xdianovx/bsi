<?php
/**
 * Блок «Другие полисы» — перелинковка между страховыми продуктами.
 *
 * Выводится на странице продукта: все остальные записи CPT insurance
 * в порядке меню. Текущая запись исключается.
 *
 * @package bsi
 */

declare(strict_types=1);

$current_id = (int) ($args['current_id'] ?? get_the_ID());

$related_posts = get_posts([
	'post_type' => 'insurance',
	'post_status' => 'publish',
	'posts_per_page' => -1,
	'orderby' => 'menu_order',
	'order' => 'ASC',
	'exclude' => [$current_id],
]);

if (empty($related_posts)) {
	return;
}
?>

<section class="insurance-related">
	<div class="container">
		<h2 class="h2">Другие полисы</h2>

		<div class="insurance-related__grid">
			<?php foreach ($related_posts as $related_post):
				$related_id = (int) $related_post->ID;
				$related_types = wp_get_object_terms($related_id, 'insurance_type', ['orderby' => 'name']);

				if (is_wp_error($related_types)) {
					$related_types = [];
				}
				?>
				<a class="insurance-related__item" href="<?php echo esc_url(get_permalink($related_id)); ?>">
					<?php if (has_post_thumbnail($related_id)): ?>
						<span class="insurance-related__media">
							<?php echo get_the_post_thumbnail($related_id, 'thumbnail', [
								'alt' => esc_attr(get_the_title($related_id)),
								'loading' => 'lazy',
							]); ?>
						</span>
					<?php endif; ?>

					<span class="insurance-related__body">
						<span class="insurance-related__title"><?php echo esc_html(get_the_title($related_id)); ?></span>

						<?php $related_excerpt = get_the_excerpt($related_id); ?>
						<?php if ($related_excerpt): ?>
							<span class="insurance-related__desc"><?php echo esc_html(wp_strip_all_tags($related_excerpt)); ?></span>
						<?php endif; ?>

						<?php if (!empty($related_types)): ?>
							<span class="insurance-related__badges">
								<?php foreach ($related_types as $related_type): ?>
									<span class="insurance-badge"><?php echo esc_html($related_type->name); ?></span>
								<?php endforeach; ?>
							</span>
						<?php endif; ?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
