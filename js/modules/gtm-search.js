import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "./forms/dropdown.js";
import { createDayRange } from "./forms/day-range.js";
import { peopleCounter } from "./gtm-people-counter.js";

export const gtmSearch = async () => {
  const section = document.querySelector(".gtm-search__section");
  if (!section) return;

  function getNightsWord(count) {
    const mod10 = count % 10;
    const mod100 = count % 100;
    
    if (mod100 >= 11 && mod100 <= 14) {
      return "ночей";
    }
    if (mod10 === 1) {
      return "ночь";
    }
    if (mod10 >= 2 && mod10 <= 4) {
      return "ночи";
    }
    return "ночей";
  }

  function formatNightsText(startDay, endDay) {
    if (startDay === endDay) {
      return `${startDay} ${getNightsWord(startDay)}`;
    }
    const startWord = getNightsWord(startDay);
    const endWord = getNightsWord(endDay);
    if (startWord === endWord) {
      return `${startDay} - ${endDay} ${startWord}`;
    }
    return `${startDay} ${startWord} - ${endDay} ${endWord}`;
  }

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

  const tabButtons = Array.from(section.querySelectorAll(".gtm-search__tab-btn"));
  const tabPanels = Array.from(section.querySelectorAll(".gtm-search__item"));

  const initedTabs = new Set();

  function setActiveTab(index) {
    const btn = tabButtons[index];
    const href = btn?.getAttribute("data-href");

    if (href) {
      const target = btn?.getAttribute("data-target");
      if (target === "_blank") {
        window.open(href, "_blank");
      } else {
        window.location.href = href;
      }
      return;
    }

    const tabName = btn?.getAttribute("data-tab");
    const panel = tabName ? section.querySelector(`.gtm-search__item[data-tab="${tabName}"]`) : null;

    if (!panel) return;

    tabButtons.forEach((b) => b.classList.remove("active"));
    tabPanels.forEach((p) => p.classList.remove("active"));

    btn?.classList.add("active");
    panel.classList.add("active");

    initTab(tabName).catch(() => {});
  }

  tabButtons.forEach((btn, idx) => btn.addEventListener("click", () => setActiveTab(idx)));

  async function initTab(name) {
    if (initedTabs.has(name)) return;

    if (name === "tours") await initToursTab();
    if (name === "hotels") await initHotelsTab();
    if (name === "excursions") await initExcursionsTab();

    initedTabs.add(name);
  }

  async function initToursTab() {
    const rootEl = section.querySelector('.gtm-search__item[data-tab="tours"]');
    if (!rootEl) return;

    section.classList.add("is-loading");

    dropdown('[data-tab="tours"] .gtm-nights-select');
    dropdown('[data-tab="tours"] .gtm-persons-select');

    const townSelectElement = rootEl.querySelector(".gtm-town-select");
    const stateSelectElement = rootEl.querySelector(".gtm-state-select");
    const submitBtn = rootEl.querySelector(".gtm-item__button");

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

      const resp = await samoAjax("townfroms");
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

      const resp = await samoAjax("states", { TOWNFROMINC: String(townId) });
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

    const dayRange = createDayRange({
      rootEl,
      gridSelector: ".day-grid",
      defaultStartDay: searchParams.nightsFrom,
      defaultEndDay: searchParams.nightsTill,
      onChange: ({ startDay, endDay }) => {
        if (startDay) {
          const nightsTill = endDay || startDay;
          updateLink({ nightsFrom: startDay, nightsTill: nightsTill });
          const nightsValue = rootEl.querySelector(".gtm-nights-select-value");
          if (nightsValue) {
            nightsValue.textContent = formatNightsText(startDay, nightsTill);
          }
        }
      },
    });

    if (dayRange) {
      const state = dayRange.getState();
      if (state.startDay) {
        const nightsTill = state.endDay || state.startDay;
        const nightsValue = rootEl.querySelector(".gtm-nights-select-value");
        if (nightsValue) {
          nightsValue.textContent = formatNightsText(state.startDay, nightsTill);
        }
      }
    }

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

    await loadTowns();
    await loadStates(searchParams.activeTown);

    section.classList.remove("is-loading");
  }

  async function initHotelsTab() {
    const rootEl = section.querySelector('.gtm-search__item[data-tab="hotels"]');
    if (!rootEl) return;

    section.classList.add("is-loading");

    dropdown('[data-tab="hotels"] .gtm-nights-select');
    dropdown('[data-tab="hotels"] .gtm-persons-select');

    const stateSelectElement = rootEl.querySelector(".gtm-state-select");
    const submitBtn = rootEl.querySelector(".gtm-item__button");

    const searchParams = {
      STATEFROM: "2",
      activeState: "",
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

    const stateSelect = new Choices(stateSelectElement, {
      searchEnabled: true,
      loadingText: "Загрузка...",
      noResultsText: "Ничего не найдено",
      itemSelectText: "",
      noChoicesText: "",
    });

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

    stateSelect.passedElement.element.addEventListener("choice", (e) => {
      updateLink({ activeState: String(e.detail.value) });
    });

    submitBtn.addEventListener("click", () => {
      window.open(hotelLink, "_blank");
    });

    const dayRange = createDayRange({
      rootEl,
      gridSelector: ".day-grid",
      defaultStartDay: searchParams.nightsFrom,
      defaultEndDay: searchParams.nightsTill,
      onChange: ({ startDay, endDay }) => {
        if (startDay) {
          const nightsTill = endDay || startDay;
          updateLink({ nightsFrom: startDay, nightsTill: nightsTill });
          const nightsValue = rootEl.querySelector(".gtm-nights-select-value");
          if (nightsValue) {
            nightsValue.textContent = formatNightsText(startDay, nightsTill);
          }
        }
      },
    });

    if (dayRange) {
      const state = dayRange.getState();
      if (state.startDay) {
        const nightsTill = state.endDay || state.startDay;
        const nightsValue = rootEl.querySelector(".gtm-nights-select-value");
        if (nightsValue) {
          nightsValue.textContent = formatNightsText(state.startDay, nightsTill);
        }
      }
    }

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

  async function initExcursionsTab() {
    const rootEl = section.querySelector('.gtm-search__item[data-tab="excursions"]');
    if (!rootEl) return;

    section.classList.add("is-loading");

    dropdown('[data-tab="excursions"] .gtm-persons-select');

    const stateSelectElement = rootEl.querySelector(".gtm-state-select");
    const toursSelectElement = rootEl.querySelector(".gtm-tours-select");
    const submitBtn = rootEl.querySelector(".gtm-item__button");

    const searchParams = {
      activeState: "",
      tours: "",
      checkInStart: "20251210",
      checkInEnd: "20251219",
      adultsCount: "2",
      childCount: "0",
      childAges: "",
    };

    let excursionLink = "";

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

      const paramsArray = [];
      paramsArray.push(`TOWNFROMINC=1`);
      if (searchParams.activeState) paramsArray.push(`STATEINC=${searchParams.activeState}`);
      if (searchParams.tours) paramsArray.push(`TOURS=${searchParams.tours}`);
      if (searchParams.checkInStart) paramsArray.push(`CHECKIN_BEG=${searchParams.checkInStart}`);
      if (searchParams.checkInEnd) paramsArray.push(`CHECKIN_END=${searchParams.checkInEnd}`);
      if (searchParams.adultsCount) paramsArray.push(`ADULT=${searchParams.adultsCount}`);
      paramsArray.push(`CURRENCY=1`);
      if (searchParams.childCount) paramsArray.push(`CHILD=${searchParams.childCount}`);
      paramsArray.push(`TOWNS_ANY=1`);
      paramsArray.push(`STARS_ANY=1`);
      paramsArray.push(`HOTELS_ANY=1`);
      paramsArray.push(`MEALS_ANY=1`);
      paramsArray.push(`ROOMS_ANY=1`);
      paramsArray.push(`FREIGHT=1`);
      paramsArray.push(`PRICEPAGE=1`);
      paramsArray.push(`DOLOAD=1`);

      excursionLink = `https://online.bsigroup.ru/search_excursion?${paramsArray.join("&")}`;
    }

    updateLink();

    const stateSelect = new Choices(stateSelectElement, {
      searchEnabled: true,
      loadingText: "Загрузка...",
      noResultsText: "Ничего не найдено",
      itemSelectText: "",
      noChoicesText: "",
    });

    const toursSelect = new Choices(toursSelectElement, {
      searchEnabled: true,
      loadingText: "Загрузка...",
      noResultsText: "Ничего не найдено",
      itemSelectText: "",
      noChoicesText: "",
    });

    async function loadStates() {
      stateSelect.clearChoices();
      stateSelect.clearStore();

      const resp = await samoAjax("excursion_states", { TOWNFROMINC: 1 });
      const states = resp?.SearchExcursion_STATES || resp;

      if (states && states.length) {
        stateSelect.setChoices(
          states.map((s) => ({ value: String(s.id), label: s.name })),
          "value",
          "label",
          true
        );
        stateSelect.setChoiceByValue(String(states[0].id));
        updateLink({ activeState: String(states[0].id), tours: "" });
        await loadTours(String(states[0].id));
      }
    }

    async function loadTours(stateId) {
      toursSelect.clearChoices();
      toursSelect.clearStore();

      if (!stateId) {
        return;
      }

      const resp = await samoAjax("excursion_tours", { TOWNFROMINC: 1, STATEINC: stateId });
      const tours = resp?.SearchExcursion_TOURS || resp || [];

      if (tours.length) {
        toursSelect.setChoices(
          tours.map((t) => ({ value: String(t.id), label: t.name || t.title || String(t.id) })),
          "value",
          "label",
          true
        );
        if (tours.length === 1) {
          toursSelect.setChoiceByValue(String(tours[0].id));
          updateLink({ tours: String(tours[0].id) });
        }
      }
    }

    stateSelect.passedElement.element.addEventListener("choice", async (e) => {
      const stateId = e.detail.value;
      updateLink({ activeState: String(stateId), tours: "" });
      await loadTours(stateId);
    });

    toursSelect.passedElement.element.addEventListener("choice", (e) => {
      updateLink({ tours: String(e.detail.value) });
    });

    submitBtn.addEventListener("click", () => {
      window.open(excursionLink, "_blank");
    });

    peopleCounter({
      rootSelector: '[data-tab="excursions"] .gtm-persons-select',
      outputSelector: '[data-tab="excursions"] .gtm-people-total',
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

    await loadStates();

    section.classList.remove("is-loading");
  }

  const activeIndex = tabPanels.findIndex((p) => p.classList.contains("active"));
  setActiveTab(activeIndex >= 0 ? activeIndex : 0);
};
