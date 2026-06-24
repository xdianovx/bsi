import { Swiper } from "swiper/bundle";
import { Fancybox } from "@fancyapps/ui";

export const sliders = () => {
  const blocks = document.querySelectorAll(".js-gallery");
  if (blocks) {
    blocks.forEach((block) => {
      const mainEl = block.querySelector(".js-gallery-main");
      const thumbsEl = block.querySelector(".js-gallery-thumbs");
      const prevEl = block.querySelector(".js-gallery-prev");
      const nextEl = block.querySelector(".js-gallery-next");

      if (!mainEl || !thumbsEl) return;

      const thumbsSwiper = new Swiper(thumbsEl, {
        slidesPerView: 4,
        spaceBetween: 8,
        slideToClickedSlide: true,
        watchSlidesProgress: true,

        breakpoints: {
          320: {
            direction: "horizontal",
          },
          1201: {
            direction: "vertical",
          },
        },
      });

      const mainSwiper = new Swiper(mainEl, {
        spaceBetween: 8,
        navigation: {
          prevEl,
          nextEl,
        },
        thumbs: {
          swiper: thumbsSwiper,
        },
      });

      const overlayEl = block.querySelector(".js-gallery-overlay");
      if (overlayEl) {
        overlayEl.addEventListener("mousedown", (e) => {
          e.stopPropagation();
        });

        overlayEl.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();

          const galleryId = overlayEl.getAttribute("data-gallery-id");
          const allGalleryItems = block.querySelectorAll(`a[data-fancybox="${galleryId}"]`);
          const hiddenItems = Array.from(allGalleryItems).slice(4);

          if (hiddenItems.length > 0) {
            Fancybox.show(
              hiddenItems.map((item) => ({
                src: item.getAttribute("href"),
                caption: item.querySelector("img")?.getAttribute("alt") || "",
              })),
              {
                groupAttr: `data-fancybox="${galleryId}"`,
              }
            );
          }
        });
      }
    });
  }

  const countryGallerySlider = new Swiper(".country-page__gallery-slider", {
    slidesPerView: 4,
    spaceBetween: 10,
    loop: true,
    navigation: {
      nextEl: ".country-page__gallery-button--next",
      prevEl: ".country-page__gallery-button--prev",
    },
  });

  const mainBannersSlider = new Swiper(".main-banners-slider", {
    slidesPerView: 1,
    spaceBetween: 10,
    speed: 800,
    loop: true,
    effect: "fade",
    fadeEffect: { crossFade: true },
    autoplay: {
      delay: 4000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true,
    },
    navigation: {
      nextEl: ".main-banner-arrow-next",
      prevEl: ".main-banner-arrow-prev",
    },
    pagination: {
      el: ".main-banner__pagination",
      type: "fraction",
      renderFraction: (currentClass, totalClass) => `<span class="${currentClass}"></span>/<span class="${totalClass}"></span>`,
    },
  });

  const newsSectionSlider = new Swiper(".news-slider-slider", {
    spaceBetween: 16,
    navigation: {
      nextEl: ".news-slider-arrow-next",
      prevEl: ".news-slider-arrow-prev",
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
      },
      769: {
        slidesPerView: 3,
      },
      1200: {
        slidesPerView: 3,
      },
    },
  });

  const singleEventRelatedEl = document.querySelector(".single-event-related-slider");
  if (singleEventRelatedEl) {
    new Swiper(singleEventRelatedEl, {
      spaceBetween: 16,
      watchOverflow: true,
      navigation: {
        nextEl: ".single-event-related-next",
        prevEl: ".single-event-related-prev",
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        769: {
          slidesPerView: 2,
        },
        1200: {
          slidesPerView: 3,
        },
      },
    });
  }

  const partnersSectionSlider = new Swiper(".partners-slider-slider", {
    autoplay: { delay: 0 },
    loop: true,
    freeMode: true,
    speed: 3000,
    breakpoints: {
      320: {
        spaceBetween: 64,
        slidesPerView: 2,
      },
      480: {
        slidesPerView: 3,
      },
      769: {
        slidesPerView: 4,
      },
      1200: {
        slidesPerView: 6,
        spaceBetween: 64,
      },
    },
  });

  const hotelGalleryMainSlider = new Swiper(".hotel-gallery-main-slider", {
    slidesPerView: 1,
    centeredSlides: true,
    loop: true,
    loopedSlides: 6,
    spaceBetween: 16,
    navigation: {
      nextEl: ".hotel-gallery-main-arrow-next",
      prevEl: ".hotel-gallery-main-arrow-prev",
    },
  });

  const hotelGalleryMainSliderThumb = new Swiper(".hotel-gallery-main-slider-thumb", {
    loop: true,
    slidesPerView: 4,
    loopedSlides: 6,
    slideToClickedSlide: true,
    spaceBetween: 8,

    thumbs: {
      hotelGalleryMainSlider,
    },
  });

  hotelGalleryMainSlider.controller.control = hotelGalleryMainSliderThumb;
  hotelGalleryMainSliderThumb.controller.control = hotelGalleryMainSlider;

  // Инициализация промо-баннеров (может быть несколько экземпляров на странице)
  const promoBannerSliders = document.querySelectorAll(".promo-banner-slider");
  if (promoBannerSliders.length > 0) {
    promoBannerSliders.forEach((sliderEl) => {
      const slideCount = sliderEl.querySelectorAll(".swiper-slide").length;
      const single = slideCount <= 1;
      const paginationEl = sliderEl.parentElement.querySelector(".promo-banner-slider-pag");
      new Swiper(sliderEl, {
        slidesPerView: 1,
        spaceBetween: 32,
        // 1 слайд — без листания: без autoplay/loop, пагинацию скрываем.
        autoplay: single ? false : { delay: 3000 },
        loop: !single,
        allowTouchMove: !single,
        freeMode: !single,
        speed: 300,
        pagination: !single && paginationEl
          ? {
              el: paginationEl,
            }
          : false,
      });
    });
  }

  const bestOffersSlider = new Swiper(".best-offers-slider", {
    spaceBetween: 16,

    navigation: {
      nextEl: ".best-offers-arrow-next",
      prevEl: ".best-offers-arrow-prev",
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
      },
      769: {
        slidesPerView: 3,
      },
      1200: {
        slidesPerView: 4,
      },
    },
  });

  const popularHotelsSlider = new Swiper(".popular-hotels-slider", {
    spaceBetween: 16,
    loop: true,

    navigation: {
      nextEl: ".popular-hotels-arrow-next",
      prevEl: ".popular-hotels-arrow-prev",
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
      },
      769: {
        slidesPerView: 3,
      },
      1200: {
        slidesPerView: 4,
      },
    },
  });

  const awardsSliderSection = new Swiper(".awards-slider", {
    spaceBetween: 16,

    navigation: {
      nextEl: ".awards-arrow-next",
      prevEl: ".awards-arrow-prev",
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
      },
      769: {
        slidesPerView: 4,
      },
      1200: {
        slidesPerView: 6,
      },
    },
  });

  const projectsSectionSlider = new Swiper(".projects-section-slider", {
    spaceBetween: 16,

    navigation: {
      nextEl: ".projects-section-arrow-next",
      prevEl: ".projects-section-arrow-prev",
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
      },
      769: {
        slidesPerView: 3,
      },
      1200: {
        slidesPerView: 4,
      },
    },
  });

  const miceReviewsSliderEl = document.querySelector(".mice-reviews-slider");
  if (miceReviewsSliderEl) {
    const miceReviewsSection = miceReviewsSliderEl.closest(".mice-reviews");
    const prevBtn = miceReviewsSection?.querySelector(".mice-reviews-slider__prev") ?? null;
    const nextBtn = miceReviewsSection?.querySelector(".mice-reviews-slider__next") ?? null;

    new Swiper(miceReviewsSliderEl, {
      spaceBetween: 24,
      loop: false,
      watchOverflow: true,
      slidesPerView: 1,
      breakpoints: {
        1200: {
          slidesPerView: 2,
        },
      },
      ...(prevBtn && nextBtn
        ? {
            navigation: {
              prevEl: prevBtn,
              nextEl: nextBtn,
            },
          }
        : {}),
    });
  }
};
