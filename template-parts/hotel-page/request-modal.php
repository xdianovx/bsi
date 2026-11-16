<?php

/**
 * Модалка заявки на конкретный номер. Открывается кнопкой `.js-hotel-room-request`
 * у номера без ссылки на бронирование; JS подставляет номер, питание, даты и цену.
 * Обработчик тот же, что у секции заявки — action `hotel_request`
 * (inc/requests/ajax-hotel-request.php), JS — js/modules/forms/hotel-request-form.js.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
if (!$view) {
  return;
}
?>

<div class="modal micromodal-slide" id="modal-hotel-request" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1">
    <div class="modal__container modal-program-booking" role="dialog" aria-modal="true">
      <button class="modal__close modal-program-booking__close" type="button" data-micromodal-close>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </button>

      <div class="modal__content modal-program-booking__content">
        <h2 class="modal-program-booking__title">Заявка на номер</h2>
        <p class="modal-program-booking__subtitle"><?= esc_html($view['name']); ?></p>
        <ul class="modal-program-booking__details js-hotel-request-details" hidden></ul>

        <form class="modal-program-booking__form js-hotel-request-form" data-modal="modal-hotel-request" novalidate>
          <input type="hidden" name="action" value="hotel_request">
          <input type="hidden" name="hotel_title" value="<?= esc_attr($view['name']); ?>">
          <input type="hidden" name="page_url" class="js-form-page-url">
          <input type="hidden" name="room_name" class="js-form-room-name">
          <input type="hidden" name="offer_meal" class="js-form-offer-meal">
          <input type="hidden" name="offer_dates" class="js-form-offer-dates">
          <input type="hidden" name="offer_price" class="js-form-offer-price">

          <div class="form-row form-row-2">
            <div class="input-item input-item--stacked">
              <label for="hotel-modal-name">Имя <span class="modal-program-booking__req">*</span></label>
              <input type="text" id="hotel-modal-name" name="name" required data-field="name" autocomplete="name" placeholder="Ваше имя">
              <span class="modal-program-booking__error js-field-error" data-error-for="name"></span>
            </div>
            <div class="input-item input-item--stacked">
              <label for="hotel-modal-phone">Телефон <span class="modal-program-booking__req">*</span></label>
              <input type="tel" id="hotel-modal-phone" name="phone" class="js-phone-mask" required data-field="phone" autocomplete="tel" placeholder="+7 (___) ___-__-__">
              <span class="modal-program-booking__error js-field-error" data-error-for="phone"></span>
            </div>
          </div>

          <div class="input-item input-item--stacked">
            <label for="hotel-modal-comment">Комментарий</label>
            <textarea id="hotel-modal-comment" name="comment" rows="3" data-field="comment" placeholder="Даты, состав гостей, пожелания"></textarea>
            <span class="modal-program-booking__error js-field-error" data-error-for="comment"></span>
          </div>

          <?php bsi_render_privacy_consent_checkbox([
            'variant' => 'input-item',
            'checkbox_id' => 'hotel-modal-privacy',
            'html_required' => true,
          ]); ?>

          <div class="modal-program-booking__form-footer">
            <button type="submit" class="modal-program-booking__submit btn btn-accent">Отправить заявку</button>
          </div>

          <p class="form-status js-hotel-request-status"></p>
        </form>
      </div>
    </div>
  </div>
</div>
