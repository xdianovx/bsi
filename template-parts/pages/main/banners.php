<?php
$front_page_id = (int) get_option('page_on_front');
if ($front_page_id <= 0) {
  return;
}

$banners = function_exists('get_field') ? (get_field('banners', $front_page_id) ?: []) : [];
$banners = function_exists('bsi_filter_schedule_rows') ? bsi_filter_schedule_rows($banners) : $banners;

if (empty($banners)) {
  return;
}
?>

<section class="main-banner__section">
  <div class="container">
    <div class="swiper main-banners-slider">
      <div class="swiper-wrapper">


        <?php foreach ($banners as $i => $banner): ?>
          <?php
          $banner_alt   = esc_attr($banner['title'] ?? 'Баннер BSI Group');
          $is_first     = ($i === 0);
          $loading      = $is_first ? 'eager' : 'lazy';
          $fetchpriority = $is_first ? ' fetchpriority="high"' : '';
          ?>
          <div class="swiper-slide">
            <?php if (!empty($banner['url'])): ?>
              <a href="<?= esc_url($banner['url']); ?>" target="_blank" rel="noopener noreferrer" class="main-banner__slide">
                <?php if (!empty($banner['mobilnyj_banner'])): ?>
                  <img class="main-banner__slide_image main-banner__slide_image--desktop" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" loading="<?= $loading; ?>"<?= $fetchpriority; ?> decoding="async" />
                  <img class="main-banner__slide_image main-banner__slide_image--mobile" src="<?= esc_url($banner['mobilnyj_banner']); ?>" alt="<?= $banner_alt; ?>" loading="<?= $loading; ?>"<?= $fetchpriority; ?> decoding="async" />
                <?php else: ?>
                  <img class="main-banner__slide_image" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" loading="<?= $loading; ?>"<?= $fetchpriority; ?> decoding="async" />
                <?php endif; ?>
              </a>
            <?php else: ?>
              <div class="main-banner__slide">
                <?php if (!empty($banner['mobilnyj_banner'])): ?>
                  <img class="main-banner__slide_image main-banner__slide_image--desktop" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" loading="<?= $loading; ?>"<?= $fetchpriority; ?> decoding="async" />
                  <img class="main-banner__slide_image main-banner__slide_image--mobile" src="<?= esc_url($banner['mobilnyj_banner']); ?>" alt="<?= $banner_alt; ?>" loading="<?= $loading; ?>"<?= $fetchpriority; ?> decoding="async" />
                <?php else: ?>
                  <img class="main-banner__slide_image" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" loading="<?= $loading; ?>"<?= $fetchpriority; ?> decoding="async" />
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-pagination main-banner__pagination"></div>
      <div class="slider-arrow-wrap main-banner__arrows">
        <div class="slider-arrow slider-arrow-prev main-banner-arrow-prev">
        </div>
        <div class="slider-arrow slider-arrow-next main-banner-arrow-next">
        </div>
      </div>

    </div>

  </div>
</section>