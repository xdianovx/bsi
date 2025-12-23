<?php
/*
Template Name: Визы
*/

// Фолбэк-данные для процедуры, если ACF-поля ещё не заполнены
$steps = [
  [
    'num' => '1',
    'title' => 'Сбор документов',
    'descr' => 'Предоставьте нам информацию'
  ],
  [
    'num' => '2',
    'title' => 'Оформление',
    'descr' => 'Мы готовим документы для подачи'
  ],
  [
    'num' => '3',
    'title' => 'Подача в консульство',
    'descr' => 'Лично или через нашего представителя'
  ],
  [
    'num' => '4',
    'title' => 'Получение паспорта',
    'descr' => 'Лично или через нашего представителя'
  ],
];

get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="page-head archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 page-award__title archive-page__title ">
          <?php the_title(); ?>
        </h1>

        <p class="page-award__excerpt archive-page__excerpt"><?= get_the_excerpt(); ?></p>
      </div>
    </div>
  </section>


  <section class="visa-page__info-section">
    <div class="container">

      <div class="visa-page__info">
        <div class="visa-info-item__wrap">

          <!-- item -->
          <div class="visa-info-item">
            <div class="visa-info-item__title">
              <div class="visa-info-item__icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="20"
                     height="20"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     class="lucide lucide-clock-icon lucide-clock">
                  <path d="M12 6v6l4 2" />
                  <circle cx="12"
                          cy="12"
                          r="10" />
                </svg>
              </div>
              <p class="visa-info-item__key">Прием и выдача документов:</p>
            </div>
            <p class="visa-info-item__value">ПН – ПТ с 10:00 до 18:00</p>
          </div>

          <!-- item -->
          <div class="visa-info-item">
            <div class="visa-info-item__title">
              <div class="visa-info-item__icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="20"
                     height="20"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     class="lucide lucide-map-pin-house-icon lucide-map-pin-house">
                  <path
                        d="M15 22a1 1 0 0 1-1-1v-4a1 1 0 0 1 .445-.832l3-2a1 1 0 0 1 1.11 0l3 2A1 1 0 0 1 22 17v4a1 1 0 0 1-1 1z" />
                  <path d="M18 10a8 8 0 0 0-16 0c0 4.993 5.539 10.193 7.399 11.799a1 1 0 0 0 .601.2" />
                  <path d="M18 22v-3" />
                  <circle cx="10"
                          cy="10"
                          r="3" />
                </svg>
              </div>
              <p class="visa-info-item__key">Адрес:</p>
            </div>
            <p class="visa-info-item__value">
              г. Москва, ул. Садовая-Кудринская д. 2/62/35, строение 1, этаж 3. м. Баррикадная, тел.: <a
                 href="tel:+7 (495) 785-55-35">+7 (495) 785-55-35</a>
            </p>
          </div>

          <div class="callout callout-neutral">
            <h3 class="callout__title">
              Правила Выдачи документов по путевке:
            </h3>

            <p>
              Все документы по турам выдаются только при наличии: счет-подтверждения на тур, паспорта гражданина РФ
            </p>
          </div>

        </div>
      </div>
    </div>
  </section>


  <section class="visa-page-features__section">
    <div class="container">
      <h2 class="h2">Наши преимущества</h2>

      <div class="visa-page-features__wrap">
        <?php if (function_exists('have_rows') && have_rows('vizy_benefits')): ?>
          <?php while (have_rows('vizy_benefits')):
            the_row(); ?>
            <?php
            $img = get_sub_field('image');
            $title = (string) get_sub_field('title');
            $desc = (string) get_sub_field('description');
            ?>
            <div class="visa-page-features__item">
              <div class="visa-page-features__item__wrap">

                <?php if (!empty($img['url'])): ?>
                  <div class="visa-page-features__item-icon">
                    <img src="<?php echo esc_url($img['url']); ?>"
                         alt="<?php echo esc_attr($title); ?>">
                  </div>
                <?php endif; ?>

                <?php if (!empty($title)): ?>
                  <div class="visa-page-features__item-title">
                    <?php echo esc_html($title); ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($desc)): ?>
                  <div class="visa-page-features__item-description">
                    <?php echo wp_kses_post(nl2br($desc)); ?>
                  </div>
                <?php endif; ?>

              </div>
            </div>
          <?php endwhile; ?>

        <?php else: ?>
          <!-- Фолбэк, если ACF ещё не заполнен -->
          <div class="visa-page-features__item">
            <div class="visa-page-features__item__wrap">
              <div class="visa-page-features__item-title">
                Оформляем визы с 2005 года
              </div>
            </div>
          </div>

          <div class="visa-page-features__item">
            <div class="visa-page-features__item__wrap">
              <div class="visa-page-features__item-title">
                Индивидуальный подход к каждому клиенту
              </div>
            </div>
          </div>

          <div class="visa-page-features__item">
            <div class="visa-page-features__item__wrap">
              <div class="visa-page-features__item-title">
                Поддержка на всех этапах оформления
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <section class="visa-page-steps__section">
    <div class="container">
      <h2 class="h2">Процедура оформления</h2>

      <div class="visa-page-steps__wrap">
        <?php if (function_exists('have_rows') && have_rows('vizy_procedure')): ?>
          <?php while (have_rows('vizy_procedure')):
            the_row(); ?>
            <?php
            $img = get_sub_field('image');
            $num = get_sub_field('order');
            $title = (string) get_sub_field('title');
            $descr = (string) get_sub_field('description');
            ?>
            <div class="visa-page-steps-item">
              <div class="visa-page-steps-item__top">
                <?php if (!empty($num) || $num === 0 || $num === '0'): ?>
                  <div class="visa-page-steps-item__num numfont">
                    <?php echo esc_html($num); ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($title)): ?>
                  <div class="visa-page-steps-item__title">
                    <?php echo esc_html($title); ?>
                  </div>
                <?php endif; ?>
              </div>

              <?php if (!empty($descr)): ?>
                <div class="visa-page-steps-item__description">
                  <?php echo wp_kses_post($descr); ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($img['url'])): ?>
                <div class="visa-page-steps-item__icon">
                  <img src="<?php echo esc_url($img['url']); ?>"
                       alt="<?php echo esc_attr($title); ?>">
                </div>
              <?php endif; ?>
            </div>
          <?php endwhile; ?>

        <?php else: ?>
          <?php foreach ($steps as $step): ?>
            <div class="visa-page-steps-item">
              <div class="visa-page-steps-item__num">
                <?php echo esc_html($step['num']); ?>
              </div>
              <div class="visa-page-steps-item__title">
                <?php echo esc_html($step['title']); ?>
              </div>
              <div class="visa-page-steps-item__description">
                <?php echo esc_html($step['descr']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>


  <?php
  /**
   * Страны с визами + список доступных типов виз по стране
   * Требования:
   * - CPT: visa
   * - ACF поле: visa_country (ID / post_object)
   * - Таксономия: visa_type
   * - У страны ACF поле: flag (url или array['url'])
   */

  $visa_ids = get_posts([
    'post_type' => 'visa',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'orderby' => 'date',
    'order' => 'DESC',
  ]);

  $country_to_visas = []; // [country_id => [visa_id, visa_id...]]
  $country_ids = [];

  if (!empty($visa_ids) && function_exists('get_field')) {
    foreach ($visa_ids as $vid) {
      $country_id = get_field('visa_country', $vid);

      if ($country_id instanceof WP_Post) {
        $country_id = (int) $country_id->ID;
      } elseif (is_array($country_id)) {
        $country_id = (int) reset($country_id);
      } else {
        $country_id = (int) $country_id;
      }

      if ($country_id > 0) {
        $country_ids[] = $country_id;
        if (empty($country_to_visas[$country_id])) {
          $country_to_visas[$country_id] = [];
        }
        $country_to_visas[$country_id][] = (int) $vid;
      }
    }
  }

  $country_ids = array_values(array_unique(array_filter($country_ids)));

  $countries = [];
  if (!empty($country_ids)) {
    $countries = get_posts([
      'post_type' => 'country',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'title',
      'order' => 'ASC',
      'post__in' => $country_ids,
    ]);
  }
  ?>

  <?php if (!empty($countries)): ?>
    <section class="visa-page-countries-section">
      <div class="container">
        <h2 class="h2">Визы по странам</h2>

        <div class="visa-countries__list">
          <?php foreach ($countries as $country): ?>
            <?php
            $cid = (int) $country->ID;
            $country_slug = (string) $country->post_name;
            $country_title = (string) get_the_title($cid);

            // флаг
            $flag_url = '';
            if (function_exists('get_field')) {
              $flag_field = get_field('flag', $cid);
              if ($flag_field) {
                if (is_array($flag_field) && !empty($flag_field['url'])) {
                  $flag_url = (string) $flag_field['url'];
                } elseif (is_string($flag_field)) {
                  $flag_url = (string) $flag_field;
                }
              }
            }

            $visa_ids_for_country = !empty($country_to_visas[$cid]) ? $country_to_visas[$cid] : [];

            // типы виз, которые реально есть у виз этой страны
            $types = [];
            if (!empty($visa_ids_for_country)) {
              $types = wp_get_object_terms($visa_ids_for_country, 'visa_type', [
                'orderby' => 'name',
                'order' => 'ASC',
              ]);
              if (is_wp_error($types)) {
                $types = [];
              }
            }

            // если вдруг у страны есть визы, но не назначен ни один visa_type — можно скрыть блок типов
            $country_visa_url = home_url('/country/' . $country_slug . '/visa/');
            ?>

            <div class="visa-country-card">
              <div class="visa-country-card__head">
                <?php if ($flag_url): ?>
                  <img class="visa-country-card__flag"
                       src="<?php echo esc_url($flag_url); ?>"
                       alt="<?php echo esc_attr($country_title); ?>">
                <?php endif; ?>

                <div class="visa-country-card__title">
                  <?php echo esc_html($country_title); ?>
                </div>


              </div>

              <?php if (!empty($types)): ?>
                <div class="visa-country-card__types">
                  <?php foreach ($types as $t): ?>
                    <?php
                    $url = add_query_arg(['visa_type[]' => (int) $t->term_id], $country_visa_url);
                    ?>
                    <a class="visa-country-card__type"
                       href="<?php echo esc_url($url); ?>">
                      <?php echo esc_html($t->name); ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="visa-country-card__types">
                  <!-- можно оставить пустым/скрывать стилями -->
                </div>
              <?php endif; ?>
            </div>

          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>


  <?php if (function_exists('have_rows') && have_rows('vizy_contacts')): ?>
    <section class="visa-page-contacts__section">
      <div class="container">
        <h2 class="h2">Контакты</h2>

        <div class="visa-page-contacts__wrap">
          <?php while (have_rows('vizy_contacts')):
            the_row(); ?>
            <?php
            $name = (string) get_sub_field('name');
            $direction = (string) get_sub_field('direction');
            $phone = (string) get_sub_field('phone'); // реальный номер
            $phone_label = (string) get_sub_field('phone_label'); // текст, который показываем вместо номера
            $email = (string) get_sub_field('email');

            $tel = preg_replace('/[^0-9\+]/', '', $phone);
            ?>

            <div class="visa-contact-item">
              <div class="visa-contact-item__inner">

                <?php if ($name): ?>
                  <div class="visa-contact-item__name"><?php echo esc_html($name); ?></div>
                <?php endif; ?>

                <?php if ($direction): ?>
                  <div class="visa-contact-item__direction"><?php echo esc_html($direction); ?></div>
                <?php endif; ?>

                <div class="visa-contact-item__links">
                  <?php if ($phone): ?>
                    <a class="visa-contact-item__phone visa-contact-item__link numfont"
                       href="<?php echo esc_url('tel:' . $tel); ?>">
                      <svg xmlns="http://www.w3.org/2000/svg"
                           width="24"
                           height="24"
                           viewBox="0 0 24 24"
                           fill="none"
                           stroke="currentColor"
                           stroke-width="1.5"
                           stroke-linecap="round"
                           stroke-linejoin="round"
                           class="lucide lucide-phone-call-icon lucide-phone-call">
                        <path d="M13 2a9 9 0 0 1 9 9" />
                        <path d="M13 6a5 5 0 0 1 5 5" />
                        <path
                              d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />
                      </svg>
                      <span><?php echo esc_html($phone_label ?: $phone); ?></span>
                    </a>
                  <?php endif; ?>

                  <?php if ($email): ?>
                    <a class="visa-contact-item__email visa-contact-item__link numfont"
                       href="<?php echo esc_url('mailto:' . $email); ?>">
                      <svg xmlns="http://www.w3.org/2000/svg"
                           width="24"
                           height="24"
                           viewBox="0 0 24 24"
                           fill="none"
                           stroke="currentColor"
                           stroke-width="1.5"
                           stroke-linecap="round"
                           stroke-linejoin="round"
                           class="lucide lucide-mail-check-icon lucide-mail-check">
                        <path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h8" />
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        <path d="m16 19 2 2 4-4" />
                      </svg>
                      <span><?php echo esc_html($email); ?></span>
                    </a>
                  <?php endif; ?>
                </div>

              </div>
            </div>

          <?php endwhile; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="visa-page-consultation__section">
    <div class="container">
      <h2 class="h2">Бесплатная консультация</h2>
      <p class="visa-consultation-form__descr">Оставьте заявку и проконсультируем вас по вопросам получения виз</p>
      <form action=""
            class="visa-consultation-form">

        <div class="form-row form-row-2">

          <div class="input-item white">

            <label for="name">Страна</label>
            <input type="text"
                   name="country"
                   id="country"
                   placeholder="Страна">

            <div class="error-message"
                 data-field="country">
            </div>
          </div>

          <div class="input-item white">
            <label for="visatype">Тип визы</label>
            <input type="text"
                   name="visatype"
                   id="visatype"
                   placeholder="Страна">

            <div class="error-message"
                 data-field="visatype">
            </div>
          </div>

          <div class="input-item white">
            <label for="name">Имя</label>
            <input type="text"
                   name="name"
                   id="name"
                   placeholder="Имя">

            <div class="error-message"
                 data-field="name">
            </div>
          </div>

          <div class="input-item white">
            <label for="graz">Гражданство</label>
            <input type="text"
                   name="graz"
                   id="graz"
                   placeholder="Гражданство">

            <div class="error-message"
                 data-field="graz">
            </div>
          </div>


          <div class="input-item white">
            <label for="tel">Телефон</label>
            <input type="tel"
                   name="tel"
                   id="tel"
                   placeholder="Имя">

            <div class="error-message"
                 data-field="tel">
            </div>
          </div>


          <div class="input-item white">
            <label for="graz">Дата поездки</label>
            <input type="text"
                   name="graz"
                   id="graz"
                   placeholder="Гражданство">

            <div class="error-message"
                 data-field="graz">
            </div>
          </div>


        </div>
        <div class="visa-consultation-form__bottom">
          <div id="form-status"></div>
          <button type="submit"
                  class="btn btn-accent fit-form__btn-submit">
            Отправить
          </button>

          <p class="form-policy fit-form__policy">
            Нажимая на кнопку "Отправить", вы соглашаетесь с <a
               href="http://webscape.beget.tech/bsi/politika-v-otnoshenii-obrabotki-personalnyh-dannyh/"
               class="policy-link">
              нашей политикой обработки персональных данных
            </a>
          </p>
        </div>
      </form>
    </div>
  </section>

</main>

<?php
get_footer();