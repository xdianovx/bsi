<?php
/**
 * Single CPT `excursion`. Каноничный URL — /excursion/{slug}/ (rewrite в inc/post-types/excursion.php).
 *
 * Первый экран — по образцу single-education.php: заголовок + локация + excerpt + main-info.
 * Обложка — featured image (post thumbnail) + галерея, без hero-фона.
 * Бронирование — модалка #modal-excursion-booking (template-parts/excursion/booking-modal.php).
 * Цены конвертируются в RUB через bsi_education_convert_price_to_rub();
 * переключатель `.js-education-show-original-currency` возвращает оригинал.
 */

$post_id = (int) get_the_ID();

$country_id = function_exists('bsi_get_excursion_country_id') ? bsi_get_excursion_country_id($post_id) : 0;
$country_title = $country_id ? get_the_title($country_id) : '';
$country_permalink = $country_id ? get_permalink($country_id) : '';
$country_flag = ($country_id && function_exists('bsi_get_country_flag_url'))
  ? bsi_get_country_flag_url($country_id)
  : '';

$region_terms = get_the_terms($post_id, 'region');
$region_term = (!empty($region_terms) && !is_wp_error($region_terms)) ? $region_terms[0] : null;
$region_name = $region_term ? (string) $region_term->name : '';
$region_permalink = '';
if ($region_term) {
  $rl = get_term_link($region_term);
  $region_permalink = is_wp_error($rl) ? '' : $rl;
}

$resort_terms = get_the_terms($post_id, 'resort');
$resort_term = (!empty($resort_terms) && !is_wp_error($resort_terms)) ? $resort_terms[0] : null;
$resort_name = $resort_term ? (string) $resort_term->name : '';
$resort_permalink = '';
if ($resort_term) {
  $rl = get_term_link($resort_term);
  $resort_permalink = is_wp_error($rl) ? '' : $rl;
}

/* Галерея — нормализуем как в single-education.php (lines 7-36) */
$gallery_raw = function_exists('get_field') ? get_field('excursion_gallery', $post_id) : [];
$gallery_raw = is_array($gallery_raw) ? $gallery_raw : [];

$gallery = [];
foreach ($gallery_raw as $image) {
  if (is_array($image) && !empty($image['url'])) {
    $gallery[] = $image;
    continue;
  }

  $img_id = is_array($image) ? ($image['ID'] ?? 0) : (int) $image;
  if (!$img_id) {
    continue;
  }

  $img_url = wp_get_attachment_image_url($img_id, 'large');
  if (!$img_url) {
    continue;
  }
  $img_full_url = wp_get_attachment_image_url($img_id, 'full');
  $img_alt = get_post_meta($img_id, '_wp_attachment_image_alt', true);

  $gallery[] = [
    'url' => $img_full_url ?: $img_url,
    'sizes' => ['large' => $img_url, 'full' => $img_full_url ?: $img_url],
    'alt' => $img_alt ?: get_the_title($post_id),
  ];
}

$program = function_exists('get_field') ? get_field('excursion_program', $post_id) : [];
$program = is_array($program) ? $program : [];

$included = function_exists('get_field') ? (string) get_field('excursion_included', $post_id) : '';
$not_included = function_exists('get_field') ? (string) get_field('excursion_not_included', $post_id) : '';
$faq = function_exists('get_field') ? get_field('excursion_faq', $post_id) : [];
$faq = is_array($faq) ? $faq : [];

$duration_hours = function_exists('get_field') ? (float) get_field('excursion_duration_hours', $post_id) : 0.0;
$phone = function_exists('get_field') ? trim((string) get_field('excursion_phone', $post_id)) : '';
$website = function_exists('get_field') ? trim((string) get_field('excursion_website', $post_id)) : '';
$cta_lead = function_exists('get_field') ? trim((string) get_field('excursion_cta_lead', $post_id)) : '';
if ($cta_lead === '') {
  $cta_lead = 'Оставьте заявку — менеджер свяжется с вами и поможет подобрать удобную дату.';
}

