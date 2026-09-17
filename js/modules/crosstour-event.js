/**
 * Crosstour (Самотур) — живые данные на странице события.
 *
 * Если событие зарезолвлено в Само (на сервере выставлен [data-crosstour-event]),
 * подтягиваем цену «от», список отелей и доступные даты через AJAX
 * (bsi_samo&method=crosstour_event) и обновляем разметку. Приоритет Само > ручное:
 * при наличии данных перекрываем ручные значения, иначе остаётся ручной fallback.
 *
 * [data-crosstour-builder] — конструктор; дата/ночи из ссылки — выбор по умолчанию.
 * Сетка сочетаний (crosstour_matrix) → чипы «заезд» и «ночи» → отели выбранного
 * сочетания (сразу из сетки, полный набор номеров — crosstour_slot).
 */

const escapeHtml = (s) =>
  String(s).replace(
    /[&<>"']/g,
    (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c],
  );

const fmtPrice = (n) => Number(n).toLocaleString("ru-RU");

const fmtDateYmd = (ymd) => {
  const s = String(ymd);
  if (s.length !== 8) return s;
  return `${s.slice(6, 8)}.${s.slice(4, 6)}.${s.slice(0, 4)}`;
};

const renderPrice = (offer) => {
  const rub = Number(offer.price_rub);
  if (!rub || rub <= 0) return;

  const orig = offer.price_original;
  const cur = offer.price_currency;

  document.querySelectorAll("[data-crosstour-price]").forEach((el) => {
    el.classList.add("js-event-price");
    el.dataset.priceRub = String(rub);
    el.dataset.hasFrom = "true";
    delete el.dataset.priceSuffix;
    if (orig && cur) {
      el.dataset.priceOriginal = String(orig);
      el.dataset.priceCurrency = String(cur);
    } else {
      delete el.dataset.priceOriginal;
      delete el.dataset.priceCurrency;
    }
    el.textContent = `от ${fmtPrice(rub)} ₽`;
  });

  // Пусть переключатель валют пересчитает (и применит сохранённое предпочтение).
  document.dispatchEvent(new CustomEvent("education:content-updated"));
};

const revealManualAccommodation = () => {
  const manual = document.querySelector("[data-manual-accommodation]");
  if (manual) manual.hidden = false;
};

const renderHotels = (offer) => {
  const wrap = document.querySelector("[data-crosstour-hotels]");
  const list = document.querySelector("[data-crosstour-hotels-list]");
  if (!wrap || !list) {
    revealManualAccommodation();
    return;
  }

  const hotels = Array.isArray(offer.hotels) ? offer.hotels : [];
  if (!hotels.length) {
    // Само без отелей → возвращаем ручной блок (fallback).
    revealManualAccommodation();
    return;
  }

  const bookingUrl = offer.booking_url || "";
  const arrow = `<svg class="single-event__accommodation-card-link-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>`;

  list.innerHTML = hotels
    .map((h) => {
      const star = h.star
        ? `<span class="single-event__accommodation-card-stars" aria-label="${escapeHtml(h.star)}">
             <span class="single-event__accommodation-card-stars-num">${escapeHtml(h.star)}</span>
           </span>`
        : "";
      const origAttr =
        h.price_original && h.price_currency
          ? ` data-price-original="${escapeHtml(h.price_original)}" data-price-currency="${escapeHtml(h.price_currency)}"`
          : "";
      const priceEl =
        h.price_rub && Number(h.price_rub) > 0
          ? `<span class="single-event__accommodation-card-price numfont js-event-price" data-price-rub="${Number(h.price_rub)}" data-has-from="true"${origAttr}>от ${fmtPrice(h.price_rub)} ₽</span>`
          : `<span class="single-event__accommodation-card-price numfont">Цена по запросу</span>`;
      // Своя актуальная ссылка на номер (с DOLOAD/датой/ночами); иначе общая тура.
      let hotelUrl = h.booking_url || bookingUrl;
      if (!h.booking_url && bookingUrl && h.hotel_key && Number(h.hotel_key) > 0) {
        hotelUrl += `&HOTELS=${Number(h.hotel_key)}`;
      }
      const bookBtn = hotelUrl
        ? `<a class="single-event__accommodation-card-link" href="${escapeHtml(hotelUrl)}" target="_blank" rel="nofollow noopener"><span>Забронировать</span>${arrow}</a>`
        : "";
      const meta = [h.room, h.meal].filter(Boolean).map(escapeHtml).join(" · ");
      const descr = meta
        ? `<p class="single-event__accommodation-card-descr">${meta}</p>`
        : "";
      return `<li class="single-event__accommodation-card">
        <div class="single-event__accommodation-card-head">
          ${star}
          <h3 class="single-event__accommodation-card-name">${escapeHtml(h.name)}</h3>
        </div>
        ${descr}
        <div class="single-event__accommodation-card-foot">
          ${priceEl}
          ${bookBtn}
        </div>
      </li>`;
    })
    .join("");

  wrap.hidden = false;

  // Само > ручное: при наличии само-списка прячем ручной блок размещения.
  const manual = document.querySelector("[data-manual-accommodation]");
  if (manual) manual.hidden = true;

  // Пересчёт цен отелей переключателем валют.
  document.dispatchEvent(new CustomEvent("education:content-updated"));
};

const renderDates = (offer) => {
  const el = document.querySelector("[data-crosstour-dates]");
  if (!el) return;

  const dates = Array.isArray(offer.dates) ? offer.dates : [];
  if (!dates.length) return;

  const first = fmtDateYmd(dates[0]);
  const last = fmtDateYmd(dates[dates.length - 1]);
  const range = first === last ? first : `${first} — ${last}`;
  el.textContent = `Доступные даты вылета: ${range}`;
  el.hidden = false;
};

const postSamo = async (ajaxUrl, params) => {
  const body = new URLSearchParams({ action: "bsi_samo", ...params });
  const res = await fetch(ajaxUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: body.toString(),
    credentials: "same-origin",
  });
  return res.json();
};

