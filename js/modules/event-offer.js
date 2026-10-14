import { openEventTicketModal } from "./forms/event-ticket-form.js";

/**
 * Страница события: дата концерта → формат «Тур с билетом / Только билет» → билет / пакет / сектор в туре.
 *
 * Разметка — single-event.php и template-parts/event/page/*. Цены всех дат отрисованы сервером
 * (.js-event-price — работает переключатель валют), здесь только показываем нужные:
 * [data-offer-key] — по дате, [data-offer-format-only] — по формату.
 * Сайдбар ([data-summary]) собирает состав заказа; на мобилке — липкая панель и шторка.
 * Конструктор тура (crosstour-event.js) фильтрует заезды по концерту (event-offer:change)
 * и отдаёт сочетание (crosstour:combo) и цены пакетов (crosstour:packages).
 * Выбор хранится в адресе: ?format=ticket&date=2027-03-07.
 */

const PASS_KEY = "all";

/**
 * UTM для переходов с сайта в Самотур (online.bsigroup.ru) — видно в Метрике Само.
 * Свои метки в ссылке менеджера не перетираем.
 */
export function withEventUtm(url, content) {
  if (!url || url.startsWith("#")) return url;
  try {
    const u = new URL(url, window.location.href);
    const slug = window.location.pathname.split("/").filter(Boolean).pop() || "";
    const utm = {
      utm_source: "bsigroup.ru",
      utm_medium: "referral",
      utm_campaign: slug ? `event_${slug}` : "event",
      utm_content: content || "",
    };
    Object.entries(utm).forEach(([k, v]) => {
      if (v && !u.searchParams.has(k)) u.searchParams.set(k, v);
    });
    return u.toString();
  } catch (_e) {
    return url;
  }
}

const fmtPrice = (n) => Number(n).toLocaleString("ru-RU");

function readJson() {
  const el = document.querySelector("[data-event-offer-json]");
  if (!el) return null;
  try {
    return JSON.parse(el.textContent || "{}");
  } catch (_e) {
    return null;
  }
}

const checkedValue = (name) => document.querySelector(`input[name="${name}"]:checked`)?.value || "";

function initAboutMore() {
  const body = document.querySelector("[data-about-body]");
  const btn = document.querySelector("[data-about-more]");
  if (!body || !btn) return;
  body.classList.add("is-clamped");
  if (body.scrollHeight - body.clientHeight < 4) {
    body.classList.remove("is-clamped");
    return;
  }
  btn.hidden = false;
  btn.addEventListener("click", () => {
    body.classList.remove("is-clamped");
    btn.hidden = true;
  });
}

