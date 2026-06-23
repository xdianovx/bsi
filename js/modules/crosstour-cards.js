/**
 * Crosstour (Самотур) — живая цена «от» на карточках событий (как у туров).
 *
 * Находит [data-crosstour-card] (кнопка-цена в template-parts/event/card.php),
 * батчем тянет цены через AJAX (bsi_samo&method=crosstour_batch) и проставляет
 * «от X ₽» + data-price-* (совместимо с переключателем валют). Карточки,
 * не связанные с Само, остаются как есть (ручная цена / «по запросу»).
 *
 * Fallback: если batch не вернул цену, но карточка имеет data-booking-url —
 * грузим через excursion_prices (тот же механизм, что у card.php туров).
 */

import { parseBookingParams, fetchTourMinPrice } from "./services/priceLoader.js";

const processed = new Set();

const fmtPrice = (n) => Number(n).toLocaleString("ru-RU");

const fetchBatch = async (ids) => {
  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) { console.warn("[crosstour-cards] no ajaxUrl"); return {}; }
  console.log("[crosstour-cards] batch request →", ids);
  const body = new URLSearchParams();
  body.set("action", "bsi_samo");
  body.set("method", "crosstour_batch");
  ids.forEach((id) => body.append("ids[]", id));

  const res = await fetch(ajaxUrl, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
    body: body.toString(),
    credentials: "same-origin",
  });
  const json = await res.json();
  console.log("[crosstour-cards] batch response:", json);
  return json && json.success && json.data ? json.data.prices || {} : {};
};

const run = async () => {
  const cards = [...document.querySelectorAll("[data-crosstour-card]")].filter(
    (el) => !processed.has(el.dataset.crosstourCard),
  );
  if (!cards.length) return;

  const ids = [...new Set(cards.map((c) => c.dataset.crosstourCard))];
  ids.forEach((id) => processed.add(id)); // помечаем сразу — без повторных запросов

  let prices = {};
  try {
    prices = await fetchBatch(ids);
  } catch (_e) {
    return;
  }

  let changed = false;
  const needFallback = [];
  cards.forEach((el) => {
    const d = prices[el.dataset.crosstourCard];
    if (!d || !d.price_rub || Number(d.price_rub) <= 0) {
      console.log("[crosstour-cards] no price for event", el.dataset.crosstourCard, "→ got:", d);
      if (el.dataset.bookingUrl) needFallback.push(el);
      return;
    }

    el.classList.add("js-event-price");
    el.dataset.priceRub = String(Number(d.price_rub));
    el.dataset.hasFrom = "true";
    delete el.dataset.priceSuffix;
    if (d.price_original && d.price_currency) {
      el.dataset.priceOriginal = String(d.price_original);
      el.dataset.priceCurrency = String(d.price_currency);
    }
    el.textContent = `от ${fmtPrice(d.price_rub)} ₽`;
    changed = true;
  });

  // Fallback: тот же механизм, что у карточек туров — excursion_prices по booking URL.
  if (needFallback.length) {
    console.log("[crosstour-cards] excursion fallback for", needFallback.length, "events");
    await Promise.all(
      needFallback.map(async (el) => {
        const params = parseBookingParams(el.dataset.bookingUrl);
        if (!params) return;
        const minPrice = await fetchTourMinPrice(params);
        console.log("[crosstour-cards] fallback event", el.dataset.crosstourCard, "minPrice:", minPrice);
        if (minPrice !== null && minPrice > 0) {
          const perPerson = Math.round(minPrice / 2);
          el.classList.add("js-event-price");
          el.dataset.priceRub = String(perPerson);
          el.dataset.hasFrom = "true";
          delete el.dataset.priceSuffix;
          el.textContent = `от ${fmtPrice(perPerson)} ₽`;
          changed = true;
        }
      })
    );
  }

  if (changed) {
    document.dispatchEvent(new CustomEvent("education:content-updated"));
  }
};

export const initCrosstourCards = () => {
  const root = document.querySelector("[data-crosstour-card]");
  if (!root) return;
  run();
  // Карточки догружаются AJAX-ом (каталог/страна) → перескан после обновления.
  document.addEventListener("education:content-updated", () => {
    run();
  });
};