/* ── Конструктор тура: заезд × ночи × отель ─────────────────────────────── */

const WEEKDAYS = ["вс", "пн", "вт", "ср", "чт", "пт", "сб"];

const ymdParts = (ymd) => {
  const s = String(ymd);
  return { y: +s.slice(0, 4), m: +s.slice(4, 6), d: +s.slice(6, 8) };
};

const fmtShortDate = (ymd) => {
  const { m, d } = ymdParts(ymd);
  return `${String(d).padStart(2, "0")}.${String(m).padStart(2, "0")}`;
};

const weekday = (ymd) => {
  const { y, m, d } = ymdParts(ymd);
  return WEEKDAYS[new Date(y, m - 1, d).getDay()];
};

const pluralNights = (n) => {
  const m10 = n % 10;
  const m100 = n % 100;
  if (m10 === 1 && m100 !== 11) return "ночь";
  if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) return "ночи";
  return "ночей";
};

const priceSpan = (item, cls) => {
  const rub = Number(item.price_rub);
  if (!rub || rub <= 0) return `<span class="${cls}">по запросу</span>`;
  const orig =
    item.price_original && item.price_currency
      ? ` data-price-original="${escapeHtml(item.price_original)}" data-price-currency="${escapeHtml(item.price_currency)}"`
      : "";
  return `<span class="${cls} numfont js-event-price" data-price-rub="${rub}" data-has-from="true"${orig}>от ${fmtPrice(rub)} ₽</span>`;
};

const cheapest = (list) =>
  list.reduce((best, c) => {
    if (!c.price_rub) return best;
    if (!best || !best.price_rub || c.price_rub < best.price_rub) return c;
    return best;
  }, null) || list[0];

const removeBuilderSkeletons = () =>
  document.querySelectorAll("[data-builder-skeleton]").forEach((el) => el.remove());

