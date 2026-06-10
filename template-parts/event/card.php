<?php
/**
 * Карточка-плитка событийного тура (CPT event) — каталог «Событийные туры», вид «плитки».
 *
 *   get_template_part('template-parts/event/card', null, ['post_id' => $event_id]);
 *
 * Вид «список» использует template-parts/event/card-row.php.
 */

$post_id = isset($args['post_id']) ? (int) $args['post_id'] : (int) get_the_ID();
if (!$post_id) {
  return;
}

$country_raw = function_exists('get_field') ? get_field('tour_country', $post_id) : null;
$country_id = 0;
if ($country_raw instanceof WP_Post) {
  $country_id = (int) $country_raw->ID;
} elseif (is_array($country_raw)) {
  $country_id = (int) reset($country_raw);
} else {
  $country_id = (int) $country_raw;
}

$link = get_permalink($post_id);
$title = get_the_title($post_id);

// Изображение: миниатюра → галерея → hero-обложка.
$img = get_the_post_thumbnail_url($post_id, 'large');
if (!$img && function_exists('get_field')) {
  $gallery = (array) get_field('tour_gallery', $post_id);
  $first = $gallery[0] ?? null;
  if (is_array($first)) {
    $img = !empty($first['sizes']['large']) ? $first['sizes']['large'] : ($first['url'] ?? '');
  }
}
if (!$img && function_exists('get_field')) {
  $hero = get_field('event_hero_cover', $post_id);
  if (is_array($hero)) {
    $img = !empty($hero['sizes']['large']) ? $hero['sizes']['large'] : ($hero['url'] ?? '');
  }
}

// Тип события — плашки поверх фото.
$type_terms = get_the_terms($post_id, BSI_EVENT_TOUR_TYPE_TAXONOMY);
if (is_wp_error($type_terms)) {
  $type_terms = [];
}

// Локация: страна + город (resort).
$country_title = $country_id ? get_the_title($country_id) : '';
$flag_url = ($country_id && function_exists('bsi_get_country_flag_url'))
  ? bsi_get_country_flag_url($country_id)
  : '';

$resorts = get_the_terms($post_id, 'resort');
$city = (!empty($resorts) && !is_wp_error($resorts)) ? $resorts[0]->name : '';

// Ближайшая дата из event_dates (fallback — event_hero_date) + кол-во остальных дат.
$event_card_date = '';
$event_dates_more = 0;
$event_dates_rows = function_exists('get_field') ? get_field('event_dates', $post_id) : [];
if (!empty($event_dates_rows) && is_array($event_dates_rows)) {
  $ds = [];
  foreach ($event_dates_rows as $row) {
    if (!empty($row['date_value'])) {
      $ds[] = (string) $row['date_value'];
    }
  }
  $ds = array_values(array_unique($ds));
  sort($ds);
  if (!empty($ds[0])) {
    $event_card_date = date_i18n('j.m.Y', strtotime($ds[0]));
    $event_dates_more = max(0, count($ds) - 1);
  }
}
if ($event_card_date === '' && function_exists('get_field')) {
  $hero_d = get_field('event_hero_date', $post_id);
  if (is_string($hero_d) && $hero_d !== '') {
    $event_card_date = date_i18n('j.m.Y', strtotime($hero_d));
  }
}

$nights = function_exists('get_field') ? (int) get_field('tour_nights', $post_id) : 0;

// «Экскурсий» — источника нет, поле скрыто (задел на будущее).
$excursions = 0;

// Описание: event_about → excerpt (обрезка 3 строки через CSS).
$excerpt_raw = '';
if (function_exists('get_field')) {
  $about = get_field('event_about', $post_id);
  if (is_string($about) && trim($about) !== '') {
    $excerpt_raw = wp_strip_all_tags($about);
  }
}
if ($excerpt_raw === '') {
  $excerpt_raw = wp_strip_all_tags(get_the_excerpt($post_id));
}
$excerpt_raw = preg_replace('/\s+/u', ' ', trim((string) $excerpt_raw));

// «Включено в тур» — иконки таксономии tour_include (как .tour-card__anemeties).
$include_terms = get_the_terms($post_id, 'tour_include');
if (is_wp_error($include_terms) || empty($include_terms)) {
  $include_terms = [];
}

// Цена для кнопки «От …».
$price = function_exists('bsi_event_card_price')
  ? bsi_event_card_price($post_id)
  : ['rub' => null, 'original' => null, 'currency' => null];
$price_rub = isset($price['rub']) ? $price['rub'] : null;
$price_original = isset($price['original']) ? $price['original'] : null;
$price_currency = isset($price['currency']) ? $price['currency'] : null;
?>

