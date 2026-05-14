<?php
/**
 * Single Event Template
 */
$post_id = get_the_ID();

$country_id = function_exists('get_field') ? get_field('tour_country', $post_id) : 0;
if ($country_id instanceof WP_Post) {
  $country_id = $country_id->ID;
}
if (is_array($country_id)) {
  $country_id = (int) reset($country_id);
}
$country_id = (int) $country_id;

$tour_booking_url = trim((string) (function_exists('get_field') ? get_field('tour_booking_url', get_the_ID()) : ''));

$region_terms = get_the_terms($post_id, 'region');
$region_term = (!empty($region_terms) && !is_wp_error($region_terms)) ? $region_terms[0] : null;

$resort_terms = get_the_terms($post_id, 'resort');
$resort_term = (!empty($resort_terms) && !is_wp_error($resort_terms)) ? $resort_terms[0] : null;

$tour_duration = function_exists('get_field') ? trim((string) get_field('tour_duration', $post_id)) : '';
$tour_program = function_exists('get_field') ? get_field('tour_program', $post_id) : [];
$tour_included = function_exists('get_field') ? (string) get_field('tour_included', $post_id) : '';
$tour_not_inc = function_exists('get_field') ? (string) get_field('tour_not_included', $post_id) : '';
$tour_extra = function_exists('get_field') ? (string) get_field('tour_extra', $post_id) : '';
$event_tickets = function_exists('get_field') ? get_field('event_tickets', $post_id) : [];
$event_venue = function_exists('get_field') ? trim((string) get_field('event_venue', $post_id)) : '';
$event_time = function_exists('get_field') ? trim((string) get_field('event_time', $post_id)) : '';
$tour_nights = function_exists('get_field') ? (int) get_field('tour_nights', $post_id) : 0;
$tour_transport = function_exists('get_field') ? trim((string) get_field('tour_transport', $post_id)) : '';
$venue_scheme = function_exists('get_field') ? get_field('venue_scheme', $post_id) : null;
$venue_scheme_legend = function_exists('get_field') ? get_field('venue_scheme_legend', $post_id) : [];
$event_faq = function_exists('get_field') ? get_field('event_faq', $post_id) : [];

$venue_scheme_url = '';
$venue_scheme_alt = '';
if (is_array($venue_scheme) && !empty($venue_scheme['url'])) {
  $venue_scheme_url = (string) $venue_scheme['url'];
  $venue_scheme_alt = !empty($venue_scheme['alt']) ? (string) $venue_scheme['alt'] : 'Схема зала';
}

$event_dates_rows = function_exists('get_field') ? get_field('event_dates', $post_id) : [];

$related_events = [];
$rel_terms = get_the_terms($post_id, 'tour_type');
$rel_type_ids = (!empty($rel_terms) && !is_wp_error($rel_terms)) ? wp_list_pluck($rel_terms, 'term_id') : [];
$rel_q = [
  'post_type' => 'event',
  'post_status' => 'publish',
  'posts_per_page' => 6,
  'post__not_in' => [$post_id],
  'orderby' => 'title',
  'order' => 'ASC',
  'no_found_rows' => true,
];
if (!empty($rel_type_ids)) {
  $rel_q['tax_query'] = [
    [
      'taxonomy' => 'tour_type',
      'field' => 'term_id',
      'terms' => $rel_type_ids,
    ],
  ];
} elseif ($country_id) {
  $rel_q['meta_query'] = [
    [
      'key' => 'tour_country',
      'value' => $country_id,
      'compare' => '=',
    ],
  ];
}
$rel_q['suppress_filters'] = false;
$related_events = get_posts($rel_q);
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
$show_quick = $tour_duration || $tour_nights || $resort_term || $tour_transport;

get_header();
?>

