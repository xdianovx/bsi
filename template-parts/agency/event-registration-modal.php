<?php
/**
 * Модальное окно регистрации на мероприятие агентства
 */
?>
<div class="modal micromodal-slide" id="modal-agency-event-reg" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container --xl" role="dialog" aria-modal="true"
      aria-labelledby="modal-agency-event-reg-title">
      <button class="modal__close modal-program-booking__close" aria-label="Закрыть" data-micromodal-close>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>

      <div class="modal__content">
        <h2 class="modal__title" id="modal-agency-event-reg-title">Регистрация на мероприятие</h2>
        <p class="modal__subtitle js-agency-reg-event-name"></p>

        <form class="agency-reg-form js-agency-reg-form" novalidate>
          <input type="hidden" name="event_id" class="js-agency-reg-event-id">
          <input type="hidden" name="event_title" class="js-agency-reg-event-title">
          <input type="hidden" name="event_kind" class="js-agency-reg-event-kind">
          <input type="hidden" name="page_url" value="<?php echo esc_url(home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? ''))); ?>">

          <div class="form-row form-row-2">
            <div class="input-item">
              <label for="agency-reg-name">ФИО *</label>
              <input type="text" name="name" id="agency-reg-name" placeholder="Фамилия Имя Отчество">
            </div>
            <div class="input-item">
              <label for="agency-reg-company">Компания *</label>
              <input type="text" name="company" id="agency-reg-company" placeholder="Название компании">
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="input-item">
              <label for="agency-reg-city">Город *</label>
              <input type="text" name="city" id="agency-reg-city" placeholder="Город">
            </div>
            <div class="input-item">
              <label for="agency-reg-inn">ИНН *</label>
              <input type="text" name="inn" id="agency-reg-inn" placeholder="ИНН компании">
            </div>
          </div>

          <div class="form-row form-row-2">
            <div class="input-item">
              <label for="agency-reg-email">Почта *</label>
              <input type="email" name="email" id="agency-reg-email" placeholder="email@example.com">
            </div>
            <div class="input-item">
              <label for="agency-reg-tel">Телефон *</label>
              <input type="tel" name="tel" id="agency-reg-tel" placeholder="+7 (___) ___-__-__">
            </div>
          </div>

          <div class="modal-program-booking__form-footer">
            <button type="submit" class="modal-program-booking__submit btn btn-accent">
              Отправить заявку
            </button>
            <p class="modal-program-booking__privacy">
              Нажимая кнопку "Отправить" вы соглашаетесь<br>
              с нашей <a href="<?php echo esc_url(home_url('/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/')); ?>" target="_blank">политикой конфиденциальности</a>
            </p>
          </div>

          <div id="agency-reg-form-status"></div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal micromodal-slide" id="modal-agency-reg-success" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container modal-program-booking-success" role="dialog" aria-modal="true">
      <div class="modal__content modal-program-booking-success__content">
        <div class="modal-program-booking-success__icon">
          <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="32" cy="32" r="32" fill="#4CAF50"/>
            <path d="M20 32L28 40L44 24" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3 class="modal-program-booking-success__title">Заявка отправлена!</h3>
        <p class="modal-program-booking-success__text">Мы свяжемся с вами в ближайшее время</p>
      </div>
    </div>
  </div>
</div>
