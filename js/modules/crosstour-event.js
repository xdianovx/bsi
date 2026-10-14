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

import { withEventUtm } from "./event-offer.js";

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

/* Пакеты проживания событийного тура: в Само «отель» = пакет («…, пакет Эконом 3-4*»).
   Ищем карточку пакета из админки по слову в названии и подставляем цену, номер и питание. */
const PACKAGE_RULES = [
  ["vip", /vip/i],
  ["econom", /эконом/i],
  ["standard", /стандарт/i],
  ["five", /\b5\s*\*|5★|пакет\s*5/i],
];
const STAR_SVG = `<svg class="single-event__star" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>`;
const packageKey = (name) => (PACKAGE_RULES.find(([, rx]) => rx.test(name)) || [""])[0];

const renderPackages = (hotels) => {
  const box = document.querySelector("[data-packages]");
  if (!box) return false;
  const best = new Map();
  hotels.forEach((h) => {
    const key = packageKey(h.name || "") || `samo-${h.hotel_key || h.name}`;
    const prev = best.get(key);
    if (!prev || (h.price_rub && (!prev.price_rub || h.price_rub < prev.price_rub))) best.set(key, h);
  });
  // Пакеты, которых нет в админке, — карточкой по образцу первой.
  best.forEach((h, key) => {
    if (box.querySelector(`[data-offer-package="${CSS.escape(key)}"]`)) return;
    const title = (h.name || "").replace(/^.*пакет\s*/i, "").replace(/\s*\d[\d\-–+]*\*\s*$/, "") || h.name;
    const stars = (String(h.name || "").match(/(\d[\d\-–+]*)\*\s*$/) || [])[1] || String(h.star || "").replace("*", "");
    box.insertAdjacentHTML(
      "beforeend",
      `<label class="ui-option ui-option--top single-event__package-option" data-offer-package="${escapeHtml(key)}" data-package-title="${escapeHtml(title)}" data-package-stars="${escapeHtml(stars)}">
        <input type="radio" class="ui-checkbox__input" name="event_tour_package" value="${escapeHtml(key)}">
        <span class="ui-checkbox__mark ui-checkbox__mark--mono"></span>
        <span class="ui-option__body">
          <span class="ui-option__label">${escapeHtml(title)}</span>
          <span class="ui-option__sub single-event__package-meta"><span class="numfont">${escapeHtml(stars)}</span>${STAR_SVG}<span data-package-meta></span></span>
        </span>
        <span class="ui-option__price numfont" data-package-price>по запросу</span>
      </label>`,
    );
  });
  box.querySelectorAll("[data-offer-package]").forEach((opt) => {
    const h = best.get(opt.dataset.offerPackage);
    // Ссылка Само на этот пакет: своя у номера, иначе общая + HOTELS=ключ пакета.
    let url = h?.booking_url || "";
    if (!url && h?.hotel_key && Number(h.hotel_key) > 0) {
      const base = document.querySelector("[data-crosstour-builder]")?.dataset.samoUrl || "";
      if (base) {
        try {
          const u = new URL(base);
          u.searchParams.delete("HOTELS_ANY");
          u.searchParams.set("HOTELS", String(Number(h.hotel_key)));
          url = u.toString();
        } catch (_e) {
          url = "";
        }
      }
    }
    opt.dataset.packageUrl = url;
    const priceEl = opt.querySelector("[data-package-price]");
    const metaEl = opt.querySelector("[data-package-meta]");
    if (metaEl) metaEl.textContent = h ? ` ${[h.room, h.meal ? String(h.meal).replace(/^[A-Z]{2}\s*-\s*/, "") : ""].filter(Boolean).join(" · ")}` : "";
    if (!priceEl) return;
    if (h && Number(h.price_rub) > 0) {
      priceEl.classList.add("js-event-price");
      priceEl.dataset.priceRub = String(Number(h.price_rub));
      priceEl.dataset.hasFrom = "true";
      if (h.price_original && h.price_currency) {
        priceEl.dataset.priceOriginal = String(h.price_original);
        priceEl.dataset.priceCurrency = String(h.price_currency);
      }
      priceEl.textContent = `от ${fmtPrice(h.price_rub)} ₽`;
    } else {
      priceEl.classList.remove("js-event-price");
      ["priceRub", "priceOriginal", "priceCurrency", "hasFrom"].forEach((k) => delete priceEl.dataset[k]);
      priceEl.textContent = h ? "по запросу" : "нет на эти даты";
    }
  });
  document.dispatchEvent(new CustomEvent("crosstour:packages"));
  document.dispatchEvent(new CustomEvent("education:content-updated"));
  return true;
};

