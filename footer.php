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




			<div class="footer__row">
				<div class=""> Написать текст про использование куки</div>
			</div>

			<div class="footer-contact-col">
				<div class="footer-contact">
					<span class="footer-contact__title">
						Круглосуточная поддержка
					</span>

					<a href="tel:<?php the_field('telefon_po_rf', 'option'); ?>"
						 class="footer-contact__link">
						<?php the_field('telefon_po_rf', 'option'); ?>
					</a>
				</div>

				<div class="footer-contact">
					<span class="footer-contact__title">
						Email
					</span>

					<a href="mailto:<?php the_field('email', 'option'); ?>"
						 class="footer-contact__link">
						<?php the_field('email', 'option'); ?>
					</a>
				</div>

				<div class="footer-contact">
					<span class="footer-contact__title">
						Офис в Москве
					</span>

					<p class="footer-contact__value">
						<?php the_field('adres_ofis', 'option'); ?>
					</p>
				</div>



			</div>

			<div class="footer__currencies">

				<div class="payments">
					<div class="payment-item">
						<img src="<?= get_template_directory_uri() ?>/img/icons/mir.png"
								 alt="mir pay">
					</div>
					<div class="payment-item">
						<img src="<?= get_template_directory_uri() ?>/img/icons/visa.png"
								 alt="visa pay">
					</div>

					<div class="payment-item">
						<img src="<?= get_template_directory_uri() ?>/img/icons/mc.png"
								 alt="mc pay">
					</div>
				</div>
			</div>
		</div>

		<div class="footer__policy">
			<p>
				BSI GROUP © 1990—<?= date("Y"); ?> Все права защищены.
			</p>

			<a href="<?= get_permalink(47) ?>"
				 class="policy-link footer-policy-link">
				Политика в отношении обработки персональных данны
			</a>
		</div>
	</div>
</footer>

<div class="footer-logos">
	<div class="container">
		<div class="footer-logos__wrap">

			<img class="footer-partner"
					 src="<?= get_template_directory_uri() ?>/img/footer/1.jpg"
					 alt="">
			<img class="footer-partner"
					 src="<?= get_template_directory_uri() ?>/img/footer/2.png"
					 alt="">
			<img class="footer-partner"
					 src="<?= get_template_directory_uri() ?>/img/footer/3.png"
					 alt="">
			<img class="footer-partner"
					 src="<?= get_template_directory_uri() ?>/img/footer/4.jpg"
					 alt="">


			<img class="footer-partner"
					 src="<?= get_template_directory_uri() ?>/img/footer/6.png"
					 alt="">
			<img class="footer-partner"
					 src="<?= get_template_directory_uri() ?>/img/footer/7.jpg"
					 alt="">

			<img class="footer-partner"
					 src="<?= get_template_directory_uri() ?>/img/footer/8.jpg"
					 alt="">
		</div>
	</div>
</div>

<?= get_template_part('template-parts/modals') ?>


<?php wp_footer(); ?>

</body>

</html>