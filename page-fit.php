<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package asd
 */

get_header();
$countries = get_posts([
  'post_type' => 'country',
  'post_status' => 'publish',
  'numberposts' => -1,
  'orderby' => 'title',
  'order' => 'ASC',
  'post_parent' => 0, // только «родительские» страны
]);
$selected_country_id = isset($_GET['country']) ? (int) $_GET['country'] : 0;

?>

<main id="primary" class="site-main">

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section>
    <div class="container">
      <?php the_title('<h1 class="h1 fit-page__title">', '</h1>'); ?>

      <p class="fit-page__description"><?= get_the_excerpt() ?></p>
    </div>
  </section>

  <section class="fit-form__section">
    <div class="container">
      <div class="fit-form__wrap">

        <!-- Табы переключения типа клиента -->
        <div class="fit-form__client-tabs tabs">
          <button type="button" class="tab-button fit-form__client-tab active" data-client-type="corporate">
            Турагентство
          </button>
          <button type="button" class="tab-button fit-form__client-tab" data-client-type="private">
            Частный клиент
          </button>
        </div>

        <form id="simple-form" class="fit-form">
          <input type="hidden" name="client_type" id="client_type" value="corporate">

          <!-- Контактные данные -->
          <div class="form-group">
            <p class="form-group__title">Контактные данные:</p>

            <div class="form-row form-row-3">
              <div class="input-item">
                <label for="full_name">ФИО *</label>
                <input type="text" name="full_name" id="full_name" placeholder="Фамилия Имя Отчество" required>

                <div class="error-message" data-field="full_name"></div>
              </div>

              <div class="input-item">
                <label for="email">Email *</label>
                <input type="email" name="email" id="email" placeholder="Почта" required>

                <div class="error-message" data-field="email"></div>
              </div>

              <div class="input-item">
                <label for="phone">Телефон *</label>
                <input type="tel" name="phone" id="phone" placeholder="+7 (___) ___-__-__" required>

                <div class="error-message" data-field="phone"></div>
              </div>
            </div>

            <!-- Поля только для корпоративных клиентов -->
            <div class="fit-form__corporate-fields">
              <div class="form-row form-row-2">
                <div class="input-item">
                  <label for="company_name">Название организации *</label>
                  <input type="text" name="company_name" id="company_name" placeholder="Название организации">

                  <div class="error-message" data-field="company_name"></div>
                </div>

                <div class="input-item">
                  <label for="inn">ИНН организации *</label>
                  <input type="text" name="inn" id="inn" placeholder="ИНН" maxlength="12">

                  <div class="error-message" data-field="inn"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Страна -->
          <div class="form-group">
            <p class="form-group__title">Страна:</p>
            <div class="form-row">
              <div class="select-item">
                <select name="country_id" class="fit-form__country-select" id="country-select">
                  <option value="">Выберите страну</option>
                  <?php if (!empty($countries)): ?>
                    <?php foreach ($countries as $country_item): ?>
                      <option value="<?= esc_attr($country_item->ID); ?>" <?= selected($selected_country_id, $country_item->ID, false); ?>>
                        <?= esc_html($country_item->post_title); ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
                <div class="error-message" data-field="country_id"></div>
              </div>
            </div>
          </div>

          <!-- Интервал вылета -->
          <div class="form-group">
            <p class="form-group__title">Интервал вылета:</p>
            <div class="form-row">
              <div class="input-item fit-form__datepicker-wrapper numfont">
                <div id="departure_range_calendar" class="fit-form__datepicker-inline"></div>
                <input type="hidden" name="departure_range" id="departure_range">
                <div class="error-message" data-field="departure_range"></div>
              </div>
            </div>
          </div>

          <!-- Продолжительность тура -->
          <div class="form-group">
            <p class="form-group__title">Продолжительность тура:</p>
            <div class="form-row">
              <div class="fit-form__duration-wrapper">
                <div class="day-grid fit-form__daypicker">
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
                <input type="hidden" name="tour_duration" id="tour_duration">
                <div class="error-message" data-field="tour_duration"></div>
              </div>
            </div>
          </div>

          <!-- Бюджет -->
          <div class="form-group">
            <p class="form-group__title">Бюджет:</p>
            <div class="form-row">
              <div class="input-item">
                <input type="text" name="budget" id="budget" class="fit-form__budget-input" placeholder="1 000 000"
                  inputmode="numeric">
                <div class="error-message" data-field="budget"></div>
              </div>
            </div>
          </div>

          <!-- Звездность отеля -->
          <div class="form-group">
            <p class="form-group__title">Звездность отеля:</p>
            <div class="form-row">
              <div class="fit-form__hotel-stars-wrapper">
                <button type="button" class="fit-form__star-btn" data-stars="1">
                  <div class="stars-rating" data-rating="1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="<?= $i <= 1 ? '#ffd700' : 'none' ?>" stroke="<?= $i <= 1 ? '#ffd700' : 'currentColor' ?>"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-star-icon lucide-star star-<?= $i ?> <?= $i <= 1 ? 'filled' : '' ?>">
                        <path
                          d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
                        </path>
                      </svg>
                    <?php endfor; ?>
                  </div>
                </button>
                <button type="button" class="fit-form__star-btn" data-stars="2">
                  <div class="stars-rating" data-rating="2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="<?= $i <= 2 ? '#ffd700' : 'none' ?>" stroke="<?= $i <= 2 ? '#ffd700' : 'currentColor' ?>"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-star-icon lucide-star star-<?= $i ?> <?= $i <= 2 ? 'filled' : '' ?>">
                        <path
                          d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
                        </path>
                      </svg>
                    <?php endfor; ?>
                  </div>
                </button>
                <button type="button" class="fit-form__star-btn" data-stars="3">
                  <div class="stars-rating" data-rating="3">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="<?= $i <= 3 ? '#ffd700' : 'none' ?>" stroke="<?= $i <= 3 ? '#ffd700' : 'currentColor' ?>"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-star-icon lucide-star star-<?= $i ?> <?= $i <= 3 ? 'filled' : '' ?>">
                        <path
                          d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
                        </path>
                      </svg>
                    <?php endfor; ?>
                  </div>
                </button>
                <button type="button" class="fit-form__star-btn" data-stars="4">
                  <div class="stars-rating" data-rating="4">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="<?= $i <= 4 ? '#ffd700' : 'none' ?>" stroke="<?= $i <= 4 ? '#ffd700' : 'currentColor' ?>"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-star-icon lucide-star star-<?= $i ?> <?= $i <= 4 ? 'filled' : '' ?>">
                        <path
                          d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
                        </path>
                      </svg>
                    <?php endfor; ?>
                  </div>
                </button>
                <button type="button" class="fit-form__star-btn" data-stars="5">
                  <div class="stars-rating" data-rating="5">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#ffd700"
                        stroke="#ffd700" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-star-icon lucide-star star-<?= $i ?> filled">
                        <path
                          d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z">
                        </path>
                      </svg>
                    <?php endfor; ?>
                  </div>
                </button>
                <input type="hidden" name="hotel_stars" id="hotel_stars" value="">
                <div class="error-message" data-field="hotel_stars"></div>
              </div>
            </div>
          </div>

          <!-- Услуги -->
          <div class="form-group">
            <p class="form-group__title">Услуги:</p>
            <div class="form-row fit-form__services-row">
              <div class="checkbox-item">
                <input type="checkbox" name="services[]" value="flight" id="service-flight">
                <label for="service-flight">Авиаперелет</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" name="services[]" value="hotel" id="service-hotel">
                <label for="service-hotel">Отель</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" name="services[]" value="transfer" id="service-transfer">
                <label for="service-transfer">Трансфер</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" name="services[]" value="guide" id="service-guide">
                <label for="service-guide">Гид</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" name="services[]" value="excursion" id="service-excursion">
                <label for="service-excursion">Экскурсия</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" name="services[]" value="insurance" id="service-insurance">
                <label for="service-insurance">Страховка</label>
              </div>
              <div class="checkbox-item">
                <input type="checkbox" name="services[]" value="visa" id="service-visa">
                <label for="service-visa">Виза</label>
              </div>
            </div>
          </div>

          <!-- Количество человек -->
          <div class="form-group">
            <p class="form-group__title">Количество человек:</p>
            <div class="form-row">
              <div class="fit-form__people-select js-dropdown">
                <button type="button" class="js-dropdown-trigger">
                  <span class="fit-form__people-total">2 человека</span>
                </button>
                <div class="js-dropdown-panel gtm-persons-dropdown">
                  <div class="person-counter__wrap">
                    <div class="person-counter__wrap_top">
                      <div class="people-counter counter-item__wrap">
                        <span class="counter-item__title">Взрослые</span>
                        <div class="people-counter counter-item people-counter--adults">
                          <button type="button" class="people-btn counter-item-minus adults-minus">−</button>
                          <span class="people-value counter-item-value adults-value">2</span>
                          <button type="button" class="people-btn counter-item-plus adults-plus">+</button>
                        </div>
                      </div>
                      <div class="people-row counter-item__wrap">
                        <span class="counter-item__title">Дети</span>
                        <div class="people-counter counter-item people-counter--children">
                          <button type="button" class="people-btn counter-item-minus children-minus">−</button>
                          <span class="people-value counter-item-value children-value">0</span>
                          <button type="button" class="people-btn counter-item-plus children-plus">+</button>
                        </div>
                      </div>
                    </div>
                    <div class="children-ages"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Комментарии -->
          <div class="form-group">
            <p class="form-group__title">Комментарии:</p>
            <div class="form-row">
              <div class="input-item">
                <textarea name="comments" id="comments" rows="6"
                  placeholder="Дополнительная информация о вашем запросе..."></textarea>
                <div class="error-message" data-field="comments"></div>
              </div>
            </div>
          </div>



          <div class="fit-form__bottom">
            
            <button type="submit" class="btn btn-accent fit-form__btn-submit">
              Отправить
            </button>

            <p class="form-policy fit-form__policy">
              Нажимая на кнопку "Отправить", вы соглашаетесь с <a href="<?= get_permalink(47) ?>" class="policy-link">
                нашей политикой обработки персональных данных
              </a>
            </p>
            <div id="form-status"></div>
          </div>
        </form>

      </div>
    </div>
  </section>


</main><!-- #main -->

<?php
// get_sidebar();
get_footer();
