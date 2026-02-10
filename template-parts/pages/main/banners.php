<?php
$banners = get_field('banners', get_the_ID());
?>

<section class="main-banner__section">
  <div class="container">
    <div class="swiper main-banners-slider">
      <div class="swiper-wrapper">


        <?php foreach ($banners as $banner): ?>
          <div class="swiper-slide">
            <?php if (!empty($banner['url'])): ?>
              <a href="<?= esc_url($banner['url']); ?>" target="_blank" rel="noopener noreferrer" class="main-banner__slide">
                <?php if (!empty($banner['mobilnyj_banner'])): ?>
                  <img class="main-banner__slide_image main-banner__slide_image--desktop" src="<?= $banner['img']; ?>" />
                  <img class="main-banner__slide_image main-banner__slide_image--mobile"
                    src="<?= $banner['mobilnyj_banner']; ?>" />
                <?php else: ?>
                  <img class="main-banner__slide_image" src="<?= $banner['img']; ?>" />
                <?php endif; ?>
              </a>
            <?php else: ?>
              <div class="main-banner__slide">
                <?php if (!empty($banner['mobilnyj_banner'])): ?>
                  <img class="main-banner__slide_image main-banner__slide_image--desktop" src="<?= $banner['img']; ?>" />
                  <img class="main-banner__slide_image main-banner__slide_image--mobile"
                    src="<?= $banner['mobilnyj_banner']; ?>" />
                <?php else: ?>
                  <img class="main-banner__slide_image" src="<?= $banner['img']; ?>" />
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