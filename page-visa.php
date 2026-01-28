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

// Загружаем список стран
$countries = get_posts([
  'post_type' => 'country',
  'post_status' => 'publish',
  'numberposts' => -1,
  'orderby' => 'title',
  'order' => 'ASC',
  'post_parent' => 0, // только «родительские» страны
]);
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
        <!-- 
        <p class="page-award__excerpt archive-page__excerpt"><?= get_the_excerpt(); ?></p> -->
        <p class="visa-disclaimer">Визовое сопровождение и поддержка c 93% одобрением в более чем 48 направлений с 1990
          года</p>
      </div>
    </div>
  </section>


  <section class="visa-page__info-section">
    <div class="container">
      <div class="visa-page__info">
        <div class="visa-info-img">
          <img src="<?php echo get_template_directory_uri() ?>/img/page-visa/9622845-1.webp" alt="">
        </div>
        <div class="visa-info-item__wrap">


          <!-- item -->
          <div class="visa-info-item">
            <div class="visa-info-item__title">

              <p class="visa-info-item__key">Прием и выдача документов</p>
            </div>
            <p class="visa-info-item__value">ПН – ПТ с 10:00 до 18:00</p>
          </div>


          <!-- item -->
          <div class="visa-info-item">
            <div class="visa-info-item__title">

              <p class="visa-info-item__key">Адрес</p>
            </div>
            <p class="visa-info-item__value">
              г. Москва, ул. Садовая-Кудринская д. 2/62/35, строение 1, этаж 3. м. Баррикадная
            </p>
          </div>


          <!-- item -->
          <div class="visa-info-item">
            <div class="visa-info-item__title">


              <p class="visa-info-item__key">Контакты</p>
            </div>
            <div class="visa-info-item__value">
              <div class="visa-info-item__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <g clip-path="url(#clip0_713_1775)">
                    <path
                      d="M10.8327 1.66675C12.8218 1.66675 14.7295 2.45692 16.136 3.86345C17.5425 5.26997 18.3327 7.17762 18.3327 9.16675M10.8327 5.00008C11.9378 5.00008 12.9976 5.43907 13.779 6.22047C14.5604 7.00187 14.9993 8.06168 14.9993 9.16675M11.526 13.8067C11.6981 13.8858 11.892 13.9038 12.0758 13.8579C12.2595 13.8121 12.4221 13.7049 12.5368 13.5542L12.8327 13.1667C12.9879 12.9598 13.1892 12.7917 13.4207 12.676C13.6521 12.5603 13.9073 12.5001 14.166 12.5001H16.666C17.108 12.5001 17.532 12.6757 17.8445 12.9882C18.1571 13.3008 18.3327 13.7247 18.3327 14.1667V16.6667C18.3327 17.1088 18.1571 17.5327 17.8445 17.8453C17.532 18.1578 17.108 18.3334 16.666 18.3334C12.6878 18.3334 8.87246 16.7531 6.05941 13.94C3.24637 11.127 1.66602 7.31166 1.66602 3.33341C1.66602 2.89139 1.84161 2.46746 2.15417 2.1549C2.46673 1.84234 2.89065 1.66675 3.33268 1.66675H5.83268C6.27471 1.66675 6.69863 1.84234 7.01119 2.1549C7.32375 2.46746 7.49935 2.89139 7.49935 3.33341V5.83341C7.49935 6.09216 7.43911 6.34734 7.32339 6.57877C7.20768 6.8102 7.03968 7.0115 6.83268 7.16675L6.44268 7.45925C6.2897 7.57606 6.18187 7.74224 6.13751 7.92954C6.09315 8.11684 6.115 8.31373 6.19935 8.48675C7.33825 10.8 9.21137 12.6707 11.526 13.8067Z"
                      stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </g>
                  <defs>
                    <clipPath id="clip0_713_1775">
                      <rect width="20" height="20" fill="white" />
                    </clipPath>
                  </defs>
                </svg>
              </div>
              <a href="tel:+7 (495) 785-55-35">+7 999 999 99 99 доб. 198</a>
            </div>
            <div class="visa-info-item__value">
              <div class="visa-info-item__icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                  <path
                    d="M20.1673 11.9167V5.50008C20.1673 5.01385 19.9742 4.54754 19.6303 4.20372C19.2865 3.8599 18.8202 3.66675 18.334 3.66675H3.66732C3.18109 3.66675 2.71477 3.8599 2.37096 4.20372C2.02714 4.54754 1.83398 5.01385 1.83398 5.50008V16.5001C1.83398 17.5084 2.65898 18.3334 3.66732 18.3334H11.0007M20.1673 6.41675L11.9448 11.6417C11.6618 11.8191 11.3346 11.9131 11.0007 11.9131C10.6667 11.9131 10.3395 11.8191 10.0565 11.6417L1.83398 6.41675M14.6673 17.4167L16.5007 19.2501L20.1673 15.5834"
                    stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
              <a class="visa-info-mail" href="mailto: v.ivanova@bsigroup.ru">v.ivanova@bsigroup.ru</a>
            </div>
          </div>


          <a class="visa-page-anchor-form" href="#contact-form">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M9.99935 4.16675V15.8334M9.99935 15.8334L15.8327 10.0001M9.99935 15.8334L4.16602 10.0001"
                stroke="#EE3145" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>Заполнить форму</span>
          </a>




        </div>


        <!-- <div class="visa-info-item --warn">
          <div class="visa-info-item__title">
            <div class="visa-info-item__icon">


              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="lucide lucide-info">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 16v-4" />
                <path d="M12 8h.01" />
              </svg>
            </div>
            <p class="visa-info-item__key">
              Правила Выдачи документов по путевке:
            </p>



          </div>
          <p class="visa-info-item__value">
            Все документы по турам выдаются только при наличии: счет-подтверждения на тур, паспорта гражданина РФ
          </p>
        </div> -->


      </div>
      <div class="visa-info-advantages__wrap">
        <div class="visa-info-advantage">
          <div class="visa-info-advantage__title">
            35 лет
          </div>
          <div class="visa-info-advantage__text">
            на рынке
          </div>
        </div>
        <div class="visa-info-advantage">
          <div class="visa-info-advantage__title">
            93%
          </div>
          <div class="visa-info-advantage__text">
            одобрения
          </div>
        </div>


        <div class="visa-info-advantage">
          <div class="visa-info-advantage__title">
            48
          </div>
          <div class="visa-info-advantage__text">
            направлений
          </div>
        </div>
        <div class="visa-info-advantage">
          <div class="visa-info-advantage__title">
            > 1 000
          </div>
          <div class="visa-info-advantage__text">
            Оформленных виз
          </div>
        </div>
      </div>
    </div>
  </section>



  <!-- <section class="visa-page-features__section">
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
                    <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($title); ?>">
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
  </section> -->





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

  $countries_with_visas = [];
  if (!empty($country_ids)) {
    $countries_with_visas = get_posts([
      'post_type' => 'country',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'title',
      'order' => 'ASC',
      'post__in' => $country_ids,
    ]);
  }
  ?>

  <?php if (!empty($countries_with_visas)): ?>
    <section class="visa-page-countries-section">
      <div class="container">
        <h2 class="h2">Визы по странам</h2>

        <div class="visa-countries__list">
          <?php foreach ($countries_with_visas as $country): ?>
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
                  <img class="visa-country-card__flag" src="<?php echo esc_url($flag_url); ?>"
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
                    <a class="visa-country-card__type" href="<?php echo esc_url($url); ?>">
                      <?php echo esc_html($t->name); ?>
                      <!-- тут цены на визы -->
                      <span class="visa-country-card__price"></span>
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
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
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
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
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

  <section class="visa-page-steps__section">
    <div class="container">
      <h2 class="h2">Процедура оформления</h2>

      <div class="visa-page-steps__wrap">
        <?php if (function_exists('have_rows') && have_rows('vizy_procedure')): ?>
          <?php
          $index = 0;
          $total_rows = count(get_field('vizy_procedure'));
          ?>
          <?php while (have_rows('vizy_procedure')):
            the_row(); ?>
            <?php
            $img = get_sub_field('image');
            $num = get_sub_field('order');
            $title = (string) get_sub_field('title');
            $descr = (string) get_sub_field('description');

            // Определяем тип стрелки
            $arrow_type = 'none';
            if ($index < $total_rows - 1) {
              $arrow_type = ($index === 1) ? 'top' : 'bottom';
            }
            ?>
            <div class="visa-page-steps-item" data-arrow="<?php echo $arrow_type; ?>">
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
                  <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($title); ?>">
                </div>
              <?php endif; ?>
            </div>
            <?php $index++; ?>
          <?php endwhile; ?>

        <?php else: ?>
          <?php foreach ($steps as $index => $step): ?>
            <?php
            $arrow_type = 'none';
            if ($index < count($steps) - 1) {
              $arrow_type = ($index === 1) ? 'top' : 'bottom';
            }
            ?>
            <div class="visa-page-steps-item" data-arrow="<?php echo $arrow_type; ?>">
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

  <section class="visa-page-contacts__section">
    <div class="container">
      <div class="visa-page-contacts__top">
        <h2 class="h2">Контакты</h2>
        <a class="visa-page-anchor-form" href="#contact-form">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M9.99935 4.16675V15.8334M9.99935 15.8334L15.8327 10.0001M9.99935 15.8334L4.16602 10.0001"
              stroke="#EE3145" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <span>Заполнить форму</span>
        </a>
      </div>


      <div class="visa-page-contacts__wrap">

        <div class="visa-page-contacts__item">
          <div class="visa-page-contacts__title">Виктория Иванова</div>
          <div class="visa-page-contacts__text">Специалист визового центра</div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <g clip-path="url(#clip0_713_1775)">
                  <path
                    d="M10.8327 1.66675C12.8218 1.66675 14.7295 2.45692 16.136 3.86345C17.5425 5.26997 18.3327 7.17762 18.3327 9.16675M10.8327 5.00008C11.9378 5.00008 12.9976 5.43907 13.779 6.22047C14.5604 7.00187 14.9993 8.06168 14.9993 9.16675M11.526 13.8067C11.6981 13.8858 11.892 13.9038 12.0758 13.8579C12.2595 13.8121 12.4221 13.7049 12.5368 13.5542L12.8327 13.1667C12.9879 12.9598 13.1892 12.7917 13.4207 12.676C13.6521 12.5603 13.9073 12.5001 14.166 12.5001H16.666C17.108 12.5001 17.532 12.6757 17.8445 12.9882C18.1571 13.3008 18.3327 13.7247 18.3327 14.1667V16.6667C18.3327 17.1088 18.1571 17.5327 17.8445 17.8453C17.532 18.1578 17.108 18.3334 16.666 18.3334C12.6878 18.3334 8.87246 16.7531 6.05941 13.94C3.24637 11.127 1.66602 7.31166 1.66602 3.33341C1.66602 2.89139 1.84161 2.46746 2.15417 2.1549C2.46673 1.84234 2.89065 1.66675 3.33268 1.66675H5.83268C6.27471 1.66675 6.69863 1.84234 7.01119 2.1549C7.32375 2.46746 7.49935 2.89139 7.49935 3.33341V5.83341C7.49935 6.09216 7.43911 6.34734 7.32339 6.57877C7.20768 6.8102 7.03968 7.0115 6.83268 7.16675L6.44268 7.45925C6.2897 7.57606 6.18187 7.74224 6.13751 7.92954C6.09315 8.11684 6.115 8.31373 6.19935 8.48675C7.33825 10.8 9.21137 12.6707 11.526 13.8067Z"
                    stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </g>
                <defs>
                  <clipPath id="clip0_713_1775">
                    <rect width="20" height="20" fill="white"></rect>
                  </clipPath>
                </defs>
              </svg>
            </div>
            <a href="tel:+7 (495) 785-55-35">+7 999 999 99 99 доб. 198</a>
          </div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                <path
                  d="M20.1673 11.9167V5.50008C20.1673 5.01385 19.9742 4.54754 19.6303 4.20372C19.2865 3.8599 18.8202 3.66675 18.334 3.66675H3.66732C3.18109 3.66675 2.71477 3.8599 2.37096 4.20372C2.02714 4.54754 1.83398 5.01385 1.83398 5.50008V16.5001C1.83398 17.5084 2.65898 18.3334 3.66732 18.3334H11.0007M20.1673 6.41675L11.9448 11.6417C11.6618 11.8191 11.3346 11.9131 11.0007 11.9131C10.6667 11.9131 10.3395 11.8191 10.0565 11.6417L1.83398 6.41675M14.6673 17.4167L16.5007 19.2501L20.1673 15.5834"
                  stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
            </div>
            <a class="visa-contacts-mail" href="mailto: v.ivanova@bsigroup.ru">v.ivanova@bsigroup.ru</a>
          </div>
        </div>
        <div class="visa-page-contacts__item">
          <div class="visa-page-contacts__title">Виктория Иванова</div>
          <div class="visa-page-contacts__text">Специалист визового центра</div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <g clip-path="url(#clip0_713_1775)">
                  <path
                    d="M10.8327 1.66675C12.8218 1.66675 14.7295 2.45692 16.136 3.86345C17.5425 5.26997 18.3327 7.17762 18.3327 9.16675M10.8327 5.00008C11.9378 5.00008 12.9976 5.43907 13.779 6.22047C14.5604 7.00187 14.9993 8.06168 14.9993 9.16675M11.526 13.8067C11.6981 13.8858 11.892 13.9038 12.0758 13.8579C12.2595 13.8121 12.4221 13.7049 12.5368 13.5542L12.8327 13.1667C12.9879 12.9598 13.1892 12.7917 13.4207 12.676C13.6521 12.5603 13.9073 12.5001 14.166 12.5001H16.666C17.108 12.5001 17.532 12.6757 17.8445 12.9882C18.1571 13.3008 18.3327 13.7247 18.3327 14.1667V16.6667C18.3327 17.1088 18.1571 17.5327 17.8445 17.8453C17.532 18.1578 17.108 18.3334 16.666 18.3334C12.6878 18.3334 8.87246 16.7531 6.05941 13.94C3.24637 11.127 1.66602 7.31166 1.66602 3.33341C1.66602 2.89139 1.84161 2.46746 2.15417 2.1549C2.46673 1.84234 2.89065 1.66675 3.33268 1.66675H5.83268C6.27471 1.66675 6.69863 1.84234 7.01119 2.1549C7.32375 2.46746 7.49935 2.89139 7.49935 3.33341V5.83341C7.49935 6.09216 7.43911 6.34734 7.32339 6.57877C7.20768 6.8102 7.03968 7.0115 6.83268 7.16675L6.44268 7.45925C6.2897 7.57606 6.18187 7.74224 6.13751 7.92954C6.09315 8.11684 6.115 8.31373 6.19935 8.48675C7.33825 10.8 9.21137 12.6707 11.526 13.8067Z"
                    stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </g>
                <defs>
                  <clipPath id="clip0_713_1775">
                    <rect width="20" height="20" fill="white"></rect>
                  </clipPath>
                </defs>
              </svg>
            </div>
            <a href="tel:+7 (495) 785-55-35">+7 999 999 99 99 доб. 198</a>
          </div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                <path
                  d="M20.1673 11.9167V5.50008C20.1673 5.01385 19.9742 4.54754 19.6303 4.20372C19.2865 3.8599 18.8202 3.66675 18.334 3.66675H3.66732C3.18109 3.66675 2.71477 3.8599 2.37096 4.20372C2.02714 4.54754 1.83398 5.01385 1.83398 5.50008V16.5001C1.83398 17.5084 2.65898 18.3334 3.66732 18.3334H11.0007M20.1673 6.41675L11.9448 11.6417C11.6618 11.8191 11.3346 11.9131 11.0007 11.9131C10.6667 11.9131 10.3395 11.8191 10.0565 11.6417L1.83398 6.41675M14.6673 17.4167L16.5007 19.2501L20.1673 15.5834"
                  stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
            </div>
            <a class="visa-contacts-mail" href="mailto: v.ivanova@bsigroup.ru">v.ivanova@bsigroup.ru</a>
          </div>
        </div>
        <div class="visa-page-contacts__item">
          <div class="visa-page-contacts__title">Виктория Иванова</div>
          <div class="visa-page-contacts__text">Специалист визового центра</div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <g clip-path="url(#clip0_713_1775)">
                  <path
                    d="M10.8327 1.66675C12.8218 1.66675 14.7295 2.45692 16.136 3.86345C17.5425 5.26997 18.3327 7.17762 18.3327 9.16675M10.8327 5.00008C11.9378 5.00008 12.9976 5.43907 13.779 6.22047C14.5604 7.00187 14.9993 8.06168 14.9993 9.16675M11.526 13.8067C11.6981 13.8858 11.892 13.9038 12.0758 13.8579C12.2595 13.8121 12.4221 13.7049 12.5368 13.5542L12.8327 13.1667C12.9879 12.9598 13.1892 12.7917 13.4207 12.676C13.6521 12.5603 13.9073 12.5001 14.166 12.5001H16.666C17.108 12.5001 17.532 12.6757 17.8445 12.9882C18.1571 13.3008 18.3327 13.7247 18.3327 14.1667V16.6667C18.3327 17.1088 18.1571 17.5327 17.8445 17.8453C17.532 18.1578 17.108 18.3334 16.666 18.3334C12.6878 18.3334 8.87246 16.7531 6.05941 13.94C3.24637 11.127 1.66602 7.31166 1.66602 3.33341C1.66602 2.89139 1.84161 2.46746 2.15417 2.1549C2.46673 1.84234 2.89065 1.66675 3.33268 1.66675H5.83268C6.27471 1.66675 6.69863 1.84234 7.01119 2.1549C7.32375 2.46746 7.49935 2.89139 7.49935 3.33341V5.83341C7.49935 6.09216 7.43911 6.34734 7.32339 6.57877C7.20768 6.8102 7.03968 7.0115 6.83268 7.16675L6.44268 7.45925C6.2897 7.57606 6.18187 7.74224 6.13751 7.92954C6.09315 8.11684 6.115 8.31373 6.19935 8.48675C7.33825 10.8 9.21137 12.6707 11.526 13.8067Z"
                    stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </g>
                <defs>
                  <clipPath id="clip0_713_1775">
                    <rect width="20" height="20" fill="white"></rect>
                  </clipPath>
                </defs>
              </svg>
            </div>
            <a href="tel:+7 (495) 785-55-35">+7 999 999 99 99 доб. 198</a>
          </div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                <path
                  d="M20.1673 11.9167V5.50008C20.1673 5.01385 19.9742 4.54754 19.6303 4.20372C19.2865 3.8599 18.8202 3.66675 18.334 3.66675H3.66732C3.18109 3.66675 2.71477 3.8599 2.37096 4.20372C2.02714 4.54754 1.83398 5.01385 1.83398 5.50008V16.5001C1.83398 17.5084 2.65898 18.3334 3.66732 18.3334H11.0007M20.1673 6.41675L11.9448 11.6417C11.6618 11.8191 11.3346 11.9131 11.0007 11.9131C10.6667 11.9131 10.3395 11.8191 10.0565 11.6417L1.83398 6.41675M14.6673 17.4167L16.5007 19.2501L20.1673 15.5834"
                  stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
            </div>
            <a class="visa-contacts-mail" href="mailto: v.ivanova@bsigroup.ru">v.ivanova@bsigroup.ru</a>
          </div>
        </div>
        <div class="visa-page-contacts__item">
          <div class="visa-page-contacts__title">Виктория Иванова</div>
          <div class="visa-page-contacts__text">Специалист визового центра</div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <g clip-path="url(#clip0_713_1775)">
                  <path
                    d="M10.8327 1.66675C12.8218 1.66675 14.7295 2.45692 16.136 3.86345C17.5425 5.26997 18.3327 7.17762 18.3327 9.16675M10.8327 5.00008C11.9378 5.00008 12.9976 5.43907 13.779 6.22047C14.5604 7.00187 14.9993 8.06168 14.9993 9.16675M11.526 13.8067C11.6981 13.8858 11.892 13.9038 12.0758 13.8579C12.2595 13.8121 12.4221 13.7049 12.5368 13.5542L12.8327 13.1667C12.9879 12.9598 13.1892 12.7917 13.4207 12.676C13.6521 12.5603 13.9073 12.5001 14.166 12.5001H16.666C17.108 12.5001 17.532 12.6757 17.8445 12.9882C18.1571 13.3008 18.3327 13.7247 18.3327 14.1667V16.6667C18.3327 17.1088 18.1571 17.5327 17.8445 17.8453C17.532 18.1578 17.108 18.3334 16.666 18.3334C12.6878 18.3334 8.87246 16.7531 6.05941 13.94C3.24637 11.127 1.66602 7.31166 1.66602 3.33341C1.66602 2.89139 1.84161 2.46746 2.15417 2.1549C2.46673 1.84234 2.89065 1.66675 3.33268 1.66675H5.83268C6.27471 1.66675 6.69863 1.84234 7.01119 2.1549C7.32375 2.46746 7.49935 2.89139 7.49935 3.33341V5.83341C7.49935 6.09216 7.43911 6.34734 7.32339 6.57877C7.20768 6.8102 7.03968 7.0115 6.83268 7.16675L6.44268 7.45925C6.2897 7.57606 6.18187 7.74224 6.13751 7.92954C6.09315 8.11684 6.115 8.31373 6.19935 8.48675C7.33825 10.8 9.21137 12.6707 11.526 13.8067Z"
                    stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </g>
                <defs>
                  <clipPath id="clip0_713_1775">
                    <rect width="20" height="20" fill="white"></rect>
                  </clipPath>
                </defs>
              </svg>
            </div>
            <a href="tel:+7 (495) 785-55-35">+7 999 999 99 99 доб. 198</a>
          </div>
          <div class="visa-contacts-item__value">
            <div class="visa-contacts-item__icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                <path
                  d="M20.1673 11.9167V5.50008C20.1673 5.01385 19.9742 4.54754 19.6303 4.20372C19.2865 3.8599 18.8202 3.66675 18.334 3.66675H3.66732C3.18109 3.66675 2.71477 3.8599 2.37096 4.20372C2.02714 4.54754 1.83398 5.01385 1.83398 5.50008V16.5001C1.83398 17.5084 2.65898 18.3334 3.66732 18.3334H11.0007M20.1673 6.41675L11.9448 11.6417C11.6618 11.8191 11.3346 11.9131 11.0007 11.9131C10.6667 11.9131 10.3395 11.8191 10.0565 11.6417L1.83398 6.41675M14.6673 17.4167L16.5007 19.2501L20.1673 15.5834"
                  stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
              </svg>
            </div>
            <a class="visa-contacts-mail" href="mailto: v.ivanova@bsigroup.ru">v.ivanova@bsigroup.ru</a>
          </div>
        </div>
      </div>


    </div>
  </section>


  <section class="visa-page-consultation__section" id="contact-form">
    <div class="container">
      <h2 class="h2">Бесплатная консультация</h2>
      <p class="visa-consultation-form__descr">Оставьте заявку и проконсультируем вас по вопросам получения виз</p>
      <form id="visa-form" class="visa-consultation-form">

        <div class="form-row form-row-3">
          <div class="education-programs-filter__field">
            <div class="education-programs-filter__label">Страна *</div>
            <select name="country_id" class="visa-form__country-select education-programs-filter__select"
              id="visa-country">
              <option value="">Выберите страну</option>
              <?php if (!empty($countries)): ?>
                <?php foreach ($countries as $country_item): ?>
                  <option value="<?= esc_attr($country_item->ID); ?>">
                    <?= esc_html($country_item->post_title); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <div class="education-programs-filter__field">
            <div class="education-programs-filter__label">Тип визы</div>
            <select name="visa_type" class="visa-form__visa-type-select education-programs-filter__select"
              id="visa-type">
              <option value="">Выберите тип визы</option>
              <option value="tourist">Туристическая</option>
              <option value="educational">Образовательная</option>
            </select>
          </div>

          <div class="input-item white">
            <label for="visa-name">Имя *</label>
            <input type="text" name="name" id="visa-name" placeholder="Введите ваше имя" required>
          </div>

          <div class="input-item white">
            <label for="visa-citizenship">Гражданство *</label>
            <input type="text" name="citizenship" id="visa-citizenship" placeholder="Введите ваше гражданство" required>
          </div>

          <div class="input-item white">
            <label for="visa-phone">Телефон *</label>
            <input type="tel" name="phone" id="visa-phone" placeholder="+7 (___) ___-__-__" required>
          </div>

          <div class="input-item white">
            <label for="visa-travel-dates">Даты поездки *</label>
            <input type="text" name="travel_dates" id="visa-travel-dates" placeholder="Укажите даты поездки" required>
          </div>
        </div>

        <div class="visa-consultation-form__bottom">

          <button type="submit" class="btn btn-accent fit-form__btn-submit">
            Отправить
          </button>

          <p class="form-policy fit-form__policy">
            Нажимая на кнопку "Отправить", вы соглашаетесь с <a href="<?= get_permalink(47) ?>" class="policy-link">
              нашей политикой обработки персональных данных
            </a>
          </p>

          <div id="visa-form-status" class="form-status"></div>

        </div>
      </form>
    </div>
  </section>




</main>

<?php
get_footer();