export function initEventOffer() {
  const page = document.querySelector("[data-event-offer]");
  if (!page) return;
  initAboutMore();

  const data = readJson();
  if (!data || !Array.isArray(data.dates) || data.dates.length === 0) {
    // Ни дат, ни выбора (старая запись, событие завершено) — работает только «Получить консультацию».
    document.addEventListener("click", (e) => {
      if (!e.target.closest("[data-offer-consult]")) return;
      e.preventDefault();
      openEventTicketModal({
        kind: "consult",
        eventTitle: page.dataset.eventTitle || "",
        eventId: page.dataset.eventId || "",
        format: "Консультация",
      });
    });
    return;
  }

  const formats = (data.formats || []).filter(Boolean);
  const params = new URLSearchParams(window.location.search);
  const state = {
    key: data.dates.some((d) => d.key === params.get("date")) ? params.get("date") : data.dates[0].key,
    format: formats.includes(params.get("format")) ? params.get("format") : data.defaultFormat,
    ticket: checkedValue("event_ticket_sector"),
    tourSector: checkedValue("event_tour_sector") || data.tourSector || "",
    package: checkedValue("event_tour_package"),
  };
  // Сочетание конструктора: ссылка на Само, «5–7 марта · 2 ночи», ночи — для панели.
  const combo = { url: "", trip: "", nights: "" };

  const current = () => data.dates.find((d) => d.key === state.key) || data.dates[0];
  const sectorOf = (name) => (current().sectors || []).find((s) => s.name === name) || null;
  const tourLink = document.querySelector("[data-offer-tour-link]");
  const tourSectorBtn = document.querySelector('[data-offer-action="tour_sector"]');
  const summary = (name) => document.querySelector(`[data-summary="${name}"]`);

  function setActive(selector, attr, value) {
    document.querySelectorAll(selector).forEach((el) => {
      const active = el.dataset[attr] === value;
      el.classList.toggle("is-active", active);
      const radio = el.querySelector(".ui-checkbox__input");
      if (radio) radio.checked = active;
    });
  }

  // Доплата за сектор в туре (не входящий в тур) — тур уходит заявкой, Само его не продаёт.
  const withExtra = () => state.tourSector !== "" && state.tourSector !== data.tourSector;

  function syncTourLink() {
    if (!tourLink) return;
    // Выбранный пакет из Само (своя ссылка на пакет) → сочетание конструктора → общая ссылка.
    const pkgUrl = document.querySelector(`[data-offer-package="${CSS.escape(state.package)}"]`)?.dataset.packageUrl || "";
    const url = pkgUrl || combo.url || current().tourHref || "";
    tourLink.href = url ? withEventUtm(url, "sidebar") : "#";
    tourLink.toggleAttribute("data-no-url", !url);
    const extra = withExtra() && state.format === "tour";
    tourLink.hidden = state.format !== "tour" || extra;
    if (tourSectorBtn) tourSectorBtn.hidden = !extra;
  }

  function syncSummary() {
    const date = current();
    const concert = summary("concert");
    if (concert) concert.textContent = date.key === PASS_KEY ? "Все дни" : date.label;

    const ticket = summary("ticket");
    const ticketSector = sectorOf(state.ticket);
    if (ticket) ticket.textContent = state.ticket;
    const ticketPrice = document.querySelector('[data-offer-summary-price="ticket"]');
    const selectedTicketPrice = document.querySelector(
      `[data-offer-ticket="${CSS.escape(state.ticket)}"] .ui-option__price[data-offer-key="${CSS.escape(state.key)}"]`
    );
    if (ticketPrice && selectedTicketPrice) {
      ticketPrice.textContent = selectedTicketPrice.textContent;
      ["priceRub", "priceOriginal", "priceCurrency", "hasFrom"].forEach((k) => {
        if (selectedTicketPrice.dataset[k]) ticketPrice.dataset[k] = selectedTicketPrice.dataset[k];
        else delete ticketPrice.dataset[k];
      });
    } else if (ticketPrice && ticketSector?.price) {
      ticketPrice.textContent = ticketSector.price;
    }

    const tourTicket = summary("tour-ticket");
    if (tourTicket) tourTicket.textContent = state.tourSector || data.tourSector;

    const pkgEl = document.querySelector(`[data-offer-package="${CSS.escape(state.package)}"]`);
    const pkgSummary = summary("package");
    if (pkgSummary && pkgEl) {
      const stars = pkgSummary.querySelector(".event-offer-summary__stars");
      pkgSummary.firstChild.textContent = `${pkgEl.dataset.packageTitle || ""} `;
      if (stars) stars.querySelector(".numfont").textContent = pkgEl.dataset.packageStars || "";
    }

    const trip = summary("trip");
    const tripRow = document.querySelector('[data-summary-row="trip"]');
    if (trip && tripRow) {
      trip.textContent = combo.trip;
      tripRow.hidden = state.format !== "tour" || !combo.trip;
    }

    // Цена тура: пакет из Само → иначе общая «от»; доплата за сектор прибавляется.
    // Валюту («Показать в валюте») несём дальше, если пакет и доплата в одной валюте.
    const tourPrice = document.querySelector('[data-offer-summary-price="tour"]');
    if (tourPrice) {
      if (!tourPrice.dataset.basePriceRub && tourPrice.dataset.priceRub) {
        tourPrice.dataset.basePriceRub = tourPrice.dataset.priceRub;
        tourPrice.dataset.baseOriginal = tourPrice.dataset.priceOriginal || "";
        tourPrice.dataset.baseCurrency = tourPrice.dataset.priceCurrency || "";
      }
      const src = pkgEl?.querySelector("[data-package-price]")?.dataset || {};
      let rub = Number(src.priceRub || tourPrice.dataset.basePriceRub || 0);
      let original = Number(src.priceRub ? src.priceOriginal || 0 : tourPrice.dataset.baseOriginal || 0);
      const currency = src.priceRub ? src.priceCurrency || "" : tourPrice.dataset.baseCurrency || "";
      const extraEl = document.querySelector(
        `[data-offer-tour-sector="${CSS.escape(state.tourSector)}"] .ui-option__price[data-offer-key="${CSS.escape(state.key)}"]`,
      );
      if (withExtra() && extraEl?.dataset.priceRub) {
        rub += Number(extraEl.dataset.priceRub);
        original = extraEl.dataset.priceCurrency === currency && original ? original + Number(extraEl.dataset.priceOriginal || 0) : 0;
      }
      if (rub > 0) {
        tourPrice.classList.add("js-event-price");
        tourPrice.dataset.priceRub = String(rub);
        tourPrice.dataset.hasFrom = "true";
        if (original > 0 && currency) {
          tourPrice.dataset.priceOriginal = String(original);
          tourPrice.dataset.priceCurrency = currency;
        } else {
          delete tourPrice.dataset.priceOriginal;
          delete tourPrice.dataset.priceCurrency;
        }
        tourPrice.textContent = `от ${fmtPrice(rub)} ₽`;
        // Включён «Показать в валюте» — переключатель перепишет текст в валюту.
        document.dispatchEvent(new CustomEvent("education:content-updated"));
      }
    }
  }

  function render() {
    // Абонемент — только у билетов: в туре возвращаемся к первой дате.
    if (state.format === "tour" && state.key === PASS_KEY) {
      state.key = data.dates.find((d) => d.key !== PASS_KEY)?.key || state.key;
    }
    const date = current();

    setActive("[data-offer-date]", "offerDate", state.key);
    setActive("[data-offer-format]", "offerFormat", state.format);
    setActive("[data-offer-ticket]", "offerTicket", state.ticket);
    setActive("[data-offer-tour-sector]", "offerTourSector", state.tourSector);
    setActive("[data-offer-package]", "offerPackage", state.package);

    document.querySelectorAll("[data-offer-key], [data-offer-format-only]").forEach((el) => {
      const keyOk = !el.dataset.offerKey || el.dataset.offerKey === state.key;
      const formatOk = !el.dataset.offerFormatOnly || el.dataset.offerFormatOnly === state.format;
      el.hidden = !(keyOk && formatOk);
    });
    document.querySelectorAll("[data-offer-package-hotels]").forEach((el) => {
      el.hidden = el.dataset.offerPackageHotels !== state.package;
    });

    const place = document.querySelector("[data-offer-place]");
    if (place) place.textContent = date.place;
    const summaryPlace = document.querySelector("[data-offer-summary-place]");
    if (summaryPlace && date.venueLine) summaryPlace.textContent = date.venueLine;

    syncUrl();
    syncTourLink();
    syncSummary();
    syncBar();
    document.dispatchEvent(new CustomEvent("education:content-updated"));
    document.dispatchEvent(new CustomEvent("event-offer:change", { detail: { key: state.key, format: state.format } }));
  }

  function syncUrl() {
    const url = new URL(window.location.href);
    if (state.key === data.dates[0].key) url.searchParams.delete("date");
    else url.searchParams.set("date", state.key);
    if (state.format === data.defaultFormat) url.searchParams.delete("format");
    else url.searchParams.set("format", state.format);
    window.history.replaceState(null, "", url.toString());
  }

  function openRequest(kind, sector) {
    const date = current();
    const isTicket = kind === "ticket" || kind === "tour_sector";
    openEventTicketModal({
      kind,
      eventTitle: page.dataset.eventTitle || "",
      eventId: page.dataset.eventId || "",
      date: isTicket ? (date.key === PASS_KEY ? "Абонемент на все дни" : date.full) : "",
      venue: date.place,
      time: page.dataset.eventTime || "",
      format: "",
      sectors: date.sectors || [],
      sector: sector || "",
      sectorPicked: !!sector,
      price: "",
    });
  }

  document.addEventListener("click", (e) => {
    const pick = (selector, key, attr) => {
      const el = e.target.closest(selector);
      if (!el) return false;
      state[key] = el.dataset[attr];
      render();
      return true;
    };
    if (pick("[data-offer-date]", "key", "offerDate")) return;
    if (pick("[data-offer-format]", "format", "offerFormat")) return;
    if (pick("[data-offer-ticket]", "ticket", "offerTicket")) return;
    if (pick("[data-offer-tour-sector]", "tourSector", "offerTourSector")) return;
    if (pick("[data-offer-package]", "package", "offerPackage")) return;

    const link = e.target.closest("[data-offer-tour-link]");
    if (link && link.hasAttribute("data-no-url")) {
      // Ссылки на Само нет — тур оформляет менеджер.
      e.preventDefault();
      openRequest("consult");
      return;
    }
    if (e.target.closest('[data-offer-action="tour_sector"]')) {
      openRequest("tour_sector", state.tourSector);
      return;
    }
    if (e.target.closest('[data-offer-action="ticket"]')) {
      openRequest("ticket", state.ticket);
      return;
    }
    if (e.target.closest("[data-offer-consult]")) {
      e.preventDefault();
      openRequest("consult");
      return;
    }
    if (e.target.closest("[data-offer-sheet-open]")) {
      toggleSheet(true);
      return;
    }
    if (e.target.closest("[data-offer-sheet-close]")) {
      toggleSheet(false);
    }
  });

  document.addEventListener("crosstour:combo", (e) => {
    combo.url = e.detail?.url || "";
    combo.trip = e.detail?.trip || "";
    combo.nights = e.detail?.nightsLabel || "";
    syncTourLink();
    syncSummary();
    syncBar();
  });
  document.addEventListener("crosstour:packages", () => {
    if (!document.querySelector(`[data-offer-package="${CSS.escape(state.package)}"]`)) {
      state.package = document.querySelector("[data-offer-package]")?.dataset.offerPackage || "";
    }
    render();
  });

  /* ── Мобилка: липкая панель и шторка ─────────────────────────────────── */
  const bar = document.querySelector("[data-offer-bar]");
  const aside = document.querySelector(".single-event__about-aside");
  const backdrop = document.querySelector(".event-offer-sheet-backdrop");
  let asideVisible = true;
  let bottomVisible = false;

  function toggleSheet(open) {
    if (!aside) return;
    aside.classList.toggle("is-sheet-open", open);
    if (backdrop) {
      backdrop.hidden = !open;
      backdrop.classList.toggle("is-open", open);
    }
    document.documentElement.style.overflow = open ? "hidden" : "";
    syncBar();
  }

  function syncBar() {
    if (!bar) return;
    const barLabel = bar.querySelector("[data-offer-bar-label]");
    const barPrice = bar.querySelector("[data-offer-bar-price]");
    const pkg = document.querySelector(`[data-offer-package="${CSS.escape(state.package)}"]`)?.dataset.packageTitle || "";
    const label =
      state.format === "tour"
        ? [pkg, withExtra() ? state.tourSector : combo.nights].filter(Boolean).join(" · ") || "Тур с билетом"
        : [current().label, state.ticket].filter(Boolean).join(" · ");
    const priceEl = document.querySelector(`[data-offer-summary-price="${state.format}"]`);
    if (barLabel) barLabel.textContent = label;
    if (barPrice) barPrice.textContent = priceEl ? priceEl.textContent.trim() : "";
    // Панель закреплена всегда (решение 08.10), прячется только под открытой шторкой.
    bar.hidden = !!aside?.classList.contains("is-sheet-open");
  }

  if (bar && "IntersectionObserver" in window) {
    const watched = document.querySelector(".single-event__booking-widget");
    if (watched) {
      new IntersectionObserver((entries) => {
        asideVisible = entries[0].isIntersecting;
        syncBar();
      }).observe(watched);
    }
    // У низа страницы панель не перекрывает форму «Остались вопросы?» и подвал.
    const seen = new Set();
    const bottomObserver = new IntersectionObserver((entries) => {
      entries.forEach((en) => (en.isIntersecting ? seen.add(en.target) : seen.delete(en.target)));
      bottomVisible = seen.size > 0;
      syncBar();
    });
    document.querySelectorAll(".single-event__booking-cta, footer").forEach((el) => bottomObserver.observe(el));
    document.querySelectorAll(".js-education-show-original-currency").forEach((cb) => {
      cb.addEventListener("change", () => window.requestAnimationFrame(syncBar));
    });
  }

  render();
}
