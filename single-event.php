<?php
/**
 * Single Event Template
 */
$post_id = get_the_ID();

$country_id = function_exists('get_field') ? get_field('tour_country', $post_id) : 0;
if ($country_id instanceof WP_Post)
  $country_id = $country_id->ID;
if (is_array($country_id))
  $country_id = (int) reset($country_id);
$country_id = (int) $country_id;

$country_title = '';
$country_permalink = '';
$country_flag = '';
$include_terms = get_the_terms(get_the_ID(), 'tour_include');
$tour_booking_url = trim((string) (function_exists('get_field') ? get_field('tour_booking_url', get_the_ID()) : ''));

$is_old_site_url = false;
if (!empty($tour_booking_url)) {
  $parsed_url = parse_url($tour_booking_url);
  $host = $parsed_url['host'] ?? '';
  if (!empty($host) && stripos($host, 'past.') === 0) {
    $is_old_site_url = true;
  }
}

if ($country_id) {
  $country_title = get_the_title($country_id);
  $country_permalink = get_permalink($country_id);

  $flag = function_exists('get_field') ? get_field('flag', $country_id) : '';
  $country_flag = (is_array($flag) && !empty($flag['url'])) ? $flag['url'] : (string) $flag;
}

$region_terms = get_the_terms($post_id, 'region');
$region_term = (!empty($region_terms) && !is_wp_error($region_terms)) ? $region_terms[0] : null;

$resort_terms = get_the_terms($post_id, 'resort');
$resort_term = (!empty($resort_terms) && !is_wp_error($resort_terms)) ? $resort_terms[0] : null;

$tour_gallery = function_exists('get_field') ? get_field('tour_gallery', $post_id) : [];
$tour_duration = function_exists('get_field') ? trim((string) get_field('tour_duration', $post_id)) : '';
$tour_route = function_exists('get_field') ? trim((string) get_field('tour_route', $post_id)) : '';
$tour_checkin_dates = function_exists('get_field') ? trim((string) get_field('tour_checkin_dates', $post_id)) : '';
$tour_price_from = function_exists('get_field') ? trim((string) get_field('price_from', $post_id)) : '';
$tour_program = function_exists('get_field') ? get_field('tour_program', $post_id) : [];
$tour_included = function_exists('get_field') ? (string) get_field('tour_included', $post_id) : '';
$tour_not_inc = function_exists('get_field') ? (string) get_field('tour_not_included', $post_id) : '';
$tour_extra = function_exists('get_field') ? (string) get_field('tour_extra', $post_id) : '';
$event_tickets = function_exists('get_field') ? get_field('event_tickets', $post_id) : [];
$event_venue = function_exists('get_field') ? trim((string) get_field('event_venue', $post_id)) : '';
$event_time = function_exists('get_field') ? trim((string) get_field('event_time', $post_id)) : '';

// Вычисляем минимальную цену из билетов
$min_ticket_price = null;
if (!empty($event_tickets) && is_array($event_tickets)) {
  foreach ($event_tickets as $ticket) {
    if (!empty($ticket['ticket_price'])) {
      $price = (int) $ticket['ticket_price'];
      if ($min_ticket_price === null || $price < $min_ticket_price) {
        $min_ticket_price = $price;
      }
    }
  }
}

$excerpt = get_the_excerpt($post_id);

get_header();
?>

