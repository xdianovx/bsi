<?php
/**
 * Форма консультации по страхованию + модалка успеха.
 *
 * get_template_part('template-parts/insurance/consultation-form', null, [
 *   'insurance_title' => 'Страхование багажа', // подставляется в поле «Тип страхования»
 * ]);
 *
 * На странице форма должна быть одна: JS ищет её по #insurance_phone.
 *
 * @package bsi
 */

declare(strict_types=1);

$insurance_title = isset($args['insurance_title']) ? (string) $args['insurance_title'] : '';
?>

<section class="insurance-consultation" id="insurance-consultation">
	<div class="container">
		<h2 class="h2">Бесплатная консультация</h2>
		<p class="insurance-consultation__descr">
			Оставьте заявку — проконсультируем по условиям полиса и поможем оформить
		</p>

		<form action="" class="visa-consultation-form insurance-consultation__form">

			<div class="form-row form-row-2">

				<div class="input-item">
					<label for="insurance_type">Тип страхования</label>
					<input type="text" name="insurance_type" id="insurance_type" placeholder="Тип страхования"
						value="<?php echo esc_attr($insurance_title); ?>">
				</div>

				<div class="input-item">
					<label for="insurance_name">Имя *</label>
					<input type="text" name="name" id="insurance_name" placeholder="Имя">
				</div>

				<div class="input-item">
					<label for="insurance_phone">Телефон *</label>
					<input type="tel" name="tel" id="insurance_phone" placeholder="+7 (___) ___-__-__">
				</div>

				<div class="input-item">
					<label for="insurance_date">Дата поездки</label>
					<input type="text" name="date" id="insurance_date" placeholder="Дата поездки">
				</div>
			</div>

			<?php
			if (function_exists('bsi_render_privacy_consent_checkbox')) {
				bsi_render_privacy_consent_checkbox([
					'variant' => 'input-item',
					'checkbox_id' => 'insurance-privacy',
					'wrapper_class' => '',
					'html_required' => true,
				]);
			}
			?>

			<div class="visa-consultation-form__bottom">
				<button type="submit" class="btn btn-accent fit-form__btn-submit">
					Отправить
				</button>
			</div>

			<div id="form-status"></div>

		</form>
	</div>
</section>

<!-- Модалка успеха -->
<div class="modal micromodal-slide" id="modal-insurance-success" aria-hidden="true">
	<div class="modal__overlay" tabindex="-1" data-micromodal-close>
		<div class="modal__container modal-program-booking-success" role="dialog" aria-modal="true">
			<div class="modal__content modal-program-booking-success__content">
				<div class="modal-program-booking-success__icon">
					<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="32" cy="32" r="32" fill="#4CAF50" />
						<path d="M20 32L28 40L44 24" stroke="white" stroke-width="4" stroke-linecap="round"
							stroke-linejoin="round" />
					</svg>
				</div>
				<h3 class="modal-program-booking-success__title">Заявка отправлена!</h3>
				<p class="modal-program-booking-success__text">Мы свяжемся с вами в ближайшее время</p>
			</div>
		</div>
	</div>
</div>
