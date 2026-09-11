/**
 * Подбор тарифов на странице отеля.
 *
 * Цену заезда считает хаб: витрина отправляет дату и число ночей в
 * `bsi_hotels_api_quote`, а тот идёт в `/v1/hotels/{id}/quote`. Оттуда же
 * приходит ссылка брони с датами — у отеля она без них.
 *
 * До ответа хаба и без JS страница показывает то, что уже известно: варианты
 * питания с ценой за ночь из календаря номера.
 */

import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";

/** Дата в «2026-09-08» — в этом виде её знают хаб и календарь номера. */
const isoDate = (date) => {
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${date.getFullYear()}-${month}-${day}`;
};

/** Дата из «2026-09-08» без сдвига часового пояса. */
const toDate = (iso) => new Date(`${iso}T00:00:00`);

const escapeHtml = (value) =>
  String(value ?? "").replace(/[&<>"']/g, (char) => {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
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

/** Строка тарифа: условия слева, срок и сумма справа. */
const offerRow = (offer, nights) => {
  const placement = offer.placementLabel
    ? `<span class="hp-offer__placement">${escapeHtml(offer.placementLabel)}</span>`
    : "";

  /* Подтверждение приходит вместе с расчётом заезда: до выбора дат хаб знает
     его только по отдельным ночам. */
  const confirmation = offer.confirmationLabel
    ? `<span class="hp-badge hp-badge--${escapeHtml(offer.confirmation)}">${escapeHtml(offer.confirmationLabel)}</span>`
    : "";

  return `
    <div class="hp-offer">
      <div class="hp-offer__terms">
        <span class="hp-offer__meal">${escapeHtml(offer.mealLabel)}</span>
        ${placement}
        ${confirmation}
      </div>
      <div class="hp-offer__price">
        <b>${escapeHtml(offer.price)}</b>
        <span>${nightsLabel(nights)}</span>
      </div>
    </div>
  `;
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

  const hotel = form.dataset.hotel;
  const note = form.querySelector(".js-hotel-search-note");
  const dateSelect = form.querySelector(".js-hotel-date");
  const nightsSelect = form.querySelector(".js-hotel-nights");
  const mealSelect = form.querySelector(".js-hotel-meal");

  const byRoom = new Map(data.map((room) => [String(room.id), room]));

  const state = {
    date: form.dataset.defaultDate || dateSelect.value,
    nights: Number(form.dataset.defaultNights) || Number(nightsSelect.value),
    meal: "",
  };

  /* Каждый расчёт получает номер: пока ходим к хабу, пользователь успевает
     выбрать другую дату, и старый ответ рисовать нельзя. */
  let request = 0;

  /** Что известно без запроса: цена ночи по каждому питанию. */
  const renderKnown = (room, container) => {
    const meals = (room?.meals || []).filter((meal) => state.meal === "" || meal.code === state.meal);

    if (!meals.length) {
      container.innerHTML = '<p class="hp-room__empty">На выбранное питание тарифов нет</p>';
      return 0;
    }

    container.innerHTML = meals.map((meal) => `
      <div class="hp-offer">
        <div class="hp-offer__terms">
          <span class="hp-offer__meal">${escapeHtml(meal.label)}</span>
          ${meal.placement_label ? `<span class="hp-offer__placement">${escapeHtml(meal.placement_label)}</span>` : ""}
          ${meal.instant_nights ? `<span class="hp-offer__instant">подтверждение сразу: ${meal.instant_nights}${meal.nights ? ` из ${meal.nights}` : ""}</span>` : ""}
        </div>
        ${meal.price ? `<div class="hp-offer__price"><b>${escapeHtml(meal.price)}</b><span>за ночь</span></div>` : ""}
      </div>
    `).join("");

    return meals.length;
  };

  /** Ответ хаба: точные суммы за выбранный заезд. */
  const renderQuotes = (quotes, nights, booking) => {
    const byRoomId = new Map();

    quotes
      .filter((quote) => state.meal === "" || quote.meal === state.meal)
      .forEach((quote) => {
        if (!byRoomId.has(quote.room)) byRoomId.set(quote.room, []);
        byRoomId.get(quote.room).push(quote);
      });

    let total = 0;

    list.querySelectorAll(".hp-room").forEach((room) => {
      const rows = byRoomId.get(String(room.dataset.room)) || [];
      const container = room.querySelector(".js-room-offers");

      if (rows.length) {
        container.innerHTML = rows.map((row) => offerRow(row, nights)).join("");
        total += rows.length;
      } else {
        container.innerHTML = '<p class="hp-room__empty">На эти даты мест нет</p>';
      }

      // Номера без предложений уезжают в конец списка, но остаются на виду.
      room.classList.toggle("is-empty", rows.length === 0);
      room.style.order = rows.length ? "0" : "1";

      // Ссылка брони от хаба уже содержит даты выбранного заезда.
      if (booking) {
        const cta = room.querySelector(".hp-room__cta");
        if (cta) {
          cta.href = booking;
          cta.target = "_blank";
          cta.rel = "nofollow noopener";
        }
      }
    });

    return total;
  };

  const apply = async () => {
    const ticket = ++request;
    const nights = state.nights;

    list.classList.add("is-loading");
    if (note) {
      note.textContent = "Считаем цены…";
      note.classList.remove("is-empty");
    }

    // Пока ждём хаб, показываем известные цены за ночь — не пустой список.
    list.querySelectorAll(".hp-room").forEach((room) => {
      renderKnown(byRoom.get(String(room.dataset.room)), room.querySelector(".js-room-offers"));
    });

    let payload = null;
    try {
      const body = new URLSearchParams({
        action: "bsi_hotels_api_quote",
        hotel,
        check_in: state.date,
        nights: String(nights),
      });

      const ajaxUrl = window.ajax?.url || window.ajaxurl || "/wp-admin/admin-ajax.php";

      const response = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
      });

      payload = await response.json();
    } catch (error) {
      payload = null;
    }

    // Пока считали, пользователь выбрал другое — этот ответ уже не нужен.
    if (ticket !== request) return;

    list.classList.remove("is-loading");

    if (!payload?.success) {
      if (note) {
        note.textContent = payload?.data?.message || "Не удалось получить цены — покажем их по запросу";
        note.classList.add("is-empty");
      }
      return;
    }

    const total = renderQuotes(payload.data.quotes || [], payload.data.nights || nights, payload.data.booking || "");

    if (note) {
      note.textContent = total
        ? `Нашли вариантов: ${total} · ${nightsLabel(payload.data.nights || nights)} с ${dayLabel(state.date)}`
        : "На эти даты мест нет — выберите другую дату или длительность";
      note.classList.toggle("is-empty", total === 0);
    }
  };

  /** «2026-09-10» → «10 сентября». */
  const dayLabel = (iso) => toDate(iso).toLocaleDateString("ru-RU", { day: "numeric", month: "long" });

  dateSelect.addEventListener("change", () => {
    state.date = dateSelect.value;
    apply();
  });

  nightsSelect.addEventListener("change", () => {
    state.nights = Number(nightsSelect.value);
    apply();
  });

  mealSelect?.addEventListener("change", () => {
    state.meal = mealSelect.value;
    apply();
  });

  const calendar = initCalendar(form, state, apply);

  initReset(form, { dateSelect, nightsSelect, mealSelect, calendar, state, apply });

  apply();
};

/**
 * Календарь заезда.
 *
 * Дни, которые хаб уже посчитал, подписаны ценой за ночь и числом свободных
 * номеров. Остальные дни не заняты — про них просто не спрашивали, поэтому они
 * тоже выбираются: цену на такой день считает `/quote`.
 */
const initCalendar = (form, state, apply) => {
  const field = form.querySelector(".js-hotel-range-field");
  const input = form.querySelector(".js-hotel-range");
  const dateField = form.querySelector(".js-hotel-date-field");

  if (!field || !input) {
    return null;
  }

  let prices = {};
  try {
    prices = JSON.parse(form.dataset.prices || "{}");
  } catch (error) {
    prices = {};
  }

  field.hidden = false;
  if (dateField) dateField.hidden = true;

  return flatpickr(input, {
    locale: Russian,
    dateFormat: "d.m.Y",
    minDate: "today",
    defaultDate: toDate(state.date),
    showMonths: window.innerWidth > 900 ? 2 : 1,
    onDayCreate: (_selected, _str, _instance, dayElem) => {
      const known = prices[isoDate(dayElem.dateObj)];
      if (!known) return;

      dayElem.classList.add("is-priced");

      const label = document.createElement("span");
      label.className = "hp-cal__price";
      label.textContent = known.price;
      dayElem.appendChild(label);
    },
    onChange: (selected) => {
      if (!selected.length) return;

      state.date = isoDate(selected[0]);
      apply();
    },
  });
};

/**
 * Сброс подбора к состоянию, с которым страница открылась: ближайший заезд,
 * три ночи и любое питание.
 */
const initReset = (form, { dateSelect, nightsSelect, mealSelect, calendar, state, apply }) => {
  const button = form.querySelector(".js-hotel-reset");
  if (!button) return;

  const initial = {
    date: form.dataset.defaultDate || dateSelect.value,
    nights: Number(form.dataset.defaultNights) || Number(nightsSelect.value),
    meal: "",
  };

  const isInitial = () => state.date === initial.date
    && state.nights === initial.nights
    && state.meal === initial.meal;

  const sync = () => {
    button.hidden = isInitial();
  };

  button.addEventListener("click", () => {
    state.date = initial.date;
    state.nights = initial.nights;
    state.meal = initial.meal;

    dateSelect.value = initial.date;
    nightsSelect.value = String(initial.nights);
    if (mealSelect) mealSelect.value = "";
    calendar?.setDate(toDate(initial.date), false);

    apply();
    sync();
  });

  // Кнопка появляется, только когда отбор отличается от исходного.
  form.addEventListener("change", sync);
  document.addEventListener("click", () => window.setTimeout(sync, 0));

  sync();
};

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
