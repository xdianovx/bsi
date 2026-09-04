/**
 * Подбор тарифов на странице отеля.
 *
 * Все офферы приходят вместе со страницей в JSON рядом со списком номеров,
 * поэтому смена даты, длительности или питания перерисовывает строки без
 * запроса к серверу. Без JS остаётся серверная выдача ближайшего заезда.
 */

const VISIBLE_OFFERS = 3;

const escapeHtml = (value) =>
  String(value ?? "").replace(/[&<>"']/g, (char) => {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return map[char];
  });

const nightsLabel = (nights) => {
  const n = Math.abs(nights) % 100;
  const n1 = n % 10;
  if (n > 10 && n < 20) return `${nights} ночей`;
  if (n1 > 1 && n1 < 5) return `${nights} ночи`;
  if (n1 === 1) return `${nights} ночь`;
  return `${nights} ночей`;
};

const tariffLabel = (count) => {
  const n = Math.abs(count) % 100;
  const n1 = n % 10;
  if (n > 10 && n < 20) return `${count} тарифов`;
  if (n1 > 1 && n1 < 5) return `${count} тарифа`;
  if (n1 === 1) return `${count} тариф`;
  return `${count} тарифов`;
};

const offerRow = (offer, showBadge) => {
  const badge = showBadge && offer.status === "request" ? '<span class="hp-offer__badge">под запрос</span>' : "";

  const placement = offer.placementLabel ? `<span class="hp-offer__placement">${escapeHtml(offer.placementLabel)}</span>` : "";

  const cta = offer.url
    ? `<a class="btn btn-accent sm hp-offer__cta" href="${escapeHtml(offer.url)}" target="_blank" rel="nofollow noopener">Забронировать</a>`
    : '<a class="btn btn-white sm hp-offer__cta" href="#hotel-request">Оставить заявку</a>';

  return `
    <div class="hp-offer">
      <div class="hp-offer__terms">
        <span class="hp-offer__meal">${escapeHtml(offer.mealLabel)}</span>
        ${placement}
        ${badge}
      </div>
      <div class="hp-offer__price">
        <b>${escapeHtml(offer.price)}</b>
        <span>${nightsLabel(offer.nights)}</span>
      </div>
      ${cta}
    </div>
  `;
};

const renderRoom = (container, offers, showBadge) => {
  if (!offers.length) {
    container.innerHTML = '<p class="hp-room__empty">На выбранные даты мест нет</p>';
    return 0;
  }

  const row = (offer) => offerRow(offer, showBadge);
  const shown = offers.slice(0, VISIBLE_OFFERS).map(row).join("");
  const hidden = offers.slice(VISIBLE_OFFERS);

  container.innerHTML =
    shown +
    (hidden.length
      ? `<div class="hp-room__rest is-hidden">${hidden.map(row).join("")}</div>
         <button class="btn-expand hp-room__more" type="button">Ещё ${tariffLabel(hidden.length)}</button>`
      : "");

  return offers.length;
};

export const initHotelOffers = () => {
  const form = document.querySelector(".js-hotel-search");
  const list = document.querySelector(".js-hotel-rooms");
  const json = document.querySelector(".js-hotel-offers");

  if (!form || !list || !json) {
    return;
  }

  let data;
  try {
    data = JSON.parse(json.textContent || "[]");
  } catch (error) {
    return;
  }

  const byRoom = new Map(data.map((room) => [String(room.id), room.offers]));
  const note = form.querySelector(".js-hotel-search-note");

  const dateSelect = form.querySelector(".js-hotel-date");
  const nightsSelect = form.querySelector(".js-hotel-nights");
  const mealSelect = form.querySelector(".js-hotel-meal");

  /** Длительности, которые есть на выбранную дату. */
  const syncNights = () => {
    const date = dateSelect.value;
    const available = new Set();

    byRoom.forEach((offers) => {
      offers.forEach((offer) => {
        if (offer.date === date) available.add(offer.nights);
      });
    });

    let fallback = null;
    Array.from(nightsSelect.options).forEach((option) => {
      const ok = available.has(Number(option.value));
      option.disabled = !ok;
      if (ok && fallback === null) fallback = option.value;
    });

    if (nightsSelect.selectedOptions[0]?.disabled && fallback !== null) {
      nightsSelect.value = fallback;
    }
  };

  const apply = () => {
    const date = dateSelect.value;
    const nights = Number(nightsSelect.value);
    const meal = mealSelect ? mealSelect.value : "";
    let total = 0;

    list.querySelectorAll(".hp-room").forEach((room) => {
      const offers = (byRoom.get(String(room.dataset.room)) || [])
        .filter((offer) => offer.date === date && offer.nights === nights && (meal === "" || offer.meal === meal))
        .sort((a, b) => a.priceValue - b.priceValue);

      const container = room.querySelector(".js-room-offers");
      const count = renderRoom(container, offers, list.dataset.allRequest !== "1");

      // Номера без предложений уезжают в конец списка, но остаются на виду.
      room.classList.toggle("is-empty", count === 0);
      room.style.order = count ? "0" : "1";
      total += count;
    });

    if (note) {
      note.textContent = total ? `Найдено тарифов: ${total}` : "На эти условия предложений нет — измените дату или длительность";
      note.classList.toggle("is-empty", total === 0);
    }
  };

  dateSelect.addEventListener("change", () => {
    syncNights();
    apply();
  });
  nightsSelect.addEventListener("change", apply);
  mealSelect?.addEventListener("change", apply);

  list.addEventListener("click", (event) => {
    const button = event.target.closest(".hp-room__more");
    if (!button) return;

    button.previousElementSibling?.classList.remove("is-hidden");
    button.remove();
  });

  syncNights();
  apply();
};

/** Подсветка активного пункта в липкой навигации по секциям. */
export const initHotelNav = () => {
  const nav = document.querySelector(".js-hotel-nav");
  if (!nav) return;

  const links = Array.from(nav.querySelectorAll(".hp-nav__link"));
  const sections = links.map((link) => document.querySelector(link.getAttribute("href"))).filter(Boolean);

  if (!sections.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        links.forEach((link) => link.classList.toggle("is-active", link.getAttribute("href") === `#${entry.target.id}`));
      });
    },
    { rootMargin: "-120px 0px -70% 0px" }
  );

  sections.forEach((section) => observer.observe(section));
};

/** Раскрытие полного списка удобств. */
export const initHotelAmenities = () => {
  const button = document.querySelector(".js-hotel-amenities-more");
  const list = document.querySelector(".js-hotel-amenities");

  if (!button || !list) return;

  button.addEventListener("click", () => {
    list.querySelectorAll(".is-hidden").forEach((item) => item.classList.remove("is-hidden"));
    button.remove();
  });
};
