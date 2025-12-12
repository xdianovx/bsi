<section class="best-offers__section">
  <div class="container">
    <div class="title-wrap news-slider__title-wrap">
      <div class="news-slider__title-wrap-left">
        <h2 class="h2 news-slider__title">Лучшие предложения</h2>
        <div class="slider-arrow-wrap news-slider__arrows-wrap">
          <div class="slider-arrow slider-arrow-prev best-offers-arrow-prev"
               tabindex="-1"
               role="button"
               aria-label="Previous slide"
               aria-controls="swiper-wrapper-6afd786aee0e5cee"
               aria-disabled="true">
          </div>
          <div class="slider-arrow slider-arrow-next best-offers-arrow-next"
               tabindex="0"
               role="button"
               aria-label="Next slide"
               aria-controls="swiper-wrapper-6afd786aee0e5cee"
               aria-disabled="false">
          </div>
        </div>
      </div>

      <div class="title-wrap__buttons">
        <a href="http://localhost:8888/bsinew/novosti/"
           class="title-wrap__link link-arrow">
          <span>Смотреть все</span>
          <div class="link-arrow__icon">

            <svg xmlns="http://www.w3.org/2000/svg"
                 width="24"
                 height="24"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.5"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 class="lucide lucide-arrow-up-right-icon lucide-arrow-up-right">
              <path d="M7 7h10v10"></path>
              <path d="M7 17 17 7"></path>
            </svg>

          </div>
        </a>
      </div>
    </div>

    <div class="best-offers__content">
      <div class="swiper best-offers-slider">
        <div class="swiper-wrapper">

          <div class="swiper-slide">
            <?= get_template_part('template-parts/best-offers/card') ?>
          </div>
          <div class="swiper-slide">
            <?= get_template_part('template-parts/best-offers/card') ?>
          </div>
          <div class="swiper-slide">
            <?= get_template_part('template-parts/best-offers/card') ?>
          </div>
          <div class="swiper-slide">
            <?= get_template_part('template-parts/best-offers/card') ?>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>