const renderHotels = (offer) => {
  if (renderPackages(Array.isArray(offer.hotels) ? offer.hotels : [])) return;
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
        ? `<a class="single-event__accommodation-card-link" href="${escapeHtml(withEventUtm(hotelUrl, "hotel"))}" target="_blank" rel="nofollow noopener"><span>Забронировать</span>${arrow}</a>`
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

const MONTHS_SHORT = ["янв", "фев", "мар", "апр", "мая", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];
const MONTHS_LONG = ["января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"];
const fmtDayMonth = (ymd) => {
  const { m, d } = ymdParts(ymd);
  return `${d} ${MONTHS_SHORT[m - 1]}`;
};
const fmtDayMonthLong = (ymd) => {
  const { m, d } = ymdParts(ymd);
  return `${d} ${MONTHS_LONG[m - 1]}`;
};
// «5–7 марта» / «30 марта – 2 апреля»
const tripRange = (from, to) => {
  const a = ymdParts(from);
  const b = ymdParts(to);
  return a.m === b.m ? `${a.d}–${b.d} ${MONTHS_LONG[b.m - 1]}` : `${fmtDayMonthLong(from)} – ${fmtDayMonthLong(to)}`;
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

// Само не ответил или тур снят: «Цены тура сейчас недоступны», в сайдбаре — заявка вместо брони.
const showBuilderError = (section) => {
  removeBuilderSkeletons();
  const controls = section.querySelector("[data-builder-controls]");
  const error = section.querySelector("[data-builder-error]");
  if (controls) controls.hidden = true;
  if (error) error.hidden = false;
  document.querySelectorAll('[data-offer-summary-price="tour"]').forEach((el) => {
    el.classList.remove("js-event-price");
    el.textContent = "Цена по запросу";
  });
  const link = document.querySelector("[data-offer-tour-link]");
  if (link) {
    link.textContent = "Оставить заявку";
    link.setAttribute("data-no-url", "");
    link.href = "#";
  }
};

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
    showBuilderError(section);
    return true;
  }

  renderPrice(json.data);

  // Дата/ночи из ссылки менеджера, иначе самое дешёвое сочетание основного тура.
  const preferred = json.data.preferred;
  const mainCombos = combos.filter((c) => !c.concert);
  const start =
    (preferred &&
      mainCombos.find((c) => c.date === preferred.date && c.nights === preferred.nights)) ||
    cheapest(mainCombos.length ? mainCombos : combos);
  const state = { date: start.date, nights: start.nights, star: "" };
  const slotCache = new Map();
  // Туров Само может быть несколько (по одному на концерт) — заезды у них одинаковые,
  // поэтому выбор идёт внутри тура выбранного концерта (pool).
  const pool = () => {
    const own = state.concert ? combos.filter((c) => c.concert === state.concert) : [];
    return own.length ? own : mainCombos.length ? mainCombos : combos;
  };
  const slotKey = (date, nights) => `${pool()[0]?.tour || 0}_${date}_${nights}`;
  const currentCombo = () =>
    pool().find((c) => c.date === state.date && c.nights === state.nights);

  // Страница события (event-offer.js): под заездом — на какой концерт он попадает
  // (ближайший концерт после заезда; тур на 6 марта стартует 5-го).
  let concerts = [];
  try {
    const offerJson = document.querySelector("[data-event-offer-json]");
    concerts = offerJson
      ? (JSON.parse(offerJson.textContent || "{}").dates || [])
          .filter((c) => /^\d{4}-\d{2}-\d{2}$/.test(c.key))
          .map((c) => ({ ymd: c.key.replace(/-/g, ""), label: c.label }))
          .sort((a, b) => a.ymd.localeCompare(b.ymd))
      : [];
  } catch (_e) {
    concerts = [];
  }
  const concertFor = (ymd) => concerts.find((c) => c.ymd > ymd) || concerts.find((c) => c.ymd === ymd);

  // Концерт выбран выше, в блоке «Выберите дату» — показываем только заезды на него.
  // Нет заездов на этот концерт — показываем все, чтобы не оставить пустой выбор.
  const activeConcert = document.querySelector("[data-offer-date].is-active")?.dataset.offerDate || "";
  state.concert = /^\d{4}-\d{2}-\d{2}$/.test(activeConcert) ? activeConcert.replace(/-/g, "") : "";
  const visibleDates = () => {
    const list = pool();
    const dates = [...new Set(list.map((c) => c.date))];
    // Свой тур у концерта — его заезды все подходят; общий тур — по ближайшему концерту.
    if (!state.concert || !concerts.length || list[0]?.concert) return dates;
    const own = dates.filter((d) => concertFor(d)?.ymd === state.concert);
    return own.length ? own : dates;
  };
  const pickForConcert = () => {
    const allowed = visibleDates();
    if (allowed.includes(state.date) && currentCombo()) return;
    const preferredCombo = cheapest(pool().filter((c) => allowed.includes(c.date)));
    state.date = preferredCombo.date;
    state.nights = preferredCombo.nights;
  };

  // Ссылка на Само с датой заезда и ночами — для кнопки «Забронировать тур» в сайдбаре.
  const samoBase = section.dataset.samoUrl || "";
  const emitCombo = (combo) => {
    let url = "";
    // У сочетания свой тур Само, если их несколько («Ещё туры Само» в админке).
    const base = combo?.samo_url || samoBase;
    if (base && combo) {
      try {
        const u = new URL(base);
        u.searchParams.set("CHECKIN_BEG", combo.date);
        u.searchParams.set("CHECKIN_END", combo.date);
        u.searchParams.set("NIGHTS_FROM", combo.nights);
        u.searchParams.set("NIGHTS_TILL", combo.nights);
        url = u.toString();
      } catch (_e) {
        url = base;
      }
    }
    const nightsLabel = combo ? `${combo.nights} ${pluralNights(combo.nights)}` : "";
    const trip = combo ? `${tripRange(combo.date, combo.checkout)} · ${nightsLabel}` : "";
    document.dispatchEvent(new CustomEvent("crosstour:combo", { detail: { url, trip, nightsLabel } }));
  };

  const chip = (active, attr, label, sub) => `<button type="button" class="ui-choice ui-choice--check${active ? " is-active" : ""}" ${attr}>
      <span class="ui-choice__mark"></span>
      <span class="ui-choice__body"><span class="ui-choice__label">${label}</span>${sub}</span>
    </button>`;

  const renderDates = () => {
    datesEl.innerHTML = visibleDates()
      .map((d) => chip(d === state.date, `data-date="${d}"`, `<span class="numfont">${fmtDayMonth(d)}</span>, ${weekday(d)}`, priceSpan(cheapest(pool().filter((c) => c.date === d)), "ui-choice__sub")))
      .join("");
  };

  const renderNights = () => {
    nightsEl.innerHTML = pool()
      .filter((c) => c.date === state.date)
      .map((c) => chip(c.nights === state.nights, `data-nights="${c.nights}"`, `<span class="numfont">${c.nights}</span> ${pluralNights(c.nights)}`, priceSpan(c, "ui-choice__sub")))
      .join("");
  };

  const renderSummary = (combo) => {
    summaryEl.textContent =
      `${fmtDayMonthLong(combo.date)}, ${weekday(combo.date)} → ${fmtDayMonthLong(combo.checkout)}, ${weekday(combo.checkout)} · ` +
      `${combo.nights} ${pluralNights(combo.nights)}`;
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
    emitCombo(combo);

    // Сразу — самый дешёвый номер каждого отеля из сетки, затем полный набор номеров.
    renderSlotHotels(slotCache.get(key) || combo.hotels || []);
    if (slotCache.has(key)) return;

    try {
      const res = await postSamo(ajaxUrl, {
        method: "crosstour_slot",
        event_id: eventId,
        date: combo.date,
        nights: String(combo.nights),
        tour: String(combo.tour || 0),
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
      const forDate = pool().filter((c) => c.date === state.date);
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

  document.addEventListener("event-offer:change", (e) => {
    const key = e.detail?.key || "";
    const concert = /^\d{4}-\d{2}-\d{2}$/.test(key) ? key.replace(/-/g, "") : "";
    if (concert === state.concert) return;
    state.concert = concert;
    pickForConcert();
    renderAll();
  });

  // Одно сочетание — выбирать нечего, секция дат скрыта, остаётся секция отелей.
  const single = pool().length < 2;
  controls.hidden = single;
  section.hidden = single;
  pickForConcert();
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
    if (builder) showBuilderError(builder);
    revealManualAccommodation();
  }
};
