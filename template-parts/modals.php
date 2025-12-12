<div class="modal micromodal-slide"
     id="modal-hotel-pdf">
  <div class="modal__overlay"
       tabindex="-1"
       data-micromodal-close>


    <div class="modal__container">

      <div class="modal__close-btn"
           data-micromodal-close="">
        <svg xmlns="http://www.w3.org/2000/svg"
             width="24"
             height="24"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="2"
             stroke-linecap="round"
             stroke-linejoin="round"
             class="lucide lucide-x-icon lucide-x">
          <path d="M18 6 6 18" />
          <path d="m6 6 12 12" />
        </svg>
      </div>

      <div class="modal__title">Оформите бланк</div>
      <p class="modal__subtitle">Lorem ipsum dolor sit amet consectetur adipisicing elit. In eveniet sed debitis earum!
      </p>

      <form action=""
            class="modal__form">
        <div class="input-item">
          <label for="name">Агенство</label>
          <input type="text"
                 name="agency"
                 id="agency"
                 placeholder="Название агенства">

          <div class="error-message"
               data-field="agency">
          </div>
        </div>
        <div class="input-item">
          <label for="name">Имя</label>
          <input type="text"
                 name="name"
                 id="name"
                 placeholder="Почта">

          <div class="error-message"
               data-field="name">
          </div>
        </div>

        <div class="input-item">
          <label for="phone">Телефон *</label>
          <input type="tel"
                 name="phone"
                 id="phone"
                 placeholder="+7 (___) ___-__-__">

          <div class="error-message"
               data-field="phone">
          </div>
        </div>
        <div class="modal-form__botom">

          <button type="submit"
                  class="btn btn-accent">Отправть</button>

          <p class="form-policy modal__policy">
            Нажимая на кнопку "Отправить", вы соглашаетесь с <a
               href="http://localhost:8888/bsinew/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/"
               class="policy-link">
              нашей политикой обработки персональных данных
            </a>
          </p>
        </div>
      </form>
    </div>
  </div>
</div>