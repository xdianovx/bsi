<?php
/**
 * Single promo template.
 * List views (country/promo/) are handled by template_include → country-promo.php.
 */

get_header();

$promo_id = get_the_ID();
$promo_booking_url = function_exists('get_field') ? trim((string) get_field('promo_booking_url', $promo_id)) : '';
$promo_booking_cta_lead = function_exists('get_field')
  ? trim((string) get_field('promo_booking_cta_lead', $promo_id))
  : '';
if ($promo_booking_cta_lead === '' && defined('BSI_PROMO_BOOKING_CTA_LEAD_DEFAULT')) {
  $promo_booking_cta_lead = BSI_PROMO_BOOKING_CTA_LEAD_DEFAULT;
}
$promo_title = get_the_title($promo_id);
$raw_from = function_exists('get_field') ? get_field('promo_date_from', $promo_id) : null;
$raw_to = function_exists('get_field') ? get_field('promo_date_to', $promo_id) : null;
$formatted_from = function_exists('format_date_value') ? format_date_value($raw_from) : $raw_from;
$formatted_to = function_exists('format_date_value') ? format_date_value($raw_to) : $raw_to;

$promo_countdown_text = (function_exists('bsi_promo_countdown_public_message') && function_exists('bsi_promo_calendar_days_until_end_from_raw'))
  ? bsi_promo_countdown_public_message(bsi_promo_calendar_days_until_end_from_raw($raw_to))
  : '';

$promo_dates_label = '';
$promo_dates_value = '';
if ($formatted_from && $formatted_to) {
  $promo_dates_label = 'Период действия акции';
  $promo_dates_value = $formatted_from . ' – ' . $formatted_to;
} elseif ($formatted_to && !$formatted_from) {
  $promo_dates_label = 'Действует до';
  $promo_dates_value = (string) $formatted_to;
} elseif ($formatted_from && !$formatted_to) {
  $promo_dates_label = 'Действует с';
  $promo_dates_value = (string) $formatted_from;
}

$promo_is_expired = function_exists('bsi_promo_is_past_promo_end_date') && bsi_promo_is_past_promo_end_date((int) $promo_id);

?>

<main class="site-main">

  <?php
  if (function_exists('yoast_breadcrumb')) {
    yoast_breadcrumb(
      '<div class="breadcrumbs container"><p>',
      '</p></div>'
    );
  }
  ?>

  <section class="single-promo__head">
    <div class="container">


      <?php if (has_post_thumbnail()): ?>
        <div class="single-promo__poster">
          <?php the_post_thumbnail('large', ['class' => 'single-promo__poster-img']); ?>
        </div>
      <?php endif; ?>


    </div>
  </section>

  <section class="post-content-section single-promo__content">
    <div class="container">
      <h1 class="h1 single-promo__title">
        <?php the_title(); ?>
      </h1>

      <?php if ($promo_dates_value !== ''): ?>
        <div class="single-promo__dates">
          <div class="single-promo__dates-head">
            <span class="single-promo__dates-label"><?php echo esc_html($promo_dates_label); ?></span>
            <span class="single-promo__dates-value"><?php echo esc_html($promo_dates_value); ?></span>
          </div>
          <?php if ($promo_countdown_text !== '' && !$promo_is_expired): ?>
            <p class="single-promo__dates-countdown"><?php echo esc_html($promo_countdown_text); ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($promo_is_expired): ?>
        <div class="single-promo__ended" role="status">
          <p class="single-promo__ended-lead">Эта акция завершена.</p>
          <p class="single-promo__ended-hint">Подпишитесь на наши соцсети — так вы не пропустите новые предложения.</p>
          <div class="single-promo__ended-socials">
            <?php get_template_part('template-parts/ui/socials'); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (get_the_excerpt()): ?>
        <div class="page-country__descr">
          <?= get_the_excerpt(); ?>
        </div>
      <?php endif; ?>
      <div class="editor-content">
        <?php the_content(); ?>
      </div>
    </div>
  </section>

  <section class="single-event__booking-cta" aria-label="Узнать подробности об акции">
    <div class="container">
      <div class="single-event__booking-cta-inner">
        <div class="single-event__booking-cta-head">
          <h2 class="single-event__booking-cta-title">Узнать подробности</h2>
          <p class="single-event__booking-cta-lead"><?= esc_html($promo_booking_cta_lead); ?></p>
        </div>
        <?php if ($promo_booking_url): ?>
          <div class="single-event__booking-cta-actions">
            <a href="<?= esc_url($promo_booking_url); ?>"
              class="single-event__booking-cta-submit single-event__booking-cta-submit--wide" target="_blank"
              rel="nofollow noopener">Забронировать</a>
          </div>
        <?php else: ?>
          <form id="promo-booking-cta-form" class="single-event__booking-cta-form" novalidate>
            <input type="hidden" name="action" value="event_ticket_booking">
            <input type="hidden" name="event_booking_minimal" value="1">
            <input type="hidden" name="booking_context" value="promo">
            <input type="hidden" name="event_title" value="<?= esc_attr($promo_title); ?>">
            <input type="hidden" name="page_url" value="<?= esc_url(function_exists('bsi_get_promo_public_url') ? bsi_get_promo_public_url((int) $promo_id) : get_permalink($promo_id)); ?>">

            <div class="single-event__booking-cta-row">
              <div class="single-event__booking-cta-field">
                <label class="screen-reader-text" for="promo-booking-cta-name">Имя</label>
                <input id="promo-booking-cta-name" type="text" name="name" class="single-event__booking-cta-input"
                  placeholder="имя" autocomplete="name" required data-field="name">
                <span class="single-event__booking-cta-field-error js-field-error" data-error-for="name"></span>
              </div>
              <div class="single-event__booking-cta-field">
                <label class="screen-reader-text" for="promo-booking-cta-phone">Телефон</label>
                <input id="promo-booking-cta-phone" type="tel" name="phone"
                  class="single-event__booking-cta-input js-phone-mask" placeholder="Телефон" autocomplete="tel" required
                  data-field="phone">
                <span class="single-event__booking-cta-field-error js-field-error" data-error-for="phone"></span>
              </div>
              <button type="submit" class="single-event__booking-cta-submit" data-default-label="Забронировать">
                Забронировать
              </button>
            </div>

            <?php
            if (function_exists('bsi_render_privacy_consent_checkbox')) {
              bsi_render_privacy_consent_checkbox([
                'variant' => 'event-booking-cta',
                'checkbox_id' => 'promo-booking-cta-privacy',
              ]);
            }
            ?>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

</main>

<?php
get_template_part('template-parts/event/ticket-booking-modal');
get_footer();