<article class="event-card">
  <a href="<?= esc_url($link); ?>" class="event-card__media">
    <?php if ($img): ?>
      <img class="event-card__img" src="<?= esc_url($img); ?>" alt="<?= esc_attr($title); ?>" loading="lazy">
    <?php else: ?>
      <span class="event-card__img-placeholder" aria-hidden="true"></span>
    <?php endif; ?>

    <?php if (!empty($type_terms)): ?>
      <div class="event-card__label">
        <?php foreach (array_slice($type_terms, 0, 3) as $term): ?>
          <span class="event-card__label-tag"><?= esc_html($term->name); ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </a>

  <div class="event-card__body">
    <?php if ($country_title !== '' || $city !== ''): ?>
      <div class="event-card__location">
        <?php if ($flag_url !== ''): ?>
          <span class="event-card__flag">
            <img src="<?= esc_url($flag_url); ?>" alt="" width="18" height="18" loading="lazy">
          </span>
        <?php endif; ?>
        <span class="event-card__location-text">
          <?php
          $loc = array_filter([$country_title, $city]);
          echo esc_html(implode(', ', $loc));
          ?>
        </span>
      </div>
    <?php endif; ?>

    <h3 class="event-card__title">
      <a href="<?= esc_url($link); ?>"><?= esc_html($title); ?></a>
    </h3>

    <?php if ($event_card_date !== '' || $nights > 0): ?>
      <div class="event-card__dateline">
        <?php if ($event_card_date !== ''): ?>
          <div class="event-card__date">
            <svg class="event-card__date-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>
            </svg>
            <span class="numfont"><?= esc_html($event_card_date); ?></span>
            <?php if ($event_dates_more > 0): ?>
              <span class="event-card__date-more">…и еще <span class="numfont"><?= (int) $event_dates_more; ?></span>
                <?= esc_html(function_exists('bsi_plural_ru') ? bsi_plural_ru($event_dates_more, 'дата', 'даты', 'дат') : 'дат'); ?></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($nights > 0): ?>
          <span class="event-card__nights"><span class="numfont"><?= esc_html((string) $nights); ?></span>
            <?= esc_html(function_exists('bsi_plural_ru') ? bsi_plural_ru($nights, 'ночь', 'ночи', 'ночей') : 'ночей'); ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($include_terms)): ?>
      <div class="hotel-card__includes tour-card__includes">
        <div class="tour-card__anemeties">
          <?php foreach ($include_terms as $term): ?>
            <?php
            $icon_url = '';
            if (function_exists('get_field')) {
              $icon = get_field('tour_include_icon', 'term_' . $term->term_id);
              if (is_array($icon) && !empty($icon['url'])) {
                $icon_url = (string) $icon['url'];
              } elseif (is_string($icon) && $icon !== '') {
                $icon_url = (string) $icon;
              } elseif (is_numeric($icon)) {
                $tmp = wp_get_attachment_image_url((int) $icon, 'thumbnail');
                if ($tmp) {
                  $icon_url = (string) $tmp;
                }
              }
            }

            if (!$icon_url) {
              $meta = get_term_meta($term->term_id, 'tour_include_icon', true);
              if (is_array($meta) && !empty($meta['url'])) {
                $icon_url = (string) $meta['url'];
              } elseif (is_string($meta) && $meta !== '') {
                $icon_url = (string) $meta;
              } elseif (is_numeric($meta)) {
                $tmp = wp_get_attachment_image_url((int) $meta, 'thumbnail');
                if ($tmp) {
                  $icon_url = (string) $tmp;
                }
              }
            }
            ?>

            <?php if ($icon_url): ?>
              <span class="hotel-card__anemetie" title="<?php echo esc_attr($term->name); ?>">
                <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($term->name); ?>">
              </span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($excerpt_raw !== ''): ?>
      <p class="event-card__excerpt"><?= esc_html($excerpt_raw); ?></p>
    <?php endif; ?>

    <div class="event-card__actions">
      <a href="<?= esc_url($link); ?>" class="event-card__btn event-card__btn-details">Подробнее</a>
      <a href="<?= esc_url($link); ?>"
        class="btn btn-accent event-card__btn event-card__btn-book<?= $price_rub !== null ? ' js-event-price' : ''; ?>"
        data-crosstour-card="<?= esc_attr((string) (int) $post_id); ?>"
        <?php if ($price_rub !== null): ?>
        data-price-rub="<?= esc_attr((string) (int) $price_rub); ?>"
        <?php if ($price_original !== null && $price_currency !== null): ?>
        data-price-original="<?= esc_attr((string) $price_original); ?>"
        data-price-currency="<?= esc_attr($price_currency); ?>"
        <?php endif; ?>
        data-has-from="true"
        <?php endif; ?>>
        <?php if ($price_rub !== null): ?>
          от <?= esc_html(number_format((int) $price_rub, 0, ',', ' ')); ?> ₽
        <?php else: ?>
          по запросу
        <?php endif; ?>
      </a>
    </div>
  </div>
</article>
