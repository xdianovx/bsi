import Swiper from "swiper";

/**
 * Слайдер «Событийные туры» на главной + клиентская фильтрация по стране
 * (слайды отрисованы сервером, data-country на каждом).
 */
export const initPopularEventToursSlider = () => {
  const root = document.querySelector(".popular-event-tours__section");
  if (!root) return;

  const sliderEl = root.querySelector(".popular-event-tours-slider");
  const wrapper = root.querySelector(".popular-event-tours-slider .swiper-wrapper");
  const filter = root.querySelector(".popular-event-tours-filter");
  if (!sliderEl || !wrapper) return;

  const getSlidesPerView = (config) => {
    const bps = config?.breakpoints || {};
    const w = window.innerWidth;
    let spv = config.slidesPerView || 1;
    Object.keys(bps)
      .map((k) => parseInt(k, 10))
      .filter((n) => !Number.isNaN(n))
      .sort((a, b) => a - b)
      .forEach((bp) => {
        if (w >= bp && bps[bp]?.slidesPerView != null) spv = bps[bp].slidesPerView;
      });
    return spv === "auto" ? 1 : Number(spv) || 1;
  };

  const toggleControls = (instance, config) => {
    if (!instance) return;
    const visible = sliderEl.querySelectorAll(
      ".swiper-slide:not(.swiper-slide-hidden)",
    ).length;
    const spv = getSlidesPerView(config);
    if (visible <= spv) {
      instance.allowTouchMove = false;
      instance.navigation?.disable?.();
      sliderEl.classList.add("is-locked");
    } else {
      instance.allowTouchMove = true;
      instance.navigation?.enable?.();
      sliderEl.classList.remove("is-locked");
    }
  };

  if (sliderEl.swiper) {
    sliderEl.swiper.destroy(true, true);
  }

  const swiperConfig = {
    spaceBetween: 16,
    loop: false,
    watchOverflow: true,
    observer: true,
    observeParents: true,
    navigation: {
      nextEl: ".popular-event-tours-arrow-next",
      prevEl: ".popular-event-tours-arrow-prev",
    },
    breakpoints: {
      320: { slidesPerView: 1 },
      769: { slidesPerView: 3 },
      1200: { slidesPerView: 4 },
    },
  };

  const swiper = new Swiper(sliderEl, swiperConfig);
  toggleControls(swiper, swiperConfig);
  swiper.slideTo(0, 0);
  window.addEventListener("resize", () => toggleControls(swiper, swiperConfig));

  if (!filter) return;

  const setActive = (btn) => {
    filter.querySelectorAll(".js-promo-filter-btn").forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  };

  const filterClient = (countryValue) => {
    const slides = wrapper.querySelectorAll(".swiper-slide");
    if (!slides.length) return;
    const val = String(countryValue || "").trim();
    slides.forEach((slide) => {
      const c = (slide.getAttribute("data-country") || "").trim();
      const show = !val || c === val || c.toLowerCase() === val.toLowerCase();
      slide.style.display = show ? "" : "none";
      slide.classList.toggle("swiper-slide-hidden", !show);
    });
    swiper.update();
    swiper.slideTo(0, 0);
    swiper.update();
    toggleControls(swiper, swiperConfig);
  };

  filter.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-promo-filter-btn");
    if (!btn) return;
    e.preventDefault();
    setActive(btn);
    filterClient(btn.getAttribute("data-country") || "");
  });
};
