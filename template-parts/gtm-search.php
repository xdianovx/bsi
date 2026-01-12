<section class="gtm-search__section is-loading">
  <div class="container">

    <div class="gtm-search__tab-btns">
      <div class="gtm-search__tab-btn active" data-tab="tours">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
          class="lucide lucide-tree-palm-icon lucide-tree-palm">
          <path d="M13 8c0-2.76-2.46-5-5.5-5S2 5.24 2 8h2l1-1 1 1h4" />
          <path d="M13 7.14A5.82 5.82 0 0 1 16.5 6c3.04 0 5.5 2.24 5.5 5h-3l-1-1-1 1h-3" />
          <path
            d="M5.89 9.71c-2.15 2.15-2.3 5.47-.35 7.43l4.24-4.25.7-.7.71-.71 2.12-2.12c-1.95-1.96-5.27-1.8-7.42.35" />
          <path d="M11 15.5c.5 2.5-.17 4.5-1 6.5h4c2-5.5-.5-12-1-14" />
        </svg>
        <span>
          Туры

        </span>
      </div>




      <div class="gtm-search__tab-btn" data-tab="hotels">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
          class="lucide lucide-bed-double-icon lucide-bed-double">
          <path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8" />
          <path d="M4 10V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4" />
          <path d="M12 4v6" />
          <path d="M2 18h20" />
        </svg>
        <span>Отели</span>
      </div>

      <!-- <div class="gtm-search__tab-btn" data-tab="excursions"> -->
      <a href="https://past.bsigroup.ru/search_tour/agency" target="_blank" rel="noopener noreferrer"
        class="gtm-search__tab-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" color="currentColor"
          fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path
            d="M3 22C7.67798 16.3864 9.95978 9.8784 10.8382 4.98644C10.8643 4.84129 10.8773 4.76868 10.8931 4.71553C11.0101 4.32106 11.3323 4.05162 11.7412 4.00613C11.7963 4 11.8642 4 12 4C12.1358 4 12.2037 4 12.2588 4.00613C12.6677 4.05162 12.9899 4.32106 13.1069 4.71553C13.1227 4.76868 13.1357 4.84129 13.1618 4.98644C14.0402 9.8784 16.322 16.3864 21 22" />
          <path d="M12 4V2" />
          <path d="M8 11H16" />
          <path d="M6 15H18" />
          <path
            d="M15.5 22C15.2904 20.959 15.1855 20.4386 14.9348 19.9979C14.822 19.7995 14.6881 19.6148 14.5358 19.447C14.1973 19.0744 13.7412 18.8227 12.8289 18.3194C12.48 18.1269 12.3055 18.0306 12.1198 18.0074C12.0402 17.9975 11.9598 17.9975 11.8802 18.0074C11.6945 18.0306 11.52 18.1269 11.1711 18.3194C10.2588 18.8227 9.8027 19.0744 9.46424 19.447C9.31188 19.6148 9.17804 19.7995 9.06518 19.9979C8.81446 20.4386 8.70964 20.959 8.5 22" />
          <path d="M15 22L22 22" />
          <path d="M2 22H9" />
        </svg>
        <span>Экскурсионные туры</span>
      </a>

      <?php
      // bsistudy.ru требует SSO авторизацию, поэтому используем tokens_exchange.php flow
      // Это нормальное поведение SSO системы - она редиректит на авторизацию если нет куков
      $bsistudy_url = 'https://bsigroup.ru/auth/tokens_exchange.php?ret_path=' . urlencode('https://www.bsistudy.ru/');
      ?>
      <a href="<?php echo esc_url($bsistudy_url); ?>" target="_blank" rel="noopener noreferrer" class="gtm-search__tab-btn">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor"
          xmlns="http://www.w3.org/2000/svg">
          <path
            d="M1.69043 6.66667C1.69043 7.78482 8.4362 10.8333 10.0122 10.8333C11.5881 10.8333 18.3339 7.78482 18.3339 6.66667C18.3339 5.54852 11.5881 2.5 10.0122 2.5C8.4362 2.5 1.69043 5.54852 1.69043 6.66667Z"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path
            d="M5.01855 9.16669L5.22281 13.8583C5.2269 13.9522 5.23705 14.0463 5.26428 14.1363C5.34842 14.4144 5.5035 14.6672 5.74011 14.837C7.59132 16.1654 12.4319 16.1654 14.2831 14.837C14.5197 14.6672 14.6748 14.4144 14.7589 14.1363C14.7861 14.0463 14.7963 13.9522 14.8004 13.8583L15.0046 9.16669"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path
            d="M17.0849 7.91669V13.75M17.0849 13.75C16.4249 14.9553 16.1331 15.601 15.8367 16.6667C15.7723 17.0459 15.8234 17.2369 16.0847 17.4066C16.1909 17.4755 16.3185 17.5 16.445 17.5H17.712C17.8467 17.5 17.9827 17.472 18.0936 17.3955C18.3365 17.228 18.399 17.0441 18.3332 16.6667C18.0733 15.6772 17.7424 15.0007 17.0849 13.75Z"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <span>Образование за рубежом</span>
      </a>



      <?php
      // Get page by slug 'vizy'
      $visa_page = get_page_by_path('vizy');
      $visa_url = $visa_page ? get_permalink($visa_page->ID) : '#';
      ?>
      <div class="gtm-search__tab-btn" data-href="<?= esc_url($visa_url); ?>">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"
          stroke="currentColor">
          <g clip-path="url(#clip0_516_78)">
            <path
              d="M10.792 18.3333C12.9959 18.3333 14.0978 18.3333 14.889 17.7031C15.6801 17.0728 15.9325 15.9939 16.4373 13.836L18.035 7.00578C18.3145 5.81133 18.4542 5.2141 18.2005 4.78156C17.7398 3.99605 16.5658 4.16665 15.7995 4.16665"
              stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <path
              d="M1.6665 7.50002C1.6665 4.75016 1.6665 3.37523 2.52078 2.52096C3.37505 1.66669 4.74998 1.66669 7.49984 1.66669H9.99984C12.7497 1.66669 14.1246 1.66669 14.9789 2.52096C15.8332 3.37523 15.8332 4.75016 15.8332 7.50002V12.5C15.8332 15.2499 15.8332 16.6248 14.9789 17.4791C14.1246 18.3334 12.7497 18.3334 9.99984 18.3334H7.49984C4.74998 18.3334 3.37505 18.3334 2.52078 17.4791C1.6665 16.6248 1.6665 15.2499 1.6665 12.5V7.50002Z"
              stroke-width="1.5" />
            <path
              d="M8.74984 5C10.5908 5 12.0832 6.49238 12.0832 8.33333C12.0832 10.1743 10.5908 11.6667 8.74984 11.6667M8.74984 5C6.90889 5 5.4165 6.49238 5.4165 8.33333C5.4165 10.1743 6.90889 11.6667 8.74984 11.6667M8.74984 5C8.05948 5 7.49984 6.49238 7.49984 8.33333C7.49984 10.1743 8.05948 11.6667 8.74984 11.6667"
              stroke-width="1.5" />
            <path d="M5.8335 14.1667L11.6668 14.1667" stroke-width="1.5" stroke-linecap="round" />
          </g>
          <defs>
            <clipPath id="clip0_516_78">
              <rect width="20" height="20" fill="white" />
            </clipPath>
          </defs>
        </svg>

        <span>Визы</span>
      </div>

      <a href="https://online.bsigroup.ru/tickets" target="_blank" rel="noopener noreferrer"
        class="gtm-search__tab-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
          class="lucide lucide-plane-icon lucide-plane">
          <path
            d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" />
        </svg>
        <span>Авиабилеты</span>
      </a>

      <?php
      // Get page by slug 'mice'
      $mice_page = get_page_by_path('mice');
      $mice_url = $mice_page ? get_permalink($mice_page->ID) : '#';
      ?>
      <a href="https://past.bsigroup.ru/business/" target="_blank" rel="noopener noreferrer"
        class="gtm-search__tab-btn">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
          stroke="currentColor">
          <path
            d="M8.5 6.5C8.5 5.09554 8.5 4.39331 8.83706 3.88886C8.98298 3.67048 9.17048 3.48298 9.38886 3.33706C9.89331 3 10.5955 3 12 3C13.4045 3 14.1067 3 14.6111 3.33706C14.8295 3.48298 15.017 3.67048 15.1629 3.88886C15.5 4.39331 15.5 5.09554 15.5 6.5"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path
            d="M19.9993 6.50016L4.0002 6.5C2.89574 6.50007 2.0001 7.39568 2 8.50013C2.0001 10.7091 3.79139 12.5004 6.00035 12.5006H17.9992C20.2081 12.5005 21.9993 10.7093 21.9994 8.5003C21.9994 7.39582 21.1038 6.50023 19.9993 6.50016Z"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M7.5 11V14M16.5 14V11" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          <path
            d="M2.00224 8.5L2.00009 13.997C1.9988 17.2979 1.99815 18.9484 3.02333 19.974C4.04851 20.9996 5.69899 20.9996 8.99993 20.9997L15.001 20.9998C18.3003 20.9999 19.95 21 20.9751 19.9751C22.0002 18.9502 22.0005 17.3005 22.0012 14.0012L22.0022 8.5"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <span>Деловой туризм</span>
      </a>

    </div>


    <div class="tab-content">
      <div class="gtm-search__item active" data-tab="tours">
        <div class="gtm-search__wrap">

          <select name="" id="" class="gtm-town-select">

          </select>

          <select name="" id="" class="gtm-state-select">

          </select>

          <input type="text" name="daterange" class="gtm-datepicker" placeholder="" />

          <div class="gtm-nights-select js-dropdown">
            <button class="js-dropdown-trigger gtm-nights-select-value">ночей: 5-7</button>
            <div class="js-dropdown-panel numfont">
              <div class="day-grid gtm-daypicker">
                <div class="day-item">1</div>
                <div class="day-item">2</div>

                <div class="day-item">3</div>
                <div class="day-item">4</div>
                <div class="day-item ">5</div>
                <div class="day-item ">6</div>
                <div class="day-item ">7</div>
                <div class="day-item">8</div>
                <div class="day-item">9</div>
                <div class="day-item">10</div>

                <div class="day-item">11</div>
                <div class="day-item">12</div>
                <div class="day-item">13</div>
                <div class="day-item">14</div>
                <div class="day-item">15</div>
                <div class="day-item">16</div>
                <div class="day-item">17</div>

                <div class="day-item">18</div>
                <div class="day-item">19</div>
                <div class="day-item">20</div>

                <div class="day-item">21</div>

                <div class="day-item">22</div>
                <div class="day-item">23</div>
                <div class="day-item">24</div>
                <div class="day-item">25</div>
                <div class="day-item">26</div>
                <div class="day-item">27</div>
                <div class="day-item">28</div>
                <div class="day-item">29</div>
                <div class="day-item">30</div>
              </div>
            </div>
          </div>

          <div class="gtm-persons-select js-dropdown">

            <button class="js-dropdown-trigger">
              <span class="gtm-people-total">2 человека</span>
            </button>
            <div class="js-dropdown-panel gtm-persons-dropdown">
              <div class="person-counter__wrap">

                <div class="person-counter__wrap_top">
                  <div class="people-counter counter-item__wrap">
                    <span class="counter-item__title">Взрослые</span>
                    <div class="people-counter counter-item people-counter--adults">
                      <button class="people-btn counter-item-minus adults-minus">−</button>
                      <span class="people-value counter-item-value adults-value">2</span>
                      <button class="people-btn counter-item-plus adults-plus">+</button>
                    </div>
                  </div>

                  <div class="people-row counter-item__wrap">
                    <span class="counter-item__title">Дети</span>
                    <div class="people-counter counter-item  people-counter--children">
                      <button class="people-btn counter-item-minus children-minus">−</button>
                      <span class="people-value counter-item-value children-value">0</span>
                      <button class="people-btn counter-item-plus children-plus">+</button>
                    </div>
                  </div>
                </div>

                <div class="children-ages"></div>
              </div>
            </div>
          </div>


        </div>

        <button class="btn btn-white gtm-item__button">Найти</button>
      </div>

      <div class="gtm-search__item" data-tab="hotels">
        <div class="gtm-search__wrap --hotels">

          <select class="gtm-state-select"></select>

          <input type="text" name="daterange" class="gtm-datepicker" />

          <div class="gtm-nights-select js-dropdown">
            <button class="js-dropdown-trigger gtm-nights-select-value">ночей: 5-7</button>
            <div class="js-dropdown-panel numfont">
              <div class="day-grid gtm-daypicker">
                <div class="day-item">1</div>
                <div class="day-item">2</div>
                <div class="day-item">3</div>
                <div class="day-item">4</div>
                <div class="day-item">5</div>
                <div class="day-item">6</div>
                <div class="day-item">7</div>
                <div class="day-item">8</div>
                <div class="day-item">9</div>
                <div class="day-item">10</div>
                <div class="day-item">11</div>
                <div class="day-item">12</div>
                <div class="day-item">13</div>
                <div class="day-item">14</div>
                <div class="day-item">15</div>
                <div class="day-item">16</div>
                <div class="day-item">17</div>
                <div class="day-item">18</div>
                <div class="day-item">19</div>
                <div class="day-item">20</div>
                <div class="day-item">21</div>
                <div class="day-item">22</div>
                <div class="day-item">23</div>
                <div class="day-item">24</div>
                <div class="day-item">25</div>
                <div class="day-item">26</div>
                <div class="day-item">27</div>
                <div class="day-item">28</div>
                <div class="day-item">29</div>
                <div class="day-item">30</div>
              </div>
            </div>
          </div>

          <div class="gtm-persons-select js-dropdown">
            <button class="js-dropdown-trigger">
              <span class="gtm-people-total">2 человека</span>
            </button>

            <div class="js-dropdown-panel gtm-persons-dropdown">
              <div class="person-counter__wrap">
                <div class="person-counter__wrap_top">
                  <div class="people-counter counter-item__wrap">
                    <span class="counter-item__title">Взрослые</span>
                    <div class="people-counter counter-item people-counter--adults">
                      <button class="people-btn counter-item-minus adults-minus">−</button>
                      <span class="people-value counter-item-value adults-value">2</span>
                      <button class="people-btn counter-item-plus adults-plus">+</button>
                    </div>
                  </div>

                  <div class="people-row counter-item__wrap">
                    <span class="counter-item__title">Дети</span>
                    <div class="people-counter counter-item people-counter--children">
                      <button class="people-btn counter-item-minus children-minus">−</button>
                      <span class="people-value counter-item-value children-value">0</span>
                      <button class="people-btn counter-item-plus children-plus">+</button>
                    </div>
                  </div>
                </div>

                <div class="children-ages"></div>
              </div>
            </div>
          </div>

        </div>

        <button class="btn btn-white gtm-item__button">Найти</button>
      </div>

      <div class="gtm-search__item" data-tab="tickets" style="display: none;">
      </div>

      <div class="gtm-search__item" data-tab="excursions">
        <div class="gtm-search__wrap --excursions">
          <select class="gtm-state-select"></select>

          <select class="gtm-tours-select">
            <option value="">Выберите тур</option>
          </select>

          <input type="text" name="daterange" class="gtm-datepicker" />

          <div class="gtm-persons-select js-dropdown">
            <button class="js-dropdown-trigger">
              <span class="gtm-people-total">2 человека</span>
            </button>
            <div class="js-dropdown-panel gtm-persons-dropdown">
              <div class="person-counter__wrap">
                <div class="person-counter__wrap_top">
                  <div class="people-counter counter-item__wrap">
                    <span class="counter-item__title">Взрослые</span>
                    <div class="people-counter counter-item people-counter--adults">
                      <button class="people-btn counter-item-minus adults-minus">−</button>
                      <span class="people-value counter-item-value adults-value">2</span>
                      <button class="people-btn counter-item-plus adults-plus">+</button>
                    </div>
                  </div>

                  <div class="people-row counter-item__wrap">
                    <span class="counter-item__title">Дети</span>
                    <div class="people-counter counter-item people-counter--children">
                      <button class="people-btn counter-item-minus children-minus">−</button>
                      <span class="people-value counter-item-value children-value">0</span>
                      <button class="people-btn counter-item-plus children-plus">+</button>
                    </div>
                  </div>
                </div>

                <div class="children-ages"></div>
              </div>
            </div>
          </div>
        </div>

        <button class="btn btn-white gtm-item__button">Найти</button>
      </div>
    </div>
  </div>
</section>