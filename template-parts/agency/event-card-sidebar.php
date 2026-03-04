<?php
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$title = get_the_title($post_id);
$permalink = get_permalink($post_id);
$content_raw = trim((string) get_post_field('post_content', $post_id));
$excerpt = '';
if ($content_raw !== '') {
  $excerpt = wp_trim_words(wp_strip_all_tags($content_raw), 20, '…');
}
$start_date = function_exists('get_field') ? trim((string) get_field('event_start_date', $post_id)) : '';
$start_time = function_exists('get_field') ? trim((string) get_field('event_start_time', $post_id)) : '';
$place = function_exists('get_field') ? trim((string) get_field('event_place', $post_id)) : '';

$start_date_label = '';
if ($start_date !== '') {
  $ts = strtotime($start_date);
  if ($ts) {
    $start_date_label = date_i18n('j F', $ts);
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

<a href="<?php echo esc_url($permalink); ?>" class="agency-sidebar-event">
  <div class="agency-sidebar-event__head">
    <span class="agency-sidebar-event__kind <?php echo esc_attr($kind_class); ?>"><?php echo esc_html($kind_label); ?></span>
    <h4 class="agency-sidebar-event__title"><?php echo esc_html($title); ?></h4>
  </div>

  <div class="agency-sidebar-event__meta">
    <?php if ($start_date_label !== ''): ?>
      <span class="agency-sidebar-event__meta-item"><?php echo esc_html($start_date_label); ?></span>
    <?php endif; ?>
    <?php if ($start_time !== ''): ?>
      <span class="agency-sidebar-event__meta-item"><?php echo esc_html($start_time); ?></span>
    <?php endif; ?>
    <?php if ($place !== ''): ?>
      <span class="agency-sidebar-event__meta-item"><?php echo esc_html($place); ?></span>
    <?php endif; ?>
  </div>

  <?php if ($excerpt !== ''): ?>
    <p class="agency-sidebar-event__excerpt"><?php echo esc_html($excerpt); ?></p>
  <?php endif; ?>
</a>
