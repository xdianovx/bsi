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
				<div class="footer-contact-col__inner">
					<div class="footer-contact footer-contact--phone">
						<div class=""> <span class="footer-contact__title">
								Телефон
							</span>
							<div class="footer-contact__links">

								<a href="tel:8 (495) 785-55-35" class="footer-contact__link">
									8 (495) 785-55-35
									<span>ПН-ПТ 10:00-19:00</span>
								</a>
								<a href="tel:8 (800) 200-55-35" class="footer-contact__link">
									8 (800) 200-55-35
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
			<!-- 
			<a href="<?= get_permalink(47) ?>" class="policy-link footer-policy-link">
				Политика в отношении обработки персональных данных
			</a> -->
		</div>
	</div>
</footer>

<?php get_template_part('template-parts/cookie-consent'); ?>

<!-- <div class="footer-logos">
	<div class="container">
		<div class="footer-logos__wrap">
			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/3.png" alt="">



			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/1.jpg" alt="">

			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/2.png" alt="">

			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/7.jpg" alt="">

			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/8.jpg" alt="">

			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/4.jpg" alt="">


			<img class="footer-partner" src="<?= get_template_directory_uri() ?>/img/footer/6.png" alt="">



		</div>
	</div>
</div> -->

<?= get_template_part('template-parts/modals') ?>
<?= get_template_part('template-parts/maintenance-modal') ?>


<!-- Yandex.Metrika counter — всегда (баннер cookie фиксирует выбор пользователя отдельно) -->
<script type="text/javascript">
  (function (m, e, t, r, i, k, a) {
    m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments) };
    m[i].l = 1 * new Date();
    for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
    k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
  })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=108341897', 'ym');

  ym(108341897, 'init', { ssr: true, webvisor: true, clickmap: true, ecommerce: "dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce: true, trackLinks: true });
</script>
<noscript>
  <div><img src="https://mc.yandex.ru/watch/108341897" style="position:absolute; left:-9999px;" alt="" /></div>
</noscript>
<!-- /Yandex.Metrika counter -->

<?php wp_footer(); ?>
<script src="https://api-maps.yandex.ru/v3/?apikey=e2acacad-47ea-4ef2-8273-61a3a5f50c5b&lang=ru_RU"></script>

</body>

</html>