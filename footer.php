<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package bsi
 */

?>

<footer class="footer">
	<div class="container">

		<div class="footer-grid">

			<div class="footer-top">
				<div class="footer-grid__links">
					<?php
					wp_nav_menu([
						'theme_location' => 'footer_nav',
						'container' => false,
						'fallback_cb' => false,
						'items_wrap' => '%3$s',
						'depth' => 1,
						'link_before' => '',
						'link_after' => '',
					]);
					?>

				</div>
				<?= get_template_part('template-parts/ui/socials') ?>
			</div>






			<div class="footer-contact-col">
				<div class="footer-contact">
					<span class="footer-contact__title">
						Телефон
					</span>
					<div class="footer-contact__links">

						<a href="tel:8 (495) 785-55-35" class="footer-contact__link">
							8 (495) 785-55-35
						</a>
						<a href="tel:8 (800) 200-55-35" class="footer-contact__link">
							8 (800) 200-55-35
						</a>
					</div>
				</div>

				<div class="footer-contact">
					<span class="footer-contact__title">
						Email
					</span>

					<a href="mailto:<?php the_field('email', 'option'); ?>" class="footer-contact__link">
						<?php the_field('email', 'option'); ?>
					</a>
				</div>

				<div class="footer-contact">
					<span class="footer-contact__title">
						Офисы в Москве
					</span>

					<p class="footer-contact__value">
						<?php the_field('adres_ofis', 'option'); ?>
					</p>

					<p class="footer-contact__value">
						<?php the_field('adres_ofis_2', 'option'); ?>
					</p>
				</div>



			</div>

			<div class="footer__row --no-border">
				<div class="footer-cookie-policy">
					Мы используем файлы cookie, чтобы запоминать ваши предпочтения, анализировать трафик и сделать наш
					сайт более удобным для вас. Вы можете управлять настройками куки в своем браузере или прочитать нашу
					<a href="<?= get_permalink(47) ?>" class="policy-link footer-policy-link">
						политику в отношении обработки персональных данных
					</a>
				</div>
			</div>

			<div class="footer__currencies">


			</div>
		</div>

		<div class="footer__policy">
			<p>
				BSI GROUP © 1990—<?= date("Y"); ?> Все права защищены.
			</p>

			<a href="<?= get_permalink(47) ?>" class="policy-link footer-policy-link">
				Политика в отношении обработки персональных данных
			</a>
		</div>
	</div>
</footer>

<div class="footer-logos">
	<div class="container">
		<div class="footer-logos__wrap">
			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/3.png" alt="">



			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/1.jpg" alt="">

			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/2.png" alt="">

			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/8.jpg" alt="">

			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/4.jpg" alt="">


			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/6.png" alt="">
			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/7.jpg" alt="">


		</div>
	</div>
</div>

<?= get_template_part('template-parts/modals') ?>


<?php wp_footer(); ?>

</body>

</html>