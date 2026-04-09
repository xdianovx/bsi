<?php
$banners = get_field('banners', get_the_ID());
?>

<section class="main-banner__section">
  <div class="container">
    <div class="swiper main-banners-slider">
      <div class="swiper-wrapper">


        <?php foreach ($banners as $banner): ?>
          <div class="swiper-slide">
            <?php $banner_alt = esc_attr($banner['title'] ?? 'Баннер BSI Group'); ?>
            <?php if (!empty($banner['url'])): ?>
              <a href="<?= esc_url($banner['url']); ?>" target="_blank" rel="noopener noreferrer" class="main-banner__slide">
                <?php if (!empty($banner['mobilnyj_banner'])): ?>
                  <img class="main-banner__slide_image main-banner__slide_image--desktop" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" />
                  <img class="main-banner__slide_image main-banner__slide_image--mobile"
                    src="<?= esc_url($banner['mobilnyj_banner']); ?>" alt="<?= $banner_alt; ?>" />
                <?php else: ?>
                  <img class="main-banner__slide_image" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" />
                <?php endif; ?>
              </a>
            <?php else: ?>
              <div class="main-banner__slide">
                <?php if (!empty($banner['mobilnyj_banner'])): ?>
                  <img class="main-banner__slide_image main-banner__slide_image--desktop" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" />
                  <img class="main-banner__slide_image main-banner__slide_image--mobile"
                    src="<?= esc_url($banner['mobilnyj_banner']); ?>" alt="<?= $banner_alt; ?>" />
                <?php else: ?>
                  <img class="main-banner__slide_image" src="<?= esc_url($banner['img']); ?>" alt="<?= $banner_alt; ?>" />
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