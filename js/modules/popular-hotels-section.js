// popular-hotel-slider.js
import Swiper from "swiper";

export const initPopularHotelsSlider = () => {
  const root = document.querySelector(".popular-hotels__section");
  if (!root) return;

  const sliderEl = root.querySelector(".popular-hotels-slider");
  const wrapper = root.querySelector(".popular-hotels-slider .swiper-wrapper");
  const filter = root.querySelector(".popular-hotels-filter");
  if (!sliderEl || !wrapper) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;

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

    const slidesCount = sliderEl.querySelectorAll(".swiper-slide").length;
    const spv = getSlidesPerView(config);

    if (slidesCount <= spv) {
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
      nextEl: ".popular-hotels-arrow-next",
      prevEl: ".popular-hotels-arrow-prev",
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

  const onResize = () => toggleControls(swiper, swiperConfig);
  window.addEventListener("resize", onResize);

  if (!filter || !ajaxUrl) return;

  const setActive = (btn) => {
    filter.querySelectorAll(".js-promo-filter-btn").forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  };

  const setLoading = (on) => root.classList.toggle("is-loading", !!on);

  const load = async (countryId) => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "popular_hotels_by_country");
      body.set("country_id", String(countryId || ""));

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      wrapper.innerHTML = json.data.html || "";

      swiper.update();
      swiper.slideTo(0, 0);
      swiper.update();

      toggleControls(swiper, swiperConfig);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  filter.addEventListener("click", (e) => {
    const btn = e.target.closest(".js-promo-filter-btn");
    if (!btn) return;

    e.preventDefault();
    setActive(btn);

    const countryId = btn.getAttribute("data-country") || "";
    load(countryId);
  });
};
