<?php
/**
 * The header for our theme (MICE)
 *
 * @package bsi
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport"
        content="width=device-width, initial-scale=1">
  <link rel="profile"
        href="https://gmpg.org/xfn/11">

  <link rel="preconnect"
        href="https://fonts.googleapis.com">
  <link rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">

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
            </a>

            <a href="tel:<?php the_field('telefon_po_rf', 'option'); ?>"
               class="phone-link numfont">

              <?php the_field('telefon_po_rf', 'option'); ?>
              <span>бесплатно из регионов</span>
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
          <?php wp_nav_menu([
            'theme_location' => 'mice_header_nav',
            'container' => 'nav',
            'container_class' => 'header__nav',
            'menu_class' => 'header__list',
            'depth' => 1, // без выпадашек
            'fallback_cb' => '__return_empty_string',
          ]); ?>


          <div class="header-old-btns">
            <a href="https://www.bsigroup.ru/"
               target="_blank"
               class="button-login header__button-login button-old-site">Старый сайт</a>
            <a href="https://online.bsigroup.ru/cl_refer"
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
            'theme_location' => 'mice_header_nav',
            'container' => 'nav',
            'container_class' => 'header__nav',
            'menu_class' => 'header__list',
            'depth' => 1, // без выпадашек
            'fallback_cb' => '__return_empty_string',
          ]);
          ?>
        </div>

        <div class="mobile-nav-contacts">
          <div class="mobile-nav-contacts__item">
            <img src="<?= get_template_directory_uri() ?>/img/icons/phone-call.svg"
                 alt="">
            <a href="tel:<?php the_field('telefon', 'option'); ?>"
               class="mobile-nav-contacts__phone">
              <?php the_field('telefon', 'option'); ?>
            </a>
          </div>
          <div class="mobile-nav-contacts__item">
            <img src="<?= get_template_directory_uri() ?>/img/icons/phone-call.svg"
                 alt="">
            <a href="tel:<?php the_field('telefon_po_rf', 'option'); ?>"
               class="mobile-nav-contacts__phone">
              <?php the_field('telefon', 'option'); ?>
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