<?php
/*
Template Name: Где купить
*/

get_header();
?>

<main class="site-main">

  <?php if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb('<div class="breadcrumbs container"><p>', '</p></div>');
  } ?>

  <section class="archive-page-head">
    <div class="container">
      <div class="archive-page__top">
        <h1 class="h1 archive-page__title">
          <?php the_title(); ?>
        </h1>
      </div>
    </div>
  </section>

  <section class="gde-kupit-page__content-section">
    <div class="container">
      <div class="gde-kupit-intro">
        <p class="gde-kupit-intro__text">
          Вы можете забронировать тур в онлайн-системе бронирования на нашем сайте<br>
          или обратиться в уполномоченное туристическое агентство
        </p>
      </div>

      <?php if (function_exists('have_rows') && have_rows('travel_agencies')): ?>
        <div class="gde-kupit-agencies__wrap">
          <?php while (have_rows('travel_agencies')):
            the_row();
            $city = get_sub_field('city');
            $agency_name = get_sub_field('agency_name');
            $phone = get_sub_field('phone');
            $email = get_sub_field('email');
            $address = get_sub_field('address');

            $phone_clean = $phone ? preg_replace('/[^0-9\+]/', '', $phone) : '';
            ?>
            <div class="gde-kupit-agency-item">
              <div class="gde-kupit-agency-item__inner">
                <?php if ($city): ?>
                  <div class="gde-kupit-agency-item__city"><?php echo esc_html($city); ?></div>
                <?php endif; ?>

                <?php if ($agency_name): ?>
                  <div class="gde-kupit-agency-item__name"><?php echo esc_html($agency_name); ?></div>
                <?php endif; ?>

                <?php if ($address): ?>
                  <div class="gde-kupit-agency-item__address">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                      class="lucide lucide-map-pin">
                      <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                      <circle cx="12" cy="10" r="3" />
                    </svg>
                    <span><?php echo esc_html($address); ?></span>
                  </div>
                <?php endif; ?>

                <div class="gde-kupit-agency-item__contacts">
                  <?php if ($phone): ?>
                    <a class="gde-kupit-agency-item__phone gde-kupit-agency-item__link numfont"
                      href="<?php echo esc_url('tel:' . $phone_clean); ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-phone-call">
                        <path d="M13 2a9 9 0 0 1 9 9" />
                        <path d="M13 6a5 5 0 0 1 5 5" />
                        <path
                          d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />
                      </svg>
                      <span><?php echo esc_html($phone); ?></span>
                    </a>
                  <?php endif; ?>

                  <?php if ($email): ?>
                    <a class="gde-kupit-agency-item__email gde-kupit-agency-item__link numfont"
                      href="<?php echo esc_url('mailto:' . $email); ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-mail">
                        <rect width="20" height="16" x="2" y="4" rx="2" />
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                      </svg>
                      <span><?php echo esc_html($email); ?></span>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="gde-kupit-empty">
          <p>Список турагентств будет добавлен позже.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

</main>

<?php
get_footer();