<main>

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div id="breadcrumbs" class="breadcrumbs"><div class="container"><p>',
      '</p></div></div>'
    );
  }
  ?>

  <section class="">
    <div class="container">
      <div class="single-hotel__title-wrap">
        <div class="title-rating__wrap">
          <h1 class="h1 single-hotel__title"><?php the_title(); ?></h1>
        </div>
      </div>
    </div>
  </section>

  <div class="event-poster-section">
    <div class="container">
      <?php
      // Получить миниатюру события (thumbnail)
      if (has_post_thumbnail($post_id)):
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'large');
        ?>
        <div class="single-event__thumbnail">
          <img src="<?= esc_url($thumbnail_url); ?>" alt="<?= esc_attr(get_the_title($post_id)); ?>" loading="lazy">
        </div>
      <?php endif; ?>
    </div>
  </div>





  <section class="single-tour__content">
    <div class="container">
      <div class="single-hotel__content__wrap">


        <div class="hotel-content">
          <!-- <?php if ($event_venue || $event_time): ?>
            <div class="event-aside-details">
              <?php if ($event_venue): ?>
                <div class="event-aside-detail">
                  <div class="event-aside-detail__label">Место проведения:</div>
                  <div class="event-aside-detail__value"><?= esc_html($event_venue); ?></div>
                </div>
              <?php endif; ?>
              <?php if ($event_time): ?>
                <div class="event-aside-detail">
                  <div class="event-aside-detail__label">Время проведения:</div>
                  <div class="event-aside-detail__value numfont"><?= esc_html($event_time); ?></div>
                </div>
              <?php endif; ?>
            </div> -->
          <?php endif; ?>





          <div class="single-tour-content editor-content">
            <?php the_content() ?>
          </div>

          <?php if (!empty($event_tickets) && is_array($event_tickets)): ?>
            <div class="event-tickets">
              <h2 class="h2">Билеты</h2>
              <div class="event-tickets__list">
                <?php foreach ($event_tickets as $ticket): ?>
                  <?php
                  $ticket_type = !empty($ticket['ticket_type']) ? $ticket['ticket_type'] : '';
                  $ticket_price = !empty($ticket['ticket_price']) ? (int) $ticket['ticket_price'] : 0;
                  $ticket_desc = !empty($ticket['ticket_description']) ? $ticket['ticket_description'] : '';
                  ?>
                  <div class="event-ticket">
                    <div class="event-ticket__main">
                      <div class="event-ticket__type"><?= esc_html($ticket_type); ?></div>
                      <?php if ($ticket_desc): ?>
                        <div class="event-ticket__desc"><?= nl2br(esc_html($ticket_desc)); ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="event-ticket__sidebar">
                      <div class="event-ticket__price">
                        <span class="event-ticket__price-value numfont"><?= number_format($ticket_price, 0, ',', ' '); ?>
                          ₽</span>
                      </div>
                      <button type="button" class="event-ticket__btn js-event-ticket-booking-btn"
                        data-ticket-type="<?= esc_attr($ticket_type); ?>"
                        data-ticket-price="<?= esc_attr($ticket_price); ?>"
                        data-ticket-desc="<?= esc_attr($ticket_desc); ?>"
                        data-event-title="<?= esc_attr(get_the_title()); ?>"
                        data-event-venue="<?= esc_attr($event_venue); ?>" data-event-time="<?= esc_attr($event_time); ?>">
                        Купить
                      </button>
                    </div>
                    <div class="event-ticket__perforation"></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_program) && is_array($tour_program)): ?>
            <section class="accordion tour-program">
              <div class="tour-program__acc-head accordion__head">
                <h2 class="h2 tour-program__title">Программа тура</h2>
                <button class="btn-expand  accordion__toggle-all" type="button">Раскрыть все</button>
              </div>

              <div class="accordion__list tour-program__list">
                <?php foreach ($tour_program as $i => $day): ?>
                  <?php
                  $day_title = !empty($day['day_title']) ? (string) $day['day_title'] : '';
                  $day_text = !empty($day['day_content']) ? (string) $day['day_content'] : '';
                  if (!$day_title && !$day_text)
                    continue;
                  $is_open = false;
                  ?>
                  <div class="accordion__item tour-program__day <?= $is_open ? 'is-open' : ''; ?>">
                    <button class="accordion__btn tour-program__day-btn" type="button">
                      <span class="accordion__title">
                        <?= esc_html($day_title ?: ('День ' . ($i + 1))); ?>
                      </span>
                      <span class="accordion__icon" aria-hidden="true">
                        <img src="<?= get_template_directory_uri() ?>/img/icons/chevron-d.svg" alt="">
                      </span>
                    </button>

                    <div class="accordion__panel">
                      <div class="accordion__content">
                        <?php if ($day_text): ?>
                          <div class="editor-content">
                            <?= wp_kses_post($day_text); ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>


        </div>

        <!-- Виджеты -->
        <aside class="hotel-aside">

          <div class="hotel-widget">
            <?php if ($country_title || $region_term || $resort_term): ?>
              <?php
              $items = [];

              if ($country_title) {
                $items[] = $country_permalink
                  ? '<a class="single-hotel__address-link" href="' . esc_url($country_permalink) . '">' . esc_html($country_title) . '</a>'
                  : '<span>' . esc_html($country_title) . '</span>';
              }

              if ($region_term) {
                $region_link = get_term_link($region_term);
                $items[] = !is_wp_error($region_link)
                  ? '<a class="single-hotel__address-link" href="' . esc_url($region_link) . '">' . esc_html($region_term->name) . '</a>'
                  : '<span>' . esc_html($region_term->name) . '</span>';
              }

              if ($resort_term) {
                $resort_link = get_term_link($resort_term);
                $items[] = !is_wp_error($resort_link)
                  ? '<a class="single-hotel__address-link" href="' . esc_url($resort_link) . '">' . esc_html($resort_term->name) . '</a>'
                  : '<span>' . esc_html($resort_term->name) . '</span>';
              }
              ?>

              <div class="single-hotel__top-line">
                <div class="single-hotel__address">
                  <?php if (!empty($country_flag)): ?>
                    <img src="<?= esc_url($country_flag); ?>" alt="">
                  <?php endif; ?>

                  <div class="single-hotel__address-text">
                    <?= implode(', ', $items); ?>
                  </div>
                </div>
              </div>

              <div class="aside-contact-item">
                <a class="aside-contact-item__link numfont" href="tel:84957855535">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-phone-call">
                    <path d="M13 2a9 9 0 0 1 9 9"></path>
                    <path d="M13 6a5 5 0 0 1 5 5"></path>
                    <path
                      d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384">
                    </path>
                  </svg>
                  <span>8 (495) 785-55-35</span>
                </a>
                <a class="aside-contact-item__link numfont" href="tel:88002005535">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-phone-call">
                    <path d="M13 2a9 9 0 0 1 9 9"></path>
                    <path d="M13 6a5 5 0 0 1 5 5"></path>
                    <path
                      d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384">
                    </path>
                  </svg>
                  <span>8 (800) 200-55-35 (из регионов)</span>
                </a>
              </div>
            <?php endif; ?>



            <?php if (!empty($include_terms) && !is_wp_error($include_terms)): ?>
              <div class="sigle-tour-include tour-card-row__included">
                <?php foreach ($include_terms as $t):
                  $icon = function_exists('get_field') ? get_field('tour_include_icon', 'term_' . $t->term_id) : null;
                  $icon_url = (is_array($icon) && !empty($icon['url'])) ? $icon['url'] : '';
                  ?>
                  <span class="tour-include__item tour-tag white">
                    <?php if ($icon_url): ?>
                      <img class="tour-include__icon" src="<?= esc_url($icon_url); ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <span class="tour-include__text"><?= esc_html($t->name); ?></span>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>



            <div class="hotel-widget__price numfont">
              <?php if ($min_ticket_price !== null): ?>
                от <?= number_format($min_ticket_price, 0, ',', ' '); ?> ₽
              <?php elseif ($tour_price_from): ?>
                <?= esc_html($tour_price_from); ?>
              <?php else: ?>
                Запросить
              <?php endif; ?>
            </div>



            <?php if ($tour_booking_url): ?>
              <a href="<?= esc_url($tour_booking_url); ?>" class="btn btn-accent hotel-widget__btn-book sm"
                target="_blank" rel="nofollow noopener">
                Забронировать
              </a>
            <?php else: ?>
              <button type="button" class="btn btn-accent hotel-widget__btn-book sm js-event-booking-btn"
                data-event-id="<?= esc_attr($post_id); ?>" data-event-title="<?= esc_attr(get_the_title()); ?>"
                data-event-venue="<?= esc_attr($event_venue); ?>" data-event-time="<?= esc_attr($event_time); ?>"
                data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">
                Забронировать
              </button>
            <?php endif; ?>

          </div>


        </aside>

      </div>
    </div>
  </section>

  <?php if (!empty($tour_included) || !empty($tour_not_inc)): ?>
    <section class="single-education__price-details-section">
      <div class="container">
        <div class="single-education__price-details">
          <?php if (!empty($tour_included)): ?>
            <div class="single-education__price-included">
              <h3 class="single-education__price-title">В стоимость входит</h3>
              <div class="single-education__price-content">
                <?= wp_kses_post($tour_included); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_not_inc)): ?>
            <div class="single-education__price-extra">
              <h3 class="single-education__price-title">Оплачивается дополнительно</h3>
              <div class="single-education__price-content">
                <?= wp_kses_post($tour_not_inc); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

</main>

<?php
// Модальное окно бронирования билета
get_template_part('template-parts/event/ticket-booking-modal');

get_footer(); ?>