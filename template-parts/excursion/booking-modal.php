<?php
/**
 * Модальное окно заявки на экскурсию.
 *
 * Открывается кнопкой `.js-excursion-booking-btn` (data-excursion-id, data-excursion-title, data-excursion-date).
 * Обработчик AJAX action `excursion_booking` — inc/requests/ajax-excursion-booking.php.
 * JS-биндинг — js/modules/forms/excursion-booking-form.js.
 */
?>
<div class="modal micromodal-slide" id="modal-excursion-booking" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1">
    <div class="modal__container --xl modal-program-booking" role="dialog" aria-modal="true"
      aria-labelledby="modal-excursion-booking-title">
      <button class="modal__close modal-program-booking__close" aria-label="Закрыть" data-micromodal-close>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>

      <div class="modal__content modal-program-booking__content">
        <h2 class="modal-program-booking__title" id="modal-excursion-booking-title">Бронирование экскурсии</h2>
        <p class="modal-program-booking__lead js-excursion-booking-lead"></p>

        <form class="modal-program-booking__form js-excursion-booking-form" novalidate>
          <input type="hidden" name="action" value="excursion_booking">
          <input type="hidden" name="excursion_id" class="js-form-excursion-id">
          <input type="hidden" name="excursion_title" class="js-form-excursion-title">
          <input type="hidden" name="excursion_date" class="js-form-excursion-date">
          <input type="hidden" name="page_url" class="js-form-page-url">

          <div class="form-row form-row-2">
            <div class="input-item">
              <label for="excursion-booking-name">Имя <span class="modal-program-booking__req">*</span></label>
              <input type="text" id="excursion-booking-name" name="name" required data-field="name" autocomplete="name"
                placeholder="Ваше имя">
              <span class="modal-program-booking__error js-field-error" data-error-for="name"></span>
            </div>
            <div class="input-item">
              <label for="excursion-booking-phone">Телефон <span class="modal-program-booking__req">*</span></label>
              <input type="tel" id="excursion-booking-phone" name="phone" class="js-phone-mask" required
                data-field="phone" autocomplete="tel" placeholder="+7 (___) ___-__-__">
              <span class="modal-program-booking__error js-field-error" data-error-for="phone"></span>
            </div>
          </div>

          <div class="input-item">
            <label for="excursion-booking-email">Почта</label>
            <input type="email" id="excursion-booking-email" name="email" data-field="email" autocomplete="email"
              placeholder="Необязательно">
            <span class="modal-program-booking__error js-field-error" data-error-for="email"></span>
          </div>

          <div class="input-item">
            <label for="excursion-booking-comment">Комментарий</label>
            <textarea id="excursion-booking-comment" name="comment" rows="3" data-field="comment"
              placeholder="Необязательно"></textarea>
            <span class="modal-program-booking__error js-field-error" data-error-for="comment"></span>
          </div>

          <?php
          if (function_exists('bsi_render_privacy_consent_checkbox')) {
            bsi_render_privacy_consent_checkbox([
              'variant' => 'input-item',
              'checkbox_id' => 'excursion-booking-privacy',
            ]);
          }
          ?>

          <div class="modal-program-booking__form-footer">
            <button type="submit" class="modal-program-booking__submit btn btn-accent" data-default-label="Отправить заявку">
              Отправить заявку
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal micromodal-slide" id="modal-excursion-booking-success" aria-hidden="true">
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