<main class="site-main single-event-page">

  <?php get_template_part('template-parts/event/single-hero', null, ['post_id' => $post_id]); ?>

  <section class="single-tour__content single-event__body">
    <div class="container">

      <div class="single-event__about-grid" id="o-sobytii">
        <div class="single-event__about-main">
          <h2 class="h2">О событии</h2>
          <?php if ($excerpt !== ''): ?>
            <p class="single-event__lead"><?= esc_html($excerpt); ?></p>
          <?php endif; ?>

          <?php
          $lucide_attrs = ['width' => '20', 'height' => '20', 'stroke' => 'currentColor'];
          if ($show_quick):
            ?>
            <div class="event-aside-details single-event__quick-facts">
              <?php if ($tour_duration): ?>
                <div class="event-aside-detail">
                  <span class="event-aside-detail__value"><?= esc_html($tour_duration); ?></span>
                </div>
              <?php elseif ($tour_nights): ?>
                <div class="event-aside-detail">
                  <span class="event-aside-detail__icon" aria-hidden="true">
                    <?php echo function_exists('bsi_lucide_icon') ? bsi_lucide_icon('calendar', $lucide_attrs) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </span>
                  <span class="event-aside-detail__value numfont"><?= (int) $tour_nights; ?></span>
                </div>
              <?php endif; ?>

              <?php if ($tour_transport): ?>
                <div class="event-aside-detail">
                  <span class="event-aside-detail__icon" aria-hidden="true">
                    <?php echo function_exists('bsi_lucide_icon') ? bsi_lucide_icon('calendar', $lucide_attrs) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </span>
                  <span class="event-aside-detail__label">Транспорт</span>
                  <span class="event-aside-detail__value"><?= esc_html($tour_transport); ?></span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <aside class="single-event__about-aside" aria-label="Краткая информация и бронирование">
          <?php
          get_template_part('template-parts/event/event-booking-widget', null, [
            'post_id' => $post_id,
            'show_phones' => false,
            'show_include_tags' => false,
            'extra_class' => 'single-event__booking-widget--inline',
          ]);
          ?>
        </aside>
      </div>

      <div class="single-hotel__content__wrap single-event__columns">

        <div class="hotel-content single-event__main-column">

          <div class="single-tour-content editor-content">
            <?php the_content(); ?>
          </div>

          <?php
          get_template_part('template-parts/event/event-dates-table', null, [
            'post_id' => $post_id,
            'event_dates_rows' => is_array($event_dates_rows) ? $event_dates_rows : [],
            'fallback_venue' => $event_venue,
            'event_tickets' => is_array($event_tickets) ? $event_tickets : [],
            'min_ticket_price' => $min_ticket_price,
          ]);
          ?>

          <?php if ($venue_scheme_url): ?>
            <section class="single-event__venue-scheme">
              <h2 class="h2">Схема зала</h2>
              <div class="single-event__venue-layout">
                <figure class="single-event__venue-figure">
                  <img src="<?= esc_url($venue_scheme_url); ?>" alt="<?= esc_attr($venue_scheme_alt); ?>" loading="lazy">
                </figure>
                <?php if (!empty($venue_scheme_legend) && is_array($venue_scheme_legend)): ?>
                  <ul class="single-event__venue-legend">
                    <?php foreach ($venue_scheme_legend as $leg): ?>
                      <?php
                      $lab = isset($leg['legend_label']) ? trim((string) $leg['legend_label']) : '';
                      $pr = isset($leg['legend_price']) ? trim((string) $leg['legend_price']) : '';
                      if ($lab === '' && $pr === '') {
                        continue;
                      }
                      ?>
                      <li class="single-event__venue-legend-item">
                        <span class="single-event__venue-legend-label"><?= esc_html($lab); ?></span>
                        <span class="single-event__venue-legend-price numfont"><?= esc_html($pr); ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </section>
          <?php endif; ?>

          <?php if (!empty(trim(strip_tags($tour_extra)))): ?>
            <section class="single-event__accommodation editor-content">
              <h2 class="h2">Размещение</h2>
              <?= wp_kses_post($tour_extra); ?>
            </section>
          <?php endif; ?>

          <?php if (!empty($event_tickets) && is_array($event_tickets)): ?>
            <div class="event-tickets">
              <h2 class="h2">Типы билетов</h2>
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
                  if (!$day_title && !$day_text) {
                    continue;
                  }
                  $is_open = false;
                  ?>
                  <div class="accordion__item tour-program__day <?= $is_open ? 'is-open' : ''; ?>">
                    <button class="accordion__btn tour-program__day-btn" type="button">
                      <span class="accordion__title">
                        <?= esc_html($day_title ?: ('День ' . ($i + 1))); ?>
                      </span>
                      <span class="accordion__icon" aria-hidden="true">
                        <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
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

        <aside class="hotel-aside hotel-aside--event-sticky single-event__aside" aria-label="Бронирование и контакты">
          <?php
          get_template_part('template-parts/event/event-booking-widget', null, [
            'post_id' => $post_id,
            'show_phones' => true,
            'show_include_tags' => true,
          ]);
          ?>
        </aside>

      </div>

    </div>
  </section>

  <?php if (!empty($tour_included) || !empty($tour_not_inc)): ?>
    <section class="single-education__price-details-section single-event__price-details-section">
      <div class="container">
        <div class="single-education__price-details">
          <?php if (!empty($tour_included)): ?>
            <div class="single-education__price-included single-event__price-col">
              <h3 class="single-education__price-title">В стоимость входит</h3>
              <div class="single-education__price-content">
                <?= wp_kses_post($tour_included); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_not_inc)): ?>
            <div class="single-education__price-extra single-event__price-col">
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

  <section class="single-event__booking-cta" aria-label="Забронировать событие">
    <div class="container single-event__booking-cta-inner">
      <h2 class="single-event__booking-cta-title">Забронировать</h2>
      <p class="single-event__booking-cta-text">
        Оставьте заявку — менеджер уточнит детали и поможет с бронированием.
      </p>
      <?php if ($tour_booking_url): ?>
        <a href="<?= esc_url($tour_booking_url); ?>" class="btn btn-accent single-event__booking-cta-btn"
          target="_blank" rel="nofollow noopener">Забронировать</a>
      <?php else: ?>
        <button type="button" class="btn btn-accent single-event__booking-cta-btn js-event-booking-btn"
          data-event-id="<?= esc_attr($post_id); ?>"
          data-event-title="<?= esc_attr(get_the_title()); ?>"
          data-event-venue="<?= esc_attr($event_venue); ?>"
          data-event-time="<?= esc_attr($event_time); ?>"
          data-min-price="<?= esc_attr($min_ticket_price ?? 0); ?>">Забронировать</button>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!empty($event_faq) && is_array($event_faq)): ?>
    <section class="single-event__faq">
      <div class="container">
        <h2 class="h2">Ответы на часто задаваемые вопросы</h2>
        <div class="accordion single-event__faq-acc">
          <div class="accordion__list single-event__faq-list">
            <?php foreach ($event_faq as $fi => $faq_row): ?>
              <?php
              $fq = isset($faq_row['faq_question']) ? trim((string) $faq_row['faq_question']) : '';
              $fa = isset($faq_row['faq_answer']) ? (string) $faq_row['faq_answer'] : '';
              if ($fq === '') {
                continue;
              }
              ?>
              <div class="accordion__item single-event__faq-item">
                <button class="accordion__btn single-event__faq-btn" type="button">
                  <span class="accordion__title"><?= esc_html($fq); ?></span>
                  <span class="accordion__icon" aria-hidden="true">
                    <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/chevron-d.svg'); ?>" alt="">
                  </span>
                </button>
                <div class="accordion__panel">
                  <div class="accordion__content editor-content">
                    <?= wp_kses_post($fa); ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($related_events)): ?>
    <?php
    get_template_part('template-parts/event/related-slider', null, [
      'posts' => $related_events,
      'title' => 'Похожие событийные туры',
    ]);
    ?>
  <?php endif; ?>

</main>

<?php
get_template_part('template-parts/event/ticket-booking-modal');

get_footer();