$tickets_rows = function_exists('bsi_get_excursion_tickets_rows') ? bsi_get_excursion_tickets_rows($post_id) : [];
$price_from = function_exists('bsi_get_excursion_price_from_rub') ? bsi_get_excursion_price_from_rub($post_id) : null;
$price_from_orig = function_exists('bsi_get_excursion_price_from_original')
  ? bsi_get_excursion_price_from_original($post_id)
  : ['amount' => null, 'currency' => null];

$language_terms = get_the_terms($post_id, 'excursion_language');
$language_names = [];
if (!is_wp_error($language_terms) && !empty($language_terms)) {
  foreach ($language_terms as $lt) {
    $language_names[] = $lt->name;
  }
}

$type_terms = get_the_terms($post_id, 'excursion_type');
$type_names = [];
if (!is_wp_error($type_terms) && !empty($type_terms)) {
  foreach ($type_terms as $tt) {
    $type_names[] = $tt->name;
  }
}

$excursion_title = get_the_title($post_id);

$duration_label = '';
if ($duration_hours > 0) {
  if (abs($duration_hours - round($duration_hours)) < 0.01) {
    $h = (int) round($duration_hours);
    $mod10 = $h % 10;
    $mod100 = $h % 100;
    if ($mod10 === 1 && $mod100 !== 11) {
      $duration_label = $h . ' час';
    } elseif (in_array($mod10, [2, 3, 4], true) && !in_array($mod100, [12, 13, 14], true)) {
      $duration_label = $h . ' часа';
    } else {
      $duration_label = $h . ' часов';
    }
  } else {
    $duration_label = rtrim(rtrim(number_format($duration_hours, 1, ',', ''), '0'), ',') . ' ч';
  }
}

$hero_price_line = '';
if ($price_from !== null && (int) $price_from > 0) {
  $hero_price_line = 'от ' . number_format((int) $price_from, 0, ',', ' ') . ' ₽';
}

