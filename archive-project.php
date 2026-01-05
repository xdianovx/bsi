<?php
get_header();

/**
 * Archive: Projects
 * Нужные зависимости:
 * - ACF поле project_country (post_object / id) -> CPT country
 * - template-parts/projects/card.php (карточка проекта)
 */

$projects = get_posts([
	'post_type' => 'project',
	'post_status' => 'publish',
	'posts_per_page' => -1,
	'orderby' => 'date',
	'order' => 'DESC',
]);

if (empty($projects)) {
	$projects = [];
}

// соберём страны, которые реально используются в проектах
$country_ids = [];

if (!empty($projects) && function_exists('get_field')) {
	foreach ($projects as $p) {
		$pid = (int) $p->ID;

		$c = get_field('project_country', $pid);
		if ($c instanceof WP_Post) {
			$c = (int) $c->ID;
		} elseif (is_array($c)) {
			$c = (int) reset($c);
		} else {
			$c = (int) $c;
		}

		if ($c > 0) {
			$country_ids[] = $c;
		}
	}
}

$country_ids = array_values(array_unique(array_filter($country_ids)));

$countries = [];
if (!empty($country_ids)) {
	$countries = get_posts([
		'post_type' => 'country',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
		'post__in' => $country_ids,
	]);

	if (class_exists('Collator')) {
		$collator = new Collator('ru_RU');
		usort($countries, function ($a, $b) use ($collator) {
			return $collator->compare($a->post_title, $b->post_title);
		});
	} else {
		usort($countries, function ($a, $b) {
			return mb_strcasecmp($a->post_title, $b->post_title);
		});
	}
}

// подготовим элементы проектов для вывода и для фильтра
$items = [];

foreach ($projects as $p) {
	$project_id = (int) $p->ID;

	$country_id = 0;
	if (function_exists('get_field')) {
		$c = get_field('project_country', $project_id);
		if ($c instanceof WP_Post) {
			$country_id = (int) $c->ID;
		} elseif (is_array($c)) {
			$country_id = (int) reset($c);
		} else {
			$country_id = (int) $c;
		}
	}

	$items[] = [
		'id' => $project_id,
		'country_id' => $country_id,
	];
}
?>

<?php if (function_exists('yoast_breadcrumb')): ?>
	<?php yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>'); ?>
<?php endif; ?>

<section class="projects-archive js-projects-archive">
	<div class="container">

		<div class="title-wrap news-slider__title-wrap">
			<div class="news-slider__title-wrap-left">
				<h1 class="h1 news-slider__title"><?php post_type_archive_title(); ?></h1>
			</div>
		</div>

		<?php if (!empty($countries)): ?>
			<div class="promo-filter projects-filter js-projects-filter">
				<button class="promo-filter__btn --all active js-projects-filter-btn"
								type="button"
								data-country="">
					Все
				</button>

				<?php foreach ($countries as $country): ?>
					<?php
					$country_id = (int) $country->ID;
					$country_title = (string) get_the_title($country_id);

					$flag_url = '';
					if (function_exists('get_field')) {
						$flag_field = get_field('flag', $country_id);
						if ($flag_field) {
							if (is_array($flag_field) && !empty($flag_field['url'])) {
								$flag_url = (string) $flag_field['url'];
							} elseif (is_string($flag_field)) {
								$flag_url = (string) $flag_field;
							}
						}
					}
					?>

					<button class="promo-filter__btn js-projects-filter-btn"
									type="button"
									data-country="<?php echo esc_attr($country_id); ?>">
						<?php if ($flag_url): ?>
							<span class="promo-filter__flag-wrap">
								<img src="<?php echo esc_url($flag_url); ?>"
										 alt="<?php echo esc_attr($country_title); ?>"
										 class="promo-filter__flag">
							</span>
						<?php endif; ?>

						<span class="promo-filter__title"><?php echo esc_html($country_title); ?></span>
					</button>
				<?php endforeach; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>

		<?php if (!empty($items)): ?>
			<div class="projects-archive__list js-projects-list">
				<?php foreach ($items as $it): ?>
					<?php
					// ВАЖНО: чтобы card.php корректно работал с the_title()/the_permalink()
					// нужно установить глобальный $post на текущий проект.
					$post = get_post((int) $it['id']);
					if (!$post) {
						continue;
					}
					setup_postdata($post);
					?>

					<div class="projects-archive__item js-projects-item"
							 data-country="<?php echo esc_attr((int) $it['country_id']); ?>">
						<?php
						set_query_var('project_id', (int) $it['id']);
						get_template_part('template-parts/projects/card');
						?>
					</div>

				<?php endforeach; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		<?php else: ?>
			<p>Пока нет проектов.</p>
		<?php endif; ?>

	</div>
</section>

<?php
get_footer();