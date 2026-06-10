/**
 * Crosstour (Самотур) — живые данные на странице события.
 *
 * Если событие зарезолвлено в Само (на сервере выставлен [data-crosstour-event]),
 * подтягиваем цену «от», список отелей и доступные даты через AJAX
 * (bsi_samo&method=crosstour_event) и обновляем разметку. Приоритет Само > ручное:
 * при наличии данных перекрываем ручные значения, иначе остаётся ручной fallback.
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

  try {
    const body = new URLSearchParams();
    body.set("action", "bsi_samo");
    body.set("method", "crosstour_event");
    body.set("event_id", eventId);

    console.debug("[crosstour] запрос", { eventId, ajaxUrl });

    const res = await fetch(ajaxUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: body.toString(),
      credentials: "same-origin",
    });

    const json = await res.json();
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
    revealManualAccommodation();
  }
};
