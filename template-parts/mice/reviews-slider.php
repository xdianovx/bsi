<?php
/**
 * Слайдер «Нас благодарят» для лендинга MICE (ACF / дефолты страницы).
 *
 * Данные через set_query_var перед get_template_part:
 * - bsimice_reviews_slider_reviews (array)
 * - bsimice_reviews_slider_heading (string)
 * - bsimice_reviews_slider_from_acf (bool)
 * - bsimice_reviews_section_id (string, опционально) id секции якоря
 */

$reviews = get_query_var('bsimice_reviews_slider_reviews', []);
if (!is_array($reviews)) {
  $reviews = [];
}

$reviews_heading = get_query_var('bsimice_reviews_slider_heading', '');
$reviews_heading = is_string($reviews_heading) ? $reviews_heading : '';
$reviews_from_acf = (bool) get_query_var('bsimice_reviews_slider_from_acf', false);

if ($reviews_heading === '') {
  $reviews_heading = 'Нас Благодарят';
}

$has_slides = false;
foreach ($reviews as $rev) {
  if (!is_array($rev)) {
    continue;
  }
  $quote = $rev['quote'] ?? '';
  $aname = $rev['author_name'] ?? '';
  if ($quote !== '' || $aname !== '') {
    $has_slides = true;
    break;
  }
}

if (!$has_slides) {
  return;
}

$reviews_section_id = get_query_var('bsimice_reviews_section_id', '');
$reviews_section_id = is_string($reviews_section_id) ? trim($reviews_section_id) : '';
?>

<section class="mice-reviews" <?php echo $reviews_section_id !== '' ? 'id="' . esc_attr($reviews_section_id) . '"' : ''; ?>>
  <div class="container">
    <div class="section_head">
      <h2 class="reviews__heading"><?php echo esc_html($reviews_heading); ?></h2>
      <div class="slider-arrow-wrap mice-reviews-slider__arrows-wrap">
        <div class="slider-arrow slider-arrow-prev mice-reviews-slider__prev" tabindex="0" role="button"
          aria-label="Previous slide"></div>
        <div class="slider-arrow slider-arrow-next mice-reviews-slider__next" tabindex="0" role="button"
          aria-label="Next slide"></div>
      </div>
    </div>
    <div class="reviews-swiper-outer">
      <div class="swiper mice-reviews-slider">
        <div class="swiper-wrapper">
            <?php foreach ($reviews as $rev):
              if (!is_array($rev)) {
                continue;
              }
              $quote = $rev['quote'] ?? '';
              $aname = $rev['author_name'] ?? '';
              $atitle = $rev['author_title'] ?? '';
              if ($quote === '' && $aname === '') {
                continue;
              }
              ?>
            <div class="swiper-slide">
                  <div class="slide_box">
                  <p class="review_text">
                    <?php
                    if (function_exists('bsimice_format_textarea')) {
                      echo bsimice_format_textarea($quote, $reviews_from_acf); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                      echo wp_kses_post(nl2br(esc_html((string) $quote)));
                    }
                    ?>
                  </p>
                  <div class="review_user">
                    <p class="review_user-name"><?php echo esc_html($aname); ?></p>
                    <span class="review_user-title"><?php echo esc_html($atitle); ?></span>
                  </div>
                </div>
              </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</section>