// Две секции: даты/ночи (section) и отели ([data-crosstour-hotels]).
const initBuilder = async (section, eventId, ajaxUrl) => {
  const hotelsSection = document.querySelector("[data-crosstour-hotels]");
  const controls = section.querySelector("[data-builder-controls]");
  const datesEl = section.querySelector("[data-builder-dates]");
  const nightsEl = section.querySelector("[data-builder-nights]");
  const starsGroup = hotelsSection?.querySelector('[data-builder-group="stars"]');
  const starsEl = hotelsSection?.querySelector("[data-builder-stars]");
  const summaryEl = section.querySelector("[data-builder-summary]");

  const json = await postSamo(ajaxUrl, { method: "crosstour_matrix", event_id: eventId });
  removeBuilderSkeletons();
  const combos = json?.success && json.data?.samo ? json.data.combos || [] : [];
  if (!combos.length || !hotelsSection) {
    console.debug("[crosstour] конструктор: Само не вернул сочетаний", json);
    // Прежний поток сам откроет секцию отелей, если найдёт их.
    section.hidden = true;
    if (hotelsSection) hotelsSection.hidden = true;
    return false;
  }

  renderPrice(json.data);

  const dates = [...new Set(combos.map((c) => c.date))];
  // Дата/ночи из ссылки менеджера, иначе самое дешёвое сочетание.
  const preferred = json.data.preferred;
  const start =
    (preferred &&
      combos.find((c) => c.date === preferred.date && c.nights === preferred.nights)) ||
    cheapest(combos);
  const state = { date: start.date, nights: start.nights, star: "" };
  const slotCache = new Map();
  const slotKey = (date, nights) => `${date}_${nights}`;
  const currentCombo = () =>
    combos.find((c) => c.date === state.date && c.nights === state.nights);

  const renderDates = () => {
    datesEl.innerHTML = dates
      .map(
        (d) => `<button type="button" class="ui-choice${d === state.date ? " is-active" : ""}" data-date="${d}">
          <span class="ui-choice__label numfont">${fmtShortDate(d)}</span>
          <span class="ui-choice__sub">${weekday(d)}</span>
        </button>`,
      )
      .join("");
  };

  const renderNights = () => {
    nightsEl.innerHTML = combos
      .filter((c) => c.date === state.date)
      .map(
        (c) => `<button type="button" class="ui-choice${c.nights === state.nights ? " is-active" : ""}" data-nights="${c.nights}">
          <span class="ui-choice__label"><span class="numfont">${c.nights}</span> ${pluralNights(c.nights)}</span>
          ${priceSpan(c, "ui-choice__sub")}
        </button>`,
      )
      .join("");
  };

  const renderSummary = (combo) => {
    summaryEl.textContent =
      `Заезд ${fmtShortDate(combo.date)} (${weekday(combo.date)}) — ` +
      `выезд ${fmtShortDate(combo.checkout)} (${weekday(combo.checkout)}), ` +
      `${combo.nights} ${pluralNights(combo.nights)}. Цена за человека при размещении вдвоём.`;
  };

  const renderStars = (hotels) => {
    if (!starsGroup || !starsEl) return;
    const stars = [...new Set(hotels.map((h) => h.star).filter(Boolean))].sort();
    if (!stars.includes(state.star)) state.star = "";
    starsGroup.hidden = stars.length < 2;
    starsEl.innerHTML = ["", ...stars]
      .map(
        (st) => `<button type="button" class="ui-choice${st === state.star ? " is-active" : ""}" data-star="${escapeHtml(st)}">
          <span class="ui-choice__label">${st ? escapeHtml(st) : "Все"}</span>
        </button>`,
      )
      .join("");
  };

  const renderSlotHotels = (hotels) => {
    renderStars(hotels);
    const shown = state.star ? hotels.filter((h) => h.star === state.star) : hotels;
    renderHotels({ hotels: shown, booking_url: "" });
  };

  const showSlot = async () => {
    const combo = currentCombo();
    if (!combo) return;
    const key = slotKey(combo.date, combo.nights);
    renderSummary(combo);

    // Сразу — самый дешёвый номер каждого отеля из сетки, затем полный набор номеров.
    renderSlotHotels(slotCache.get(key) || combo.hotels || []);
    if (slotCache.has(key)) return;

    try {
      const res = await postSamo(ajaxUrl, {
        method: "crosstour_slot",
        event_id: eventId,
        date: combo.date,
        nights: String(combo.nights),
      });
      const hotels = res?.success ? res.data?.offer?.hotels || [] : [];
      if (!hotels.length) return;
      slotCache.set(key, hotels);
      if (slotKey(state.date, state.nights) === key) renderSlotHotels(hotels);
    } catch (e) {
      console.warn("[crosstour] конструктор: номера не догрузились", e);
    }
  };

  const renderAll = () => {
    renderDates();
    renderNights();
    showSlot();
  };

  const onChoiceClick = (e) => {
    const btn = e.target.closest(".ui-choice");
    if (!btn || btn.classList.contains("is-active")) return;

    if (btn.dataset.date) {
      state.date = btn.dataset.date;
      const forDate = combos.filter((c) => c.date === state.date);
      if (!forDate.some((c) => c.nights === state.nights)) {
        state.nights = cheapest(forDate).nights;
      }
      renderAll();
    } else if (btn.dataset.nights) {
      state.nights = Number(btn.dataset.nights);
      renderAll();
    } else if (btn.dataset.star !== undefined) {
      state.star = btn.dataset.star;
      const combo = currentCombo();
      const key = slotKey(combo.date, combo.nights);
      renderSlotHotels(slotCache.get(key) || combo.hotels || []);
    }
  };
  controls.addEventListener("click", onChoiceClick);
  starsGroup?.addEventListener("click", onChoiceClick);

  // Одно сочетание — выбирать нечего, секция дат скрыта, остаётся секция отелей.
  const single = combos.length < 2;
  controls.hidden = single;
  section.hidden = single;
  renderAll();

  // Кнопки «Забронировать» ведут в конструктор, а не в общий поиск Само.
  const target = single ? hotelsSection : section;
  document.querySelectorAll("[data-crosstour-book-link]").forEach((a) => {
    a.href = `#${target.id}`;
    a.removeAttribute("target");
    a.removeAttribute("rel");
  });
  return true;
};

