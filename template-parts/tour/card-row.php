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
$duration = function_exists('get_field') ? trim((string) get_field('tour_duration', $post_id)) : '';
$route = function_exists('get_field') ? trim((string) get_field('tour_route', $post_id)) : '';
$booking_url = function_exists('get_field') ? trim((string) get_field('tour_booking_url', $post_id)) : '';

// Taxonomies
$types = get_the_terms($post_id, 'tour_type');
$regions = get_the_terms($post_id, 'region');
$resorts = get_the_terms($post_id, 'resort');

// ✅ Включено в тур (имя таксы как в CPT: tour_include)
$included = [];
if (taxonomy_exists('tour_include')) {
  $included = get_the_terms($post_id, 'tour_include');
  if (is_wp_error($included) || empty($included))
    $included = [];
}

// URLs / text
$link = get_permalink($post_id);
$title = get_the_title($post_id);
$excerpt = get_the_excerpt($post_id);

// Image: featured -> first gallery
$img = get_the_post_thumbnail_url($post_id, 'large');
if (!$img && !empty($tour_gallery)) {
  $first = $tour_gallery[0] ?? null;
  if (is_array($first)) {
    if (!empty($first['sizes']['large']))
      $img = $first['sizes']['large'];
    elseif (!empty($first['url']))
      $img = $first['url'];
  }
}

// Country title (в карточке без ссылок)
$country_title = $country_id ? get_the_title($country_id) : '';
?>

<article class="tour-card-row">

  <?php if (!empty($types) && !is_wp_error($types)): ?>
    <div class="tour-card-row__tags">
      <?php foreach ($types as $t): ?>
        <span class="tour-card-row__tag"><?= esc_html($t->name); ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

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

      <?php if ($country_title || (!empty($regions) && !is_wp_error($regions)) || (!empty($resorts) && !is_wp_error($resorts))): ?>
        <div class="tour-card-row__location">

          <?php if ($country_title): ?>
            <div class="tour-card-row__location-link">
              <?= esc_html($country_title); ?>
              <?= (!empty($regions) && !is_wp_error($regions)) || (!empty($resorts) && !is_wp_error($resorts)) ? ',' : ''; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($regions) && !is_wp_error($regions)): ?>
            <?php $r = $regions[0]; ?>
            <div class="tour-card-row__location-link">
              <?= esc_html($r->name); ?>     <?= (!empty($resorts) && !is_wp_error($resorts)) ? ',' : ''; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($resorts) && !is_wp_error($resorts)): ?>
            <?php $s = $resorts[0]; ?>
            <div class="tour-card-row__location-link">
              <?= esc_html($s->name); ?>
            </div>
          <?php endif; ?>

        </div>
      <?php endif; ?>

      <h3 class="tour-card-row__title"><a href="<?= esc_url($link); ?>">

          <?= esc_html($title); ?></h3>
      </a>

      <?php if ($duration): ?>
        <div class="tour-card-row__duration">
          <span class="tour-card-row__duration-label">
            <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/tour/cal.svg'); ?>"
                 alt="">
          </span>
          <span class="tour-card-row__duration-value numfont"><?= esc_html($duration); ?></span>
        </div>
      <?php endif; ?>

    </div>

    <?php if ($route): ?>
      <div class="tour-card-row__route numfont">
        <span class="tour-card-row__route-label">
          <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/tour/route.svg'); ?>"
               alt="">
        </span>
        <span class="tour-card-row__route-value"><?= esc_html($route); ?></span>
      </div>
    <?php endif; ?>




  </div>

  <div class="tour-card-row__actions">
    <?php if (!empty($included)): ?>
      <p class="tour-card-row__actions_title">Включено:</p>
      <div class="tour-card-row__included">
        <?php foreach ($included as $t): ?>
          <?php
          // ✅ ACF поле у термина: tour_include_icon
          $icon = function_exists('get_field') ? get_field('tour_include_icon', 'term_' . $t->term_id) : null;
          $icon_url = (is_array($icon) && !empty($icon['url'])) ? $icon['url'] : '';
          ?>
          <span class="tour-tag">
            <?php if ($icon_url): ?>
              <img class="tour-tag__icon"
                   src="<?= esc_url($icon_url); ?>"
                   alt=""
                   loading="lazy">
            <?php endif; ?>
            <span class="tour-tag__text"><?= esc_html($t->name); ?></span>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="tour-card-row__btns">
      <a class="tour-card-row__more sm btn btn-gray"
         href="<?= esc_url($link); ?>">Подробнее</a>

      <?php if ($booking_url): ?>
        <a class="tour-card-row__book sm btn btn-accent"
           href="<?= esc_url($booking_url); ?>"
           target="_blank"
           rel="nofollow noopener">Забронировать</a>
      <?php endif; ?>
    </div>
  </div>

</article>