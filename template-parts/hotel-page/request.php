<?php

/**
 * Заявка на отель. Сюда ведут кнопки тарифов, у которых нет ссылки на бронирование.
 * Обработчик — inc/requests/ajax-hotel-request.php, JS — js/modules/forms/hotel-request-form.js.
 *
 * @var array $args ['view' => view-модель]
 */

$view = $args['view'] ?? [];
if (!$view) {
  return;
}
?>

<section class="hp-section hp-request" id="hotel-request">
  <div class="container">
    <div class="hp-request__box">
      <div class="hp-request__intro">
        <h2 class="h2 hp-section__title">Оставить заявку</h2>
        <p class="hp-request__text">
          Подберём номер в отеле «<?= esc_html($view['name']); ?>» на ваши даты и пришлём точную цену.
        </p>
        <p class="hp-request__selected js-hotel-request-selected" hidden></p>
      </div>

      <form class="hp-request__form js-hotel-request-form" novalidate>
        <input type="hidden" name="action" value="hotel_request">
        <input type="hidden" name="hotel_title" value="<?= esc_attr($view['name']); ?>">
        <input type="hidden" name="page_url" class="js-form-page-url">
        <input type="hidden" name="room_name" class="js-form-room-name">
        <input type="hidden" name="offer_meal" class="js-form-offer-meal">
        <input type="hidden" name="offer_dates" class="js-form-offer-dates">
        <input type="hidden" name="offer_price" class="js-form-offer-price">

        <div class="form-row form-row-2">
          <div class="input-item">
            <label for="hotel-request-name">Имя <span class="hp-request__req">*</span></label>
            <input type="text" id="hotel-request-name" name="name" required data-field="name" autocomplete="name" placeholder="Ваше имя">
            <span class="form-error js-field-error" data-error-for="name"></span>
          </div>

          <div class="input-item">
            <label for="hotel-request-phone">Телефон <span class="hp-request__req">*</span></label>
            <input type="tel" id="hotel-request-phone" name="phone" class="js-phone-mask" required data-field="phone" autocomplete="tel" placeholder="+7 (___) ___-__-__">
            <span class="form-error js-field-error" data-error-for="phone"></span>
          </div>
        </div>

        <div class="input-item">
          <label for="hotel-request-comment">Комментарий</label>
          <textarea id="hotel-request-comment" name="comment" rows="3" data-field="comment" placeholder="Даты, состав гостей, пожелания"></textarea>
          <span class="form-error js-field-error" data-error-for="comment"></span>
        </div>

        <?php bsi_render_privacy_consent_checkbox([
          'variant' => 'modal',
          'checkbox_id' => 'hotel-request-privacy',
          'html_required' => true,
        ]); ?>

        <button class="btn btn-accent hp-request__submit" type="submit">Отправить заявку</button>

        <p class="form-status js-hotel-request-status"></p>
      </form>
    </div>
  </div>
</section>