/* Похожие экскурсии той же страны */
$related_excursions = [];
if ($country_id > 0 && function_exists('bsi_get_country_excursion_candidate_ids_cached')) {
  $candidate_ids = bsi_get_country_excursion_candidate_ids_cached((int) $country_id);
  $candidate_ids = array_values(array_filter(
    array_map('intval', $candidate_ids),
    static fn($id) => $id > 0 && $id !== $post_id
  ));
  if (!empty($candidate_ids)) {
    $related_excursions = get_posts([
      'post_type'              => 'excursion',
      'post_status'            => 'publish',
      'posts_per_page'         => 8,
      'post__in'               => $candidate_ids,
      'orderby'                => 'rand',
      'no_found_rows'          => true,
      'bsi_skip_schedule'      => true,
      'update_post_meta_cache' => false,
    ]);
  }
}

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

  <section class="single-excursion__title-section">
    <div class="container">
      <div class="single-education__title-wrap single-excursion__title-wrap">
        <div class="title-rating__wrap">
          <h1 class="h1 single-education__title"><?= esc_html($excursion_title); ?></h1>

          <?php if ($country_title || $region_name || $resort_name): ?>
            <div class="single-education__country single-hotel__address">
              <?php if ($country_flag): ?>
                <img src="<?= esc_url($country_flag); ?>" alt="<?= esc_attr($country_title); ?>"
                     class="single-education__flag">
              <?php endif; ?>
              <div class="single-education__location-text">
                <?php if ($country_title): ?>
                  <?php if ($country_permalink): ?>
                    <a href="<?= esc_url($country_permalink); ?>" class="single-education__country-link">
                      <?= esc_html($country_title); ?><?= ($region_name || $resort_name) ? ',' : ''; ?>
                    </a>
                  <?php else: ?>
                    <span class="single-education__country-text">
                      <?= esc_html($country_title); ?><?= ($region_name || $resort_name) ? ',' : ''; ?>
                    </span>
                  <?php endif; ?>
                <?php endif; ?>
                <?php if ($region_name): ?>
                  <span class="single-education__region-text">
                    <?= esc_html($region_name); ?><?= $resort_name ? ',' : ''; ?>
                  </span>
                <?php endif; ?>
                <?php if ($resort_name): ?>
                  <span class="single-education__resort-text"><?= esc_html($resort_name); ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (has_excerpt()): ?>
            <div class="single-education__excerpt page-country__descr">
              <?php the_excerpt(); ?>
            </div>
          <?php endif; ?>

          <?php
          $info_items = [];
          if ($duration_label !== '') {
            $info_items[] = ['Длительность', $duration_label];
          }
          if (!empty($type_names)) {
            $info_items[] = ['Тип', implode(', ', $type_names)];
          }
          if (!empty($language_names)) {
            $info_items[] = ['Язык гида', implode(', ', $language_names)];
          }
          ?>
          <?php if (!empty($info_items)): ?>
            <div class="single-education__main-info single-excursion__main-info">
              <?php foreach ($info_items as $i => $item): ?>
                <div class="single-education__info-item">
                  <span class="single-education__info-label"><?= esc_html($item[0]); ?>:</span>
                  <span class="single-education__info-value"><?= esc_html($item[1]); ?></span>
                </div>
                <?php if ($i < count($info_items) - 1): ?>
                  <span class="single-education__info-separator"></span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($gallery)): ?>
    <section class="single-education__gallery-section single-excursion__gallery-section">
      <div class="container">
        <div class="country-page__gallery">
          <?php
          get_template_part('template-parts/sections/gallery', null, [
            'gallery' => $gallery,
            'id' => 'excursion_' . $post_id,
          ]);
          ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="single-education__content-section single-excursion__content-section">
    <div class="container">
      <div class="single-education__content__wrap single-excursion__content__wrap">

        <div class="single-excursion__main-column">

          <?php if (have_posts()): ?>
            <?php while (have_posts()):
              the_post(); ?>
              <?php if (get_the_content()): ?>
                <section class="single-excursion__about">
                  <h2 class="h2">Об экскурсии</h2>
                  <div class="editor-content">
                    <?php the_content(); ?>
                  </div>
                </section>
              <?php endif; ?>
            <?php endwhile;
            rewind_posts(); ?>
          <?php endif; ?>

          <?php if (!empty($tickets_rows)): ?>
            <?php
            set_query_var('excursion_tickets_rows', $tickets_rows);
            set_query_var('excursion_post_id', $post_id);
            set_query_var('excursion_post_title', $excursion_title);
            get_template_part('template-parts/excursion/tickets-list');
            ?>
          <?php endif; ?>

          <?php if (!empty($program)): ?>
            <?php
            set_query_var('excursion_program_rows', $program);
            get_template_part('template-parts/excursion/program-days');
            ?>
          <?php endif; ?>
        </div>

        <aside class="single-education__aside-column single-excursion__aside-column">
          <div class="hotel-widget">
            <div class="single-education__school-title">
              <?= esc_html($excursion_title); ?>
            </div>

            <?php if ($phone || $website): ?>
              <div class="hotel-widget__contacts">
                <?php if ($phone): ?>
                  <div class="hotel-widget__phone hotel-widget__contacts-item">
                    <a href="tel:<?= esc_attr(preg_replace('/\s+/', '', $phone)); ?>">
                      <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/hotel/call.svg'); ?>" alt="">
                      <span><?= esc_html($phone); ?></span>
                    </a>
                  </div>
                <?php endif; ?>

                <?php if ($website): ?>
                  <div class="hotel-widget__site hotel-widget__contacts-item">
                    <a href="<?= esc_url($website); ?>" target="_blank" rel="nofollow noopener">
                      <img src="<?= esc_url(get_template_directory_uri() . '/img/icons/hotel/url.svg'); ?>" alt="">
                      <span>Сайт</span>
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if ($price_from !== null && (int) $price_from > 0): ?>
              <div class="single-education__info">
                <div class="single-education__info-item">
                  <div class="single-education__info-value js-excursion-price"
                       data-price-rub="<?= esc_attr((string) (int) $price_from); ?>"
                       <?php if (!empty($price_from_orig['amount']) && !empty($price_from_orig['currency'])): ?>
                       data-price-original="<?= esc_attr((string) $price_from_orig['amount']); ?>"
                       data-price-currency="<?= esc_attr((string) $price_from_orig['currency']); ?>"
                       <?php endif; ?>
                       data-has-from="true"><?= esc_html($hero_price_line); ?></div>
                </div>
              </div>
            <?php endif; ?>

            <div class="single-education__booking">
              <button type="button" class="btn btn-accent single-education__booking-btn js-excursion-booking-btn"
                      data-excursion-id="<?= esc_attr((string) $post_id); ?>"
                      data-excursion-title="<?= esc_attr($excursion_title); ?>">
                Забронировать
              </button>
            </div>
          </div>
        </aside>

      </div>
    </div>
  </section>

  <?php if (!empty($included) || !empty($not_included)): ?>
    <section class="single-education__price-details-section single-excursion__price-details-section">
      <div class="container">
        <div class="single-education__price-details">
          <?php if (!empty($included)): ?>
            <div class="single-education__price-included">
              <h3 class="single-education__price-title">В стоимость входит</h3>
              <div class="single-education__price-content">
                <?= wp_kses_post($included); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($not_included)): ?>
            <div class="single-education__price-extra">
              <h3 class="single-education__price-title">Оплачивается дополнительно</h3>
              <div class="single-education__price-content">
                <?= wp_kses_post($not_included); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($faq)): ?>
    <section class="single-event__faq single-excursion__faq">
      <div class="container">
        <h2 class="h2">Ответы на часто задаваемые вопросы</h2>
        <div class="accordion single-event__faq-acc">
          <div class="accordion__list single-event__faq-list">
            <?php foreach ($faq as $faq_row): ?>
              <?php
              $fq = isset($faq_row['question']) ? trim((string) $faq_row['question']) : '';
              $fa = isset($faq_row['answer']) ? (string) $faq_row['answer'] : '';
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

  <section class="single-event__booking-cta single-excursion__booking-cta" aria-label="Получить консультацию по экскурсии">
    <div class="container">
      <div class="single-event__booking-cta-inner">
        <div class="single-event__booking-cta-head">
          <h2 class="single-event__booking-cta-title">Получить консультацию</h2>
          <p class="single-event__booking-cta-lead"><?= esc_html($cta_lead); ?></p>
        </div>

        <form id="excursion-cta-form" class="single-event__booking-cta-form js-excursion-booking-form" novalidate
              data-excursion-id="<?= esc_attr((string) $post_id); ?>"
              data-excursion-title="<?= esc_attr($excursion_title); ?>">
          <input type="hidden" name="action" value="excursion_booking">
          <input type="hidden" name="excursion_booking_minimal" value="1">
          <input type="hidden" name="excursion_id" value="<?= esc_attr((string) $post_id); ?>">
          <input type="hidden" name="excursion_title" value="<?= esc_attr($excursion_title); ?>">
          <input type="hidden" name="excursion_date" value="">
          <input type="hidden" name="page_url" value="<?= esc_url(get_permalink($post_id)); ?>">

          <div class="single-event__booking-cta-row">
            <div class="single-event__booking-cta-field">
              <label class="screen-reader-text" for="excursion-cta-name">Имя</label>
              <input id="excursion-cta-name" type="text" name="name" class="single-event__booking-cta-input"
                     placeholder="имя" autocomplete="name" required data-field="name">
              <span class="single-event__booking-cta-field-error js-field-error" data-error-for="name"></span>
            </div>
            <div class="single-event__booking-cta-field">
              <label class="screen-reader-text" for="excursion-cta-phone">Телефон</label>
              <input id="excursion-cta-phone" type="tel" name="phone"
                     class="single-event__booking-cta-input js-phone-mask"
                     placeholder="Телефон" autocomplete="tel" required data-field="phone">
              <span class="single-event__booking-cta-field-error js-field-error" data-error-for="phone"></span>
            </div>
            <button type="submit" class="single-event__booking-cta-submit" data-default-label="Получить консультацию">
              Получить консультацию
            </button>
          </div>

          <?php
          if (function_exists('bsi_render_privacy_consent_checkbox')) {
            bsi_render_privacy_consent_checkbox([
              'variant' => 'event-booking-cta',
              'checkbox_id' => 'excursion-cta-privacy',
            ]);
          }
          ?>
        </form>
      </div>
    </div>
  </section>

  <?php
  if (!empty($related_excursions)) {
    set_query_var('related_excursions', $related_excursions);
    get_template_part('template-parts/excursion/related-slider');
  }
  ?>

</main>

<?php
get_template_part('template-parts/excursion/booking-modal');
get_footer();
?>
