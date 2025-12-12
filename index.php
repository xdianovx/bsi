<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package asd
 */

get_header();
?>

<main id="primary"
			class="site-main">

	<?php
	if (have_posts()):

		if (is_home() && !is_front_page()):
			?>
			<header>
				<h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
			</header>
			<?php
		endif;

		/* Start the Loop */
		while (have_posts()):
			the_post();

			/*
			 * Include the Post-Type-specific template for the content.
			 * If you want to override this in a child theme, then include a file
			 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
			 */
			get_template_part('template-parts/content', get_post_type());

		endwhile;

		the_posts_navigation();

	else:

		get_template_part('template-parts/content', 'none');

	endif;
	?>


	<li class="header-menu__item">
		<a href="#"
			 class="header-menu__link">Туристам</a>
		<div class="mega-menu">
			<div class="mega-menu__inner">
				<!-- Колонка "Информация" -->
				<div class="mega-menu__col">
					<div class="mega-menu__title">Информация</div>
					<ul class="mega-menu__list">
						<li class="mega-menu__item"><a href="/about"
								 class="mega-menu__link">О компании</a></li>
						<li class="mega-menu__item"><a href="/contacts"
								 class="mega-menu__link">Контакты</a></li>
						<li class="mega-menu__item"><a href="/reviews"
								 class="mega-menu__link">Отзывы</a></li>
						<li class="mega-menu__item"><a href="/hotline"
								 class="mega-menu__link">Горячая линия для туристов</a></li>
						<li class="mega-menu__item"><a href="/quality-service"
								 class="mega-menu__link">Служба контроля качества</a></li>
						<li class="mega-menu__item"><a href="/payment-methods"
								 class="mega-menu__link">Способы оплаты</a></li>
					</ul>
				</div>
				<!-- Колонка "Услуги" -->
				<div class="mega-menu__col">
					<div class="mega-menu__title">Услуги</div>
					<ul class="mega-menu__list">
						<li class="mega-menu__item"><a href="/gift-certificates"
								 class="mega-menu__link">Подарочные сертификаты</a></li>
						<li class="mega-menu__item"><a href="/how-to-book"
								 class="mega-menu__link">Как забронировать тур онлайн</a></li>
						<li class="mega-menu__item"><a href="/tour-booking"
								 class="mega-menu__link">Бронирование тура</a></li>
						<li class="mega-menu__item"><a href="/country-memos"
								 class="mega-menu__link">Памятки по странам</a></li>
					</ul>
				</div>
			</div>
		</div>
	</li>

	<?= get_template_part('template-parts/main-pages-grid') ?>
	<?= get_template_part('template-parts/sections/features') ?>
	<?= get_template_part('template-parts/news/news-slider') ?>
	<?= get_template_part('template-parts/partners/partners-slider') ?>
</main><!-- #main -->

<?php
get_sidebar();
get_footer();
