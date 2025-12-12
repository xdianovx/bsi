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
  <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&family=Rubik:ital,wght@0,300..900;1,300..900&display=swap"
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
              <div class="currency-item__value"></div>
            </div>

            <div class="currency-item">
              <div class="currency-item__title">EUR</div>
              <div class="currency-item__value"></div>
            </div>
            <div class="currency-select js-dropdown">
              <button class="js-dropdown-trigger currency-select-trigger__wrap">
                <span class="currency-current">RUB</span>
                <img src="<?= get_template_directory_uri() ?>/img/icons/chevron-d.svg"
                     alt="">
              </button>

              <div class="js-dropdown-panel">
                <button class="currency-option"
                        data-iso="RUB">RUB ₽</button>
                <button class="currency-option"
                        data-iso="USD">USD $</button>
                <button class="currency-option"
                        data-iso="EUR">EUR €</button>
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
               class="phone-link">
              <?php the_field('telefon', 'option'); ?>
            </a>

            <a href="tel:<?php the_field('telefon_po_rf', 'option'); ?>"
               class="phone-link">

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
            <a href="https://online.bsigroup.ru/cl_refer"
               class="button-login header__button-login">Старый сайт</a>
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
          </div>

        </div>
      </div>


      <a href="#"
         class="button-login header__button-login --mob">
        <svg width="16"
             height="17"
             viewBox="0 0 16 17"
             fill="#EE3145"
             xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd"
                clip-rule="evenodd"
                d="M8.00043 10.3303C6.45389 10.3294 4.94413 10.8019 3.67393 11.6841C2.40373 12.5664 1.4339 13.8162 0.894714 15.2657C0.702715 15.7783 1.11243 16.2857 1.65929 16.2857H14.3381C14.8867 16.2857 15.2947 15.7783 15.1044 15.2657C14.5652 13.8162 13.5954 12.5664 12.3252 11.6841C11.055 10.8019 9.54696 10.3294 8.00043 10.3303ZM11.7856 4.6474C11.7856 5.65242 11.3863 6.61627 10.6757 7.32693C9.96501 8.03759 9.00116 8.43683 7.99614 8.43683C6.99112 8.43683 6.02727 8.03759 5.31661 7.32693C4.60596 6.61627 4.20671 5.65242 4.20671 4.6474C4.20671 3.64238 4.60596 2.67852 5.31661 1.96787C6.02727 1.25721 6.99112 0.857971 7.99614 0.857971C9.00116 0.857971 9.96501 1.25721 10.6757 1.96787C11.3863 2.67852 11.7856 3.64238 11.7856 4.6474Z">
          </path>
        </svg>
      </a>

      <div class="burger">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
    </div>
  </header>

  <?= get_template_part('template-parts/gtm-search') ?>