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
        <div class="gde-kupit-table-wrapper">
          <table class="gde-kupit-table">
            <thead>
              <tr>
                <th>Город</th>
                <th>Турагентство</th>
                <th>Телефон</th>
                <th>Электронная почта</th>
                <th>Адрес</th>
              </tr>
            </thead>
            <tbody>
              <?php while (have_rows('travel_agencies')):
                the_row();
                $city = get_sub_field('city');
                $agency_name = get_sub_field('agency_name');
                $phone = get_sub_field('phone');
                $email = get_sub_field('email');
                $address = get_sub_field('address');
                ?>
                <tr>
                  <td><?php echo esc_html($city); ?></td>
                  <td><?php echo esc_html($agency_name); ?></td>
                  <td>
                    <?php if ($phone): ?>
                      <?php
                      $phone_clean = preg_replace('/[^0-9\+]/', '', $phone);
                      ?>
                      <a href="<?php echo esc_url('tel:' . $phone_clean); ?>" class="gde-kupit-table__link">
                        <?php echo esc_html($phone); ?>
                      </a>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($email): ?>
                      <a href="<?php echo esc_url('mailto:' . $email); ?>" class="gde-kupit-table__link">
                        <?php echo esc_html($email); ?>
                      </a>
                    <?php endif; ?>
                  </td>
                  <td><?php echo esc_html($address); ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
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
