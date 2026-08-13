<?php
/**
 * Single template for agency events (CPT agency_event).
 */

get_header();

$post_id = get_the_ID();
$title = get_the_title($post_id);

$start_date = function_exists('get_field') ? trim((string) get_field('event_start_date', $post_id)) : '';
$start_time = function_exists('get_field') ? trim((string) get_field('event_start_time', $post_id)) : '';
$city = function_exists('get_field') ? trim((string) get_field('event_city', $post_id)) : '';
$place = function_exists('get_field') ? trim((string) get_field('event_place', $post_id)) : '';
$place_display = implode(', ', array_filter([$city, $place]));
$registration_closed = function_exists('get_field') ? (bool) get_field('event_registration_closed', $post_id) : false;
$registration_url = function_exists('get_field') ? trim((string) get_field('event_registration_url', $post_id)) : '';
$price_raw = function_exists('get_field') ? trim((string) get_field('event_price', $post_id)) : '';
$price = $price_raw;
if ($price !== '' && function_exists('format_price_text')) {
  $price = format_price_text($price);
}
// Знак рубля не подставляем: менеджер пишет цену как есть («Бесплатно», «5000 руб», «€300»).

$start_date_label = '';
if ($start_date !== '') {
  $ts = strtotime($start_date);
  if ($ts) {
    $start_date_label = date_i18n('j F Y', $ts);
  }
}

$event_start_ts = (int) get_post_meta($post_id, 'event_start_ts', true);
if (!$event_start_ts && $start_date !== '' && $start_time !== '') {
  $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $start_date . ' ' . $start_time, wp_timezone());
  $event_start_ts = $dt ? $dt->getTimestamp() : 0;
}
$now_ts = (int) current_time('timestamp');
$is_past = ($event_start_ts > 0 && $event_start_ts < $now_ts);
$is_registration_closed = ($registration_closed || $is_past);

$kind_terms = get_the_terms($post_id, 'agency_event_kind');
$kind = (!empty($kind_terms) && !is_wp_error($kind_terms)) ? $kind_terms[0] : null;
$kind_label = $kind ? $kind->name : 'Событие';
$kind_slug = $kind ? $kind->slug : '';

$kind_class = bsi_agency_event_kind_class($kind_slug);

$kind_breadcrumb_label = $kind_slug
  ? bsi_agency_event_kind_plural($kind_slug, $kind_label)
  : 'Мероприятия';

$kind_url = bsi_agency_events_tab_url($kind_slug);
?>

<main class="site-main agency-page">
  <div id="breadcrumbs" class="breadcrumbs">
    <div class="container">
      <p>
        <a href="<?php echo esc_url(home_url('/')); ?>">Главная</a>
        <span class="breadcrumb-separator"></span>
        <a href="<?php echo esc_url($kind_url); ?>"><?php echo esc_html($kind_breadcrumb_label); ?></a>
        <span class="breadcrumb-separator"></span>
        <span><?php echo esc_html($title); ?></span>
      </p>
    </div>
  </div>

  <section class="agency-page__section">
    <div class="container">
      <div class="agency-page__layout">
        <aside class="agency-page__aside">
          <?php get_template_part('template-parts/pages/agency/sidebar'); ?>
        </aside>

        <div class="agency-page__content">
          <div class="agency-education-card__top">
            <span
              class="agency-education-card__kind <?php echo esc_attr($kind_class); ?>"><?php echo esc_html($kind_label); ?></span>
          </div>

          <h1 class="h1 agency-page__title"><?php echo esc_html($title); ?></h1>

          <?php
          $meta_args = [
            'date_label' => $start_date_label,
            'time' => $start_time,
            'place' => $place_display,
          ];
          ?>

          <?php get_template_part('template-parts/agency/event-meta', null, $meta_args); ?>

          <?php
          $cta_args = [
            'post_id' => $post_id,
            'title' => $title,
            'kind_label' => $kind_label,
            'price' => $price,
            'registration_url' => $registration_url,
            'registration_closed' => $is_registration_closed,
          ];
          ?>

          <?php get_template_part('template-parts/agency/event-cta', null, $cta_args); ?>

          <?php if (trim((string) get_post_field('post_content', $post_id)) !== ''): ?>
            <div class="editor-content agency-page__editor" style="margin-top: 24px;">
              <?php the_content(); ?>
            </div>

            <?php // Мета и CTA повторяются под описанием — чтобы не листать обратно наверх. ?>
            <div class="agency-event-single__footer">
              <?php get_template_part('template-parts/agency/event-meta', null, $meta_args); ?>
              <?php get_template_part('template-parts/agency/event-cta', null, $cta_args); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php get_template_part('template-parts/sections/subscribe'); ?>
</main>

<?php get_template_part('template-parts/agency/event-registration-modal'); ?>
<?php get_footer();