export const initCrosstourEvent = async () => {
  const root = document.querySelector("[data-crosstour-event]");
  if (!root) {
    console.debug("[crosstour] нет [data-crosstour-event] — событие не связано с Само");
    return;
  }

  const eventId = root.dataset.crosstourEvent;
  if (!eventId) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) {
    console.warn("[crosstour] ajaxUrl не найден (window.ajax.url / window.ajaxurl)");
    return;
  }

  const builder = document.querySelector("[data-crosstour-builder]");

  try {
    // Сетки нет (продукт не в crosstour / сбой) → прежний список отелей ниже.
    if (builder && (await initBuilder(builder, eventId, ajaxUrl))) {
      return;
    }

    console.debug("[crosstour] запрос", { eventId, ajaxUrl });
    const json = await postSamo(ajaxUrl, { method: "crosstour_event", event_id: eventId });
    console.debug("[crosstour] ответ", json);

    if (!json || !json.success || !json.data || !json.data.samo) {
      console.debug("[crosstour] Само не вернул данные (samo=false / ошибка)");
      revealManualAccommodation();
      return;
    }

    const offer = json.data.offer || {};
    console.debug("[crosstour] offer", offer);
    renderPrice(offer);
    renderHotels(offer);
    renderDates(offer);
  } catch (e) {
    console.warn("[crosstour] ошибка запроса", e);
    if (builder?.querySelector("[data-builder-skeleton]")) {
      removeBuilderSkeletons();
      builder.hidden = true;
      const hotels = document.querySelector("[data-crosstour-hotels]");
      if (hotels) hotels.hidden = true;
    }
    revealManualAccommodation();
  }
};
