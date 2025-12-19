<?php
/**
 * Template part: Tour card (row)
 * Usage:
 *   get_template_part('template-parts/tour/card-row', null, ['post_id' => $tour_id]);
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id)
  return;

// ACF
$country_id = function_exists('get_field') ? (int) get_field('tour_country', $post_id) : 0;
$tour_gallery = function_exists('get_field') ? (array) get_field('tour_gallery', $post_id) : [];
$duration = function_exists('get_field') ? (string) get_field('tour_duration', $post_id) : '';
$route = function_exists('get_field') ? (string) get_field('tour_route', $post_id) : '';

// Taxonomies (обычные WP)
$types = get_the_terms($post_id, 'tour_type');
$regions = get_the_terms($post_id, 'region');
$resorts = get_the_terms($post_id, 'resort');

// URLs / text
$link = get_permalink($post_id);
$title = get_the_title($post_id);
$excerpt = get_the_excerpt($post_id);

// Картинка: сначала featured, иначе первая из галереи
$img = get_the_post_thumbnail_url($post_id, 'large');
if (!$img && !empty($tour_gallery) && is_array($tour_gallery)) {
  $first = $tour_gallery[0] ?? null;
  if (is_array($first)) {
    if (!empty($first['sizes']['large']))
      $img = $first['sizes']['large'];
    elseif (!empty($first['url']))
      $img = $first['url'];
  }
}

// Страна
$country_title = $country_id ? get_the_title($country_id) : '';
$country_url = $country_id ? get_permalink($country_id) : '';
?>

<article class="tour-card-row">
  <a class="tour-card-row__link"
     href="<?= esc_url($link); ?>">
    <div class="tour-card-row__poster">
      <?php if ($img): ?>
        <img class="tour-card-row__img"
             src="<?= esc_url($img); ?>"
             alt="<?= esc_attr($title); ?>"
             loading="lazy">
      <?php else: ?>
        <div class="tour-card-row__img-placeholder"></div>
      <?php endif; ?>
    </div>

    <div class="tour-card-row__content">

      <div class="tour-card-row__top">
        <h3 class="tour-card-row__title"><?= esc_html($title); ?></h3>

        <?php if ($duration): ?>
          <div class="tour-card-row__duration">
            <span class="tour-card-row__duration-label">Длительность:</span>
            <span class="tour-card-row__duration-value"><?= esc_html($duration); ?></span>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($country_title || (!empty($regions) && !is_wp_error($regions)) || (!empty($resorts) && !is_wp_error($resorts))): ?>
        <div class="tour-card-row__location">
          <?php if ($country_title && $country_url): ?>
            <span class="tour-card-row__location-item">
              <span class="tour-card-row__location-sep"> </span>
              <a class="tour-card-row__location-link"
                 href="<?= esc_url($country_url); ?>"><?= esc_html($country_title); ?></a>
            </span>
          <?php elseif ($country_title): ?>
            <span class="tour-card-row__location-item"><?= esc_html($country_title); ?></span>
          <?php endif; ?>

          <?php if (!empty($regions) && !is_wp_error($regions)): ?>
            <?php $r = $regions[0];
            $r_url = get_term_link($r); ?>
            <span class="tour-card-row__location-item">
              <span class="tour-card-row__location-sep">, </span>
              <?php if (!is_wp_error($r_url)): ?>
                <a class="tour-card-row__location-link"
                   href="<?= esc_url($r_url); ?>"><?= esc_html($r->name); ?></a>
              <?php else: ?>
                <?= esc_html($r->name); ?>
              <?php endif; ?>
            </span>
          <?php endif; ?>

          <?php if (!empty($resorts) && !is_wp_error($resorts)): ?>
            <?php $s = $resorts[0];
            $s_url = get_term_link($s); ?>
            <span class="tour-card-row__location-item">
              <span class="tour-card-row__location-sep">, </span>
              <?php if (!is_wp_error($s_url)): ?>
                <a class="tour-card-row__location-link"
                   href="<?= esc_url($s_url); ?>"><?= esc_html($s->name); ?></a>
              <?php else: ?>
                <?= esc_html($s->name); ?>
              <?php endif; ?>
            </span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($route): ?>
        <div class="tour-card-row__route">
          <span class="tour-card-row__route-label">Маршрут:</span>
          <span class="tour-card-row__route-value"><?= esc_html($route); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($excerpt): ?>
        <div class="tour-card-row__excerpt">
          <?= esc_html($excerpt); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($types) && !is_wp_error($types)): ?>
        <div class="tour-card-row__tags">
          <?php foreach ($types as $t): ?>
            <span class="tour-card-row__tag"><?= esc_html($t->name); ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="tour-card-row__actions">
        <span class="tour-card-row__more">Подробнее</span>
      </div>

    </div>
  </a>
</article>