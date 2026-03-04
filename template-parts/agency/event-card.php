<?php
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$title = get_the_title($post_id);
$content_raw = trim((string) get_post_field('post_content', $post_id));

$start_date = function_exists('get_field') ? trim((string) get_field('event_start_date', $post_id)) : '';
$start_time = function_exists('get_field') ? trim((string) get_field('event_start_time', $post_id)) : '';
$place = function_exists('get_field') ? trim((string) get_field('event_place', $post_id)) : '';
$registration_closed = function_exists('get_field') ? (bool) get_field('event_registration_closed', $post_id) : false;
$price_raw = function_exists('get_field') ? trim((string) get_field('event_price', $post_id)) : '';
$price = function_exists('format_price_with_from') ? format_price_with_from($price_raw, false) : $price_raw;

$start_date_label = '';
if ($start_date !== '') {
  $ts = strtotime($start_date);
  if ($ts) {
    $start_date_label = date_i18n('j F Y', $ts);
  }
}

$kind_terms = get_the_terms($post_id, 'agency_event_kind');
$kind = (!empty($kind_terms) && !is_wp_error($kind_terms)) ? $kind_terms[0] : null;
$kind_label = $kind ? $kind->name : 'Событие';
$kind_slug = $kind ? $kind->slug : '';
$kind_class = 'is-default';
if ('webinar' === $kind_slug) {
  $kind_class = 'is-webinar';
} elseif ('event' === $kind_slug) {
  $kind_class = 'is-event';
} elseif ('promo-tour' === $kind_slug) {
  $kind_class = 'is-promo';
}
?>

<article class="agency-education-card">
  <div class="agency-education-card__top">
    <span class="agency-education-card__kind <?php echo esc_attr($kind_class); ?>"><?php echo esc_html($kind_label); ?></span>
    <h3 class="agency-education-card__title"><?php echo esc_html($title); ?></h3>
  </div>

  <div class="agency-education-card__meta">
    <?php if ($start_date_label !== ''): ?>
      <span class="agency-education-card__meta-item"><?php echo esc_html($start_date_label); ?></span>
    <?php endif; ?>
    <?php if ($start_time !== ''): ?>
      <span class="agency-education-card__meta-item"><?php echo esc_html($start_time); ?></span>
    <?php endif; ?>
    <?php if ($place !== ''): ?>
      <span class="agency-education-card__meta-item"><?php echo esc_html($place); ?></span>
    <?php endif; ?>
  </div>

  <div class="agency-education-card__bottom">
    <?php if ($price !== ''): ?>
      <div class="agency-education-card__price numfont"><?php echo esc_html($price); ?></div>
    <?php endif; ?>
    <button type="button"
            class="btn sm <?php echo $registration_closed ? 'btn-gray' : 'btn-accent'; ?> agency-education-card__cta"
      <?php echo $registration_closed ? 'disabled' : ''; ?>>
      <?php echo $registration_closed ? 'Запись недоступна' : 'Регистрация'; ?>
    </button>
  </div>

  <?php if ($content_raw !== ''): ?>
    <details class="agency-education-card__details">
      <summary>Описание</summary>
      <div class="editor-content agency-education-card__details-content">
        <?php echo wp_kses_post(wpautop($content_raw)); ?>
      </div>
    </details>
  <?php endif; ?>
</article>
