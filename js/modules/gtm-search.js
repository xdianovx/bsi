// gtm-search.js
import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "./forms/dropdown.js";
import { createDayRange } from "./forms/day-range.js";
import { peopleCounter } from "./gtm-people-counter.js";

export const gtmSearch = async () => {
  const section = document.querySelector(".gtm-search__section");
  if (!section) return;

  // ============================================================
  // 1) Общий AJAX хелпер (одна точка входа в WP: action=bsi_samo)
  // ============================================================
  async function samoAjax(method, params = {}) {
    const body = new URLSearchParams({
      action: "bsi_samo",
      method,
      ...params,
    });

    const res = await fetch(ajax.url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body,
    });

    const json = await res.json();
    if (!json.success) throw new Error(json.data?.message || "AJAX error");
    return json.data;
  }

  // ============================================================
  // 2) Табы: переключение + ленивая инициализация
  // ============================================================
  const tabButtons = Array.from(section.querySelectorAll(".gtm-search__tab-btn"));
  const tabPanels = Array.from(section.querySelectorAll(".gtm-search__item"));

  // по твоей разметке: panels идут в порядке (tours/hotels/tickets/excursions)
  const tabNames = tabPanels.map((p) => p.getAttribute("data-tab") || "");

  const initedTabs = new Set();

  function setActiveTab(index) {
    tabButtons.forEach((b) => b.classList.remove("active"));
    tabPanels.forEach((p) => p.classList.remove("active"));

    tabButtons[index]?.classList.add("active");
    tabPanels[index]?.classList.add("active");

    const name = tabNames[index];
    if (name) initTab(name).catch(() => {});
  }

  tabButtons.forEach((btn, idx) => btn.addEventListener("click", () => setActiveTab(idx)));

  // ============================================================
  // 3) ИНИТ конкретных табов (только 1 раз)
  // ============================================================
  async function initTab(name) {
    if (initedTabs.has(name)) return;

    if (name === "tours") await initToursTab();
    if (name === "hotels") await initHotelsTab();
    // tickets/excursions потом

    initedTabs.add(name);
  }

  // ============================================================
  // 4) ТАБ "Туры"
  // ============================================================
  async function initToursTab() {
    const rootEl = section.querySelector('.gtm-search__item[data-tab="tours"]');
    if (!rootEl) return;

    // локальный лоадинг именно таба (можно и section трогать, как тебе удобнее)
    section.classList.add("is-loading");

    // dropdown внутри этого таба (важно: селектор scoped)
    dropdown('[data-tab="tours"] .gtm-nights-select');
    dropdown('[data-tab="tours"] .gtm-persons-select');

    const townSelectElement = rootEl.querySelector(".gtm-town-select");
    const stateSelectElement = rootEl.querySelector(".gtm-state-select");
    const submitBtn = rootEl.querySelector(".gtm-item__button");

    // --- состояние таба "Туры"
    const searchParams = {
      activeTown: "2",
      activeState: "",
      nightsFrom: 5,
      nightsTill: 10,
      checkInStart: "20251210",
      checkInEnd: "20251219",
      adultsCount: "2",
      childCount: "0",
      childAges: "",
    };

    let tourLink = "";

    function formatDateYYYYMMDD(date) {
      return date.getFullYear() + String(date.getMonth() + 1).padStart(2, "0") + String(date.getDate()).padStart(2, "0");
    }

    function updateLink(params = {}) {
      Object.assign(searchParams, params);

      if (searchParams.checkInStart instanceof Date) {
        searchParams.checkInStart = formatDateYYYYMMDD(searchParams.checkInStart);
      }
      if (searchParams.checkInEnd instanceof Date) {
        searchParams.checkInEnd = formatDateYYYYMMDD(searchParams.checkInEnd);
      }

      tourLink =
        `https://online.bsigroup.ru/default.php?page=search_tour` +
        `&TOWNFROMINC=${searchParams.activeTown}` +
        `&STATEINC=${searchParams.activeState}` +
        `&CHECKIN_BEG=${searchParams.checkInStart}` +
        `&CHECKIN_END=${searchParams.checkInEnd}` +
        `&NIGHTS_FROM=${searchParams.nightsFrom}` +
        `&NIGHTS_TILL=${searchParams.nightsTill}` +
        `&ADULT=${searchParams.adultsCount}` +
        `&CHILD=${searchParams.childCount}` +
        `&AGES=${searchParams.childAges}` +
        `&DOLOAD=1`;
    }

    updateLink();

    // --- Choices (Туры)
    const townSelect = new Choices(townSelectElement, {
      searchEnabled: true,
      loadingText: "Загрузка...",
      noResultsText: "Ничего не найдено",
      itemSelectText: "",
      noChoicesText: "",
    });

    const stateSelect = new Choices(stateSelectElement, {
      searchEnabled: false,
      loadingText: "Загрузка...",
      noResultsText: "Ничего не найдено",
      itemSelectText: "",
      noChoicesText: "",
    });

    async function loadTowns() {
      townSelect.clearChoices();
      townSelect.clearStore();

      const resp = await samoAjax("townfroms"); // PHP уже есть
      const towns = resp?.SearchTour_TOWNFROMS || resp;

      townSelect.setChoices(
        (towns || []).map((t) => ({ value: String(t.id), label: t.name })),
        "value",
        "label",
        true
      );

      townSelect.setChoiceByValue(String(searchParams.activeTown));
    }

    async function loadStates(townId) {
      stateSelect.clearChoices();
      stateSelect.clearStore();

      const resp = await samoAjax("states", { TOWNFROMINC: String(townId) }); // PHP уже есть
      const states = resp?.SearchTour_STATES || resp;

      if (states && states.length) {
        stateSelect.setChoices(
          states.map((s) => ({ value: String(s.id), label: s.name })),
          "value",
          "label",
          true
        );
        stateSelect.setChoiceByValue(String(states[0].id));
        updateLink({ activeState: String(states[0].id) });
      }
    }

    // --- listeners (Туры)
    townSelect.passedElement.element.addEventListener("choice", async (e) => {
      const townId = e.detail.value;
      updateLink({ activeTown: String(townId), activeState: "" });
      await loadStates(townId);
    });

    stateSelect.passedElement.element.addEventListener("choice", (e) => {
      updateLink({ activeState: String(e.detail.value) });
    });

    submitBtn.addEventListener("click", () => {
      window.open(tourLink, "_blank");
    });

    // --- Ночи (важно: rootEl)
    createDayRange({
      rootEl,
      gridSelector: ".day-grid",
      defaultStartDay: searchParams.nightsFrom,
      defaultEndDay: searchParams.nightsTill,
      onChange: ({ startDay, endDay }) => {
        if (startDay && endDay) {
          updateLink({ nightsFrom: startDay, nightsTill: endDay });
          rootEl.querySelector(".gtm-nights-select-value").textContent = `${startDay} - ${endDay} ночей`;
        }
      },
    });

    // --- Люди (scoped через селектор таба)
    peopleCounter({
      rootSelector: '[data-tab="tours"] .gtm-persons-select',
      outputSelector: '[data-tab="tours"] .gtm-people-total',
      maxAdults: 4,
      maxChildren: 3,
      onChange: ({ adults, children, ages }) => {
        const encodedAges = ages.length ? ages.join("%2C") : "";
        updateLink({
          adultsCount: String(adults),
          childCount: String(children),
          childAges: encodedAges,
        });
      },
    });

    // --- Даты (Туры)
    const datepick = rootEl.querySelector(".gtm-datepicker");
    if (datepick) {
      const today = new Date();
      const nextWeek = new Date();
      nextWeek.setDate(today.getDate() + 7);

      flatpickr(datepick, {
        mode: "range",
        minDate: "today",
        locale: Russian,
        dateFormat: "d.m",
        defaultDate: [today, nextWeek],
        onChange: (selectedDates) => {
          if (selectedDates.length === 2) {
            updateLink({ checkInStart: selectedDates[0], checkInEnd: selectedDates[1] });
          }
        },
      });
    }

    // --- init загрузки данных (Туры)
    await loadTowns();
    await loadStates(searchParams.activeTown);

    section.classList.remove("is-loading");
  }

  // ============================================================
  // 5) ТАБ "Отели" (без дублирования логики табов, но параметры другие)
  // ============================================================
  async function initHotelsTab() {
    const rootEl = section.querySelector('.gtm-search__item[data-tab="hotels"]');
    if (!rootEl) return;

    section.classList.add("is-loading");

    dropdown('[data-tab="hotels"] .gtm-nights-select');
    dropdown('[data-tab="hotels"] .gtm-persons-select');

    const stateSelectElement = rootEl.querySelector(".gtm-state-select");
    const submitBtn = rootEl.querySelector(".gtm-item__button");

    const searchParams = {
      STATEFROM: "2", // ты сказал “всегда 2”
      activeState: "", // STATEINC
      nightsFrom: 5,
      nightsTill: 10,
      checkInStart: "20251210",
      checkInEnd: "20251219",
      adultsCount: "2",
      childCount: "0",
      childAges: "",
    };

    let hotelLink = "";

    function formatDateYYYYMMDD(date) {
      return date.getFullYear() + String(date.getMonth() + 1).padStart(2, "0") + String(date.getDate()).padStart(2, "0");
    }

    function updateLink(params = {}) {
      Object.assign(searchParams, params);

      if (searchParams.checkInStart instanceof Date) {
        searchParams.checkInStart = formatDateYYYYMMDD(searchParams.checkInStart);
      }
      if (searchParams.checkInEnd instanceof Date) {
        searchParams.checkInEnd = formatDateYYYYMMDD(searchParams.checkInEnd);
      }

      hotelLink =
        `https://online.bsigroup.ru/default.php?page=search_hotel` +
        `&STATEFROM=${searchParams.STATEFROM}` +
        `&STATEINC=${searchParams.activeState}` +
        `&CHECKIN_BEG=${searchParams.checkInStart}` +
        `&CHECKIN_END=${searchParams.checkInEnd}` +
        `&NIGHTS_FROM=${searchParams.nightsFrom}` +
        `&NIGHTS_TILL=${searchParams.nightsTill}` +
        `&ADULT=${searchParams.adultsCount}` +
        `&CHILD=${searchParams.childCount}` +
        `&AGES=${searchParams.childAges}` +
        `&DOLOAD=1`;
    }

    updateLink();

    // Choices (Отели)
    const stateSelect = new Choices(stateSelectElement, {
      searchEnabled: true,
      loadingText: "Загрузка...",
      noResultsText: "Ничего не найдено",
      itemSelectText: "",
      noChoicesText: "",
    });

    //  !!! нужен PHP-роут: method=hotel_states
    async function loadHotelStates() {
      stateSelect.clearChoices();
      stateSelect.clearStore();

      const resp = await samoAjax("hotel_states", { STATEFROM: searchParams.STATEFROM });
      const states = resp?.SearchHotel_STATES || resp;

      if (states && states.length) {
        stateSelect.setChoices(
          states.map((s) => ({ value: String(s.id), label: s.name })),
          "value",
          "label",
          true
        );

        stateSelect.setChoiceByValue(String(states[0].id));
        updateLink({ activeState: String(states[0].id) });
      }
    }

    // listeners (Отели)
    stateSelect.passedElement.element.addEventListener("choice", (e) => {
      updateLink({ activeState: String(e.detail.value) });
    });

    submitBtn.addEventListener("click", () => {
      window.open(hotelLink, "_blank");
    });

    // Ночи (rootEl)
    createDayRange({
      rootEl,
      gridSelector: ".day-grid",
      defaultStartDay: searchParams.nightsFrom,
      defaultEndDay: searchParams.nightsTill,
      onChange: ({ startDay, endDay }) => {
        if (startDay && endDay) {
          updateLink({ nightsFrom: startDay, nightsTill: endDay });
          rootEl.querySelector(".gtm-nights-select-value").textContent = `${startDay} - ${endDay} ночей`;
        }
      },
    });

    // Люди (Отели)
    peopleCounter({
      rootSelector: '[data-tab="hotels"] .gtm-persons-select',
      outputSelector: '[data-tab="hotels"] .gtm-people-total',
      maxAdults: 4,
      maxChildren: 3,
      onChange: ({ adults, children, ages }) => {
        const encodedAges = ages.length ? ages.join("%2C") : "";
        updateLink({
          adultsCount: String(adults),
          childCount: String(children),
          childAges: encodedAges,
        });
      },
    });

    // Даты (Отели)
    const datepick = rootEl.querySelector(".gtm-datepicker");
    if (datepick) {
      const today = new Date();
      const nextWeek = new Date();
      nextWeek.setDate(today.getDate() + 7);

      flatpickr(datepick, {
        mode: "range",
        minDate: "today",
        locale: Russian,
        dateFormat: "d.m",
        defaultDate: [today, nextWeek],
        onChange: (selectedDates) => {
          if (selectedDates.length === 2) {
            updateLink({ checkInStart: selectedDates[0], checkInEnd: selectedDates[1] });
          }
        },
      });
    }

    await loadHotelStates();

    section.classList.remove("is-loading");
  }

  // ============================================================
  // 6) Стартуем: активный таб (по разметке уже active)
  // ============================================================
  const activeIndex = tabPanels.findIndex((p) => p.classList.contains("active"));
  setActiveTab(activeIndex >= 0 ? activeIndex : 0);
};
