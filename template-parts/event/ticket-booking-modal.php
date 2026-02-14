<?php
/**
 * Модальное окно бронирования билета на событийный тур
 * Контент заполняется через JavaScript
 */
?>
<div class="modal micromodal-slide" id="modal-event-ticket-booking" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1">
    <div class="modal__container --xl modal-program-booking" role="dialog" aria-modal="true"
      aria-labelledby="modal-event-ticket-booking-title">
      <button class="modal__close modal-program-booking__close" aria-label="Закрыть" data-micromodal-close>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>

      <div class="modal__content modal-program-booking__content">
        <!-- Заголовок события -->
        <h2 class="modal-program-booking__title" id="modal-event-ticket-booking-title"></h2>

        <!-- Мета информация: место, время -->
        <div class="modal-program-booking__meta">
          <span class="modal-program-booking__meta-item js-modal-venue"></span>
          <span class="modal-program-booking__meta-separator"></span>
          <span class="modal-program-booking__meta-item js-modal-time"></span>
        </div>

        <!-- Выбранный тип билета -->
        <div class="modal-program-booking__accommodation js-modal-ticket-type"></div>

        <!-- Итого -->
        <div class="modal-program-booking__total">
          <span class="modal-program-booking__total-label">Цена за билет:</span>
          <span class="modal-program-booking__total-value js-modal-ticket-price"></span>
        </div>

        <!-- Форма -->
        <form class="modal-program-booking__form js-event-ticket-booking-form" novalidate>
          <input type="hidden" name="action" value="event_ticket_booking">
          <input type="hidden" name="event_title" class="js-form-event-title">
          <input type="hidden" name="event_venue" class="js-form-event-venue">
          <input type="hidden" name="event_time" class="js-form-event-time">
          <input type="hidden" name="ticket_type" class="js-form-ticket-type">
          <input type="hidden" name="ticket_price" class="js-form-ticket-price">
          <input type="hidden" name="page_url" class="js-form-page-url">

          <!-- Первый ряд: Имя, Телефон -->
          <div class="modal-program-booking__form-row">
            <div class="modal-program-booking__form-group">
              <label for="event-booking-name" class="modal-program-booking__label">Имя</label>
              <input type="text" id="event-booking-name" name="name" class="modal-program-booking__input" required
                data-field="name">
              <span class="modal-program-booking__error js-field-error" data-error-for="name"></span>
            </div>

            <div class="modal-program-booking__form-group">
              <label for="event-booking-phone" class="modal-program-booking__label">Телефон</label>
              <input type="tel" id="event-booking-phone" name="phone" class="modal-program-booking__input js-phone-mask"
                placeholder="+7" required data-field="phone">
              <span class="modal-program-booking__error js-field-error" data-error-for="phone"></span>
            </div>
          </div>

          <!-- Второй ряд: Почта, Количество билетов -->
          <div class="modal-program-booking__form-row">
            <div class="modal-program-booking__form-group">
              <label for="event-booking-email" class="modal-program-booking__label">Почта</label>
              <input type="email" id="event-booking-email" name="email" class="modal-program-booking__input" required
                data-field="email">
              <span class="modal-program-booking__error js-field-error" data-error-for="email"></span>
            </div>

            <div class="modal-program-booking__form-group">
              <label for="event-booking-quantity" class="modal-program-booking__label">Количество билетов</label>
              <input type="number" id="event-booking-quantity" name="quantity" class="modal-program-booking__input"
                min="1" value="1" required data-field="quantity">
              <span class="modal-program-booking__error js-field-error" data-error-for="quantity"></span>
            </div>
          </div>

          <div class="modal-program-booking__form-group modal-program-booking__form-group--full">
            <label for="event-booking-comment" class="modal-program-booking__label">Пожелания и комментарии</label>
            <textarea id="event-booking-comment" name="comment" class="modal-program-booking__textarea" rows="4"
              data-field="comment"></textarea>
          </div>

          <div class="modal-program-booking__form-footer">
            <button type="submit" class="modal-program-booking__submit btn btn-accent">
              Отправить заявку
            </button>
            <p class="modal-program-booking__privacy">
              Нажимая кнопку "Отправить" вы соглашаетесь<br>
              с нашей <a href="/privacy-policy/" target="_blank">политикой конфиденциальности</a>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Модалка успеха (используем существующую) -->
<div class="modal micromodal-slide" id="modal-event-booking-success" aria-hidden="true">
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
