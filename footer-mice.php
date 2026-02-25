<?php
/**
 * The template for displaying the footer (MICE)
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
						'theme_location' => 'mice_footer_nav',
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
				<div class="footer-contact-col__inner">
					<div class="footer-contact footer-contact--phone">
						<div class=""> <span class="footer-contact__title">
								Телефон
							</span>
							<div class="footer-contact__links">

								<a href="tel:<?php the_field('telefon', 'option'); ?>" class="footer-contact__link">
									<?php the_field('telefon', 'option'); ?>
									<span>ПН-ПТ 10:00-19:00</span>
								</a>
								<a href="tel:<?php the_field('telefon_po_rf', 'option'); ?>" class="footer-contact__link">
									<?php the_field('telefon_po_rf', 'option'); ?>
									<span>Бесплатно из регионов</span>
								</a>
							</div>
						</div>


						<div class="footer__currencies">
							<div class="footer__currencies-date">
								Курс на <?= date_i18n('d.m.Y') ?>
							</div>
							<div class="footer__currencies-inner">
								<div class="currency-item">
									<div class="currency-item__title">USD</div>
									<div class="currency-item__value numfont"></div>
								</div>

								<div class="currency-item">
									<div class="currency-item__title">EUR</div>
									<div class="currency-item__value numfont"></div>
								</div>

								<div class="currency-select js-dropdown">
									<button class="js-dropdown-trigger currency-select-trigger__wrap">
										<span class="currency-current">RUB</span>
										<img src="<?= get_template_directory_uri() ?>/img/icons/chevron-d.svg" alt="">
									</button>

									<div class="js-dropdown-panel"></div>
								</div>
							</div>
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


		</div>

		<div class="footer__policy">
			<p>
				BSI GROUP © 1990—<?= date("Y"); ?> Все права защищены.
			</p>
		</div>
	</div>
</footer>

<?= get_template_part('template-parts/modals') ?>
<?= get_template_part('template-parts/maintenance-modal') ?>


<?php wp_footer(); ?>
<script src="https://api-maps.yandex.ru/v3/?apikey=e2acacad-47ea-4ef2-8273-61a3a5f50c5b&lang=ru_RU"></script>

</body>

</html>
