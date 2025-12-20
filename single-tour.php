<?php
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
$tour_program = function_exists('get_field') ? get_field('tour_program', $post_id) : [];
$tour_included = function_exists('get_field') ? (string) get_field('tour_included', $post_id) : '';
$tour_not_inc = function_exists('get_field') ? (string) get_field('tour_not_included', $post_id) : '';
$tour_extra = function_exists('get_field') ? (string) get_field('tour_extra', $post_id) : '';

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

  <?php if (!empty($tour_gallery) && is_array($tour_gallery)): ?>
    <section class="tour-gallery-section">
      <div class="container">
        <div class="country-page__gallery">
          <?php
          get_template_part('template-parts/sections/gallery', null, [
            'gallery' => $tour_gallery,
            'id' => 'tour_' . $post_id,
          ]);
          ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="single-hotel__content">
    <div class="container">
      <div class="single-hotel__content__wrap">

        <div class="hotel-content editor-content">
          <?php if (!empty($excerpt)): ?>
            <div class="page-country__descr">
              <?= wp_kses_post(wpautop($excerpt)); ?>
            </div>
          <?php endif; ?>
          <?php if ($tour_duration || $tour_route): ?>
            <div class="tour-details">
              <?php if ($tour_duration): ?>
                <p><strong>Продолжительность:</strong> <?= esc_html($tour_duration); ?></p>
              <?php endif; ?>

              <?php if ($tour_route): ?>
                <p><strong>Маршрут:</strong> <?= esc_html($tour_route); ?></p>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_program) && is_array($tour_program)): ?>
            <div class="tour-program">
              <h2 class="h2">Программа тура</h2>

              <?php foreach ($tour_program as $day): ?>
                <?php
                $day_title = !empty($day['day_title']) ? (string) $day['day_title'] : '';
                $day_text = !empty($day['day_content']) ? (string) $day['day_content'] : '';
                if (!$day_title && !$day_text)
                  continue;
                ?>
                <div class="tour-program__day">
                  <?php if ($day_title): ?>
                    <h3 class="h3"><?= esc_html($day_title); ?></h3>
                  <?php endif; ?>

                  <?php if ($day_text): ?>
                    <div class="editor-content">
                      <?= wp_kses_post($day_text); ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_included)): ?>
            <div class="tour-included">
              <h2 class="h2">В стоимость включено</h2>
              <div class="editor-content">
                <?= wp_kses_post($tour_included); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_not_inc)): ?>
            <div class="tour-not-included">
              <h2 class="h2">В стоимость не включено</h2>
              <div class="editor-content">
                <?= wp_kses_post($tour_not_inc); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($tour_extra)): ?>
            <div class="tour-extra">
              <h2 class="h2">Дополнительно</h2>
              <div class="editor-content">
                <?= wp_kses_post($tour_extra); ?>
              </div>
            </div>
          <?php endif; ?>

        </div>




        <!-- Виджеты -->
        <aside class="hotel-aside">


          <?php if (!empty($include_terms) && !is_wp_error($include_terms)): ?>
            <div class="tour-include">
              <?php foreach ($include_terms as $t):
                $icon = function_exists('get_field') ? get_field('tour_include_icon', 'term_' . $t->term_id) : null;
                $icon_url = (is_array($icon) && !empty($icon['url'])) ? $icon['url'] : '';
                ?>
                <span class="tour-include__item">
                  <?php if ($icon_url): ?>
                    <img class="tour-include__icon"
                         src="<?= esc_url($icon_url); ?>"
                         alt=""
                         loading="lazy">
                  <?php endif; ?>
                  <span class="tour-include__text"><?= esc_html($t->name); ?></span>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($tour_booking_url): ?>
            <a href="<?= esc_url($tour_booking_url); ?>"
               class="btn btn-accent tour-widget__btn-book sm"
               target="_blank"
               rel="nofollow noopener">
              Забронировать
            </a>
          <?php endif; ?>


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
                  <img src="<?= esc_url($country_flag); ?>"
                       alt="">
                <?php endif; ?>

                <div class="single-hotel__address-text">
                  <?= implode(', ', $items); ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </aside>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>