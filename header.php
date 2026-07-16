<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package bsi
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
  <meta name="yandex-verification"
        content="e8e2ebd18d4f8bdf" />
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport"
        content="width=device-width, initial-scale=1">
  <link rel="profile"
        href="https://gmpg.org/xfn/11">





  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
  <header class="header">
    <div class="header-top__wrap">
      <div class="container">
        <div class="header__top">
          <div class="header__currencies ">

            <div class="currency-item">
              <div class="currency-item__title">USD</div>
              <div class="currency-item__value numfont"></div>
            </div>

            <div class="currency-item">
              <div class="currency-item__title">EUR</div>
              <div class="currency-item__value numfont"></div>
            </div>

            <div class="currency-select js-dropdown">
              <button class="js-dropdown-trigger currency-select-trigger__wrap">
                <span class="currency-current">RUB</span>
                <img src="<?= get_template_directory_uri() ?>/img/icons/chevron-d.svg"
                     alt="">
              </button>

              <div class="js-dropdown-panel">
                <button type="button"
                        class="currency-option currency-history-trigger"
                        data-micromodal-trigger="modal-currency-history">История</button>
              </div>

            </div>

            <div class="currency-rates">
              <span class="currency-rate currency-rate--usd"></span>
              <span class="currency-rate currency-rate--eur"></span>
              <span class="currency-rate currency-rate--rub"></span>
            </div>
          </div>

          <div class="header__contacts">

            <a href="tel:<?php the_field('telefon', 'option'); ?>"
               class="phone-link numfont">
              <?php the_field('telefon', 'option'); ?>
              <span>Поддержка 24/7</span>
            </a>

            <a href="tel:<?php the_field('telefon_po_rf', 'option'); ?>"
               class="phone-link numfont">

              <?php the_field('telefon_po_rf', 'option'); ?>
              <span>Бесплатно из регионов</span>
            </a>

            <?= get_template_part('template-parts/ui/socials') ?>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="header__wrap">
        <?= get_custom_logo() ?>
        <div class="header__div"></div>

        <div class="header__right">
          <!--  -->



          <?php wp_nav_menu([
            'theme_location' => 'header_nav',
            'container' => 'nav',
            'container_class' => 'header__nav',
            'menu_class' => 'header__list',
            'depth' => 3,
            'walker' => new BSI_Mega_Menu_Walker(),
          ]) ?>



          <div class="header-old-btns">
            <a href="https://past.bsigroup.ru/"
               target="_blank"
               class="button-login header__button-login button-old-site">Старый сайт</a>
            <a href="https://online.bsigroup.ru/cl_refer"
               target="_blank"
               class="button-login header__button-login">

              <svg xmlns="http://www.w3.org/2000/svg"
                   width="20"
                   height="20"
                   viewBox="0 0 24 24"
                   fill="none"
                   stroke="currentColor"
                   stroke-width="1.5"
                   stroke-linecap="round"
                   stroke-linejoin="round"
                   class="lucide lucide-circle-user-round-icon lucide-circle-user-round">
                <path d="M18 20a6 6 0 0 0-12 0" />
                <circle cx="12"
                        cy="10"
                        r="4" />
                <circle cx="12"
                        cy="12"
                        r="10" />
              </svg>
              <span>Личный кабинет</span>
            </a>

            <div class="burger">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>

        </div>
      </div>





    </div>
  </header>


  <section class="mobile-nav">
    <div class="container">
      <div class="mobile-nav__wrap">
        <div class="mobile-nav__nav">
          <?php
          wp_nav_menu([
            'theme_location' => 'header_nav',
            'container' => false,
            'items_wrap' => '%3$s',
            'depth' => 3,
            'walker' => new Mobile_Nav_Walker(),
            'fallback_cb' => '__return_empty_string',
          ]);
          ?>

        </div>

        <div class="mobile-nav__old-site">
          <a href="https://past.bsigroup.ru/"
             target="_blank"
             rel="noopener noreferrer"
             class="button-login mobile-nav__old-site-link">Старый сайт</a>
        </div>

        <div class="mobile-nav__currencies">
          <div class="currency-item">
            <div class="currency-item__title">USD</div>
            <div class="currency-item__value numfont"></div>
          </div>

          <div class="currency-item">
            <div class="currency-item__title">EUR</div>
            <div class="currency-item__value numfont"></div>
          </div>

          <div class="currency-select js-dropdown">
            <button class="js-dropdown-trigger currency-select-trigger__wrap">
              <span class="currency-current">RUB</span>
              <img src="<?= get_template_directory_uri() ?>/img/icons/chevron-d.svg"
                   alt="">
            </button>

            <div class="js-dropdown-panel">
              <button type="button"
                      class="currency-option currency-history-trigger"
                      data-micromodal-trigger="modal-currency-history">История</button>
            </div>

          </div>

          <div class="currency-rates">
            <span class="currency-rate currency-rate--usd"></span>
            <span class="currency-rate currency-rate--eur"></span>
            <span class="currency-rate currency-rate--rub"></span>
          </div>
        </div>

        <div class="mobile-nav-contacts">
          <div class="mobile-nav-contacts__item">
            <img src="<?= get_template_directory_uri() ?>/img/icons/phone-call.svg"
                 alt="">
            <a href="tel:<?php the_field('telefon', 'option'); ?>"
               class="mobile-nav-contacts__phone">
              <?php the_field('telefon', 'option'); ?>
              <span>Поддержка 24/7</span>
            </a>
          </div>
          <div class="mobile-nav-contacts__item">
            <img src="<?= get_template_directory_uri() ?>/img/icons/phone-call.svg"
                 alt="">
            <a href="tel:<?php the_field('telefon_po_rf', 'option'); ?>"
               class="mobile-nav-contacts__phone">
              <?php the_field('telefon_po_rf', 'option'); ?>
              <span>Бесплатно из регионов</span>
            </a>
          </div>

          <div class="mobile-nav-socials">
            <?= get_template_part('template-parts/ui/socials') ?>

          </div>
        </div>

        <div class="mobile-nav-bottom">
          <!-- asds -->
        </div>
      </div>
    </div>
  </section>

  <div class="modal micromodal-slide"
       id="modal-currency-history"
       aria-hidden="true">
    <div class="modal__overlay"
         tabindex="-1"
         data-micromodal-close>
      <div class="modal__container"
           role="dialog"
           aria-modal="true"
           aria-labelledby="modal-currency-history-title">
        <div class="modal__close-btn"
             data-micromodal-close>
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

        <div class="modal__title"
             id="modal-currency-history-title">История курсов</div>
        <div class="currency-history-modal">
          <label for="currency-history-date">Выберите дату</label>
          <input type="text"
                 id="currency-history-date"
                 class="currency-history-modal__date">
          <div class="currency-history-modal__body"
               data-currency-history-body>Выберите дату для загрузки курсов.</div>
        </div>
      </div>
    </div>
  </div>

  <?= get_template_part('template-parts/gtm-search') ?>