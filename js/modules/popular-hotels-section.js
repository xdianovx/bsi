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

  const swiper = new Swiper(".popular-hotels-slider", {
    spaceBetween: 16,
    navigation: {
      nextEl: ".popular-hotels-arrow-next",
      prevEl: ".popular-hotels-arrow-prev",
    },
    breakpoints: {
      320: { slidesPerView: 1 },
      769: { slidesPerView: 3 },
      1200: { slidesPerView: 4 },
    },
  });

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
      swiper.slideToLoop(0, 0);
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
