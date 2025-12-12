<?php
$banners = get_field('banners', get_the_ID());
?>

<section class="main-banner__section">
  <div class="container">
    <div class="swiper main-banners-slider">
      <div class="swiper-wrapper">


        <?php foreach ($banners as $banner): ?>
          <div class="swiper-slide">
            <div class="main-banner__slide">
              <img class="main-banner__slide_image"
                   src="<?= $banner['img']; ?>" />

            </div>
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