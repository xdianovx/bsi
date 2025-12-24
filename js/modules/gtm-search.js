import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "./forms/dropdown.js";
import { createDayRange } from "./forms/day-range.js";
import { peopleCounter } from "./gtm-people-counter.js";

export const gtmSearch = async () => {
  const gtmSection = document.querySelector(".gtm-search__section");
  if (!gtmSection) return;

  const setLoading = (on) => gtmSection.classList.toggle("is-loading", !!on);

  async function samoAjax(method, params = {}) {
    const body = new URLSearchParams();
    body.set("action", "bsi_samo");
    body.set("method", method);

    Object.entries(params).forEach(([k, v]) => {
      if (v === undefined || v === null) return;
      body.set(k, String(v));
    });

    // nonce позже добавим
    // body.set("nonce", bsiSamo.nonce);

    const res = await fetch(ajax.url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body,
    });

    const json = await res.json().catch(() => null);
    if (!json || !json.success) throw new Error(json?.data?.message || "AJAX error");

    // то, что вернул wp_send_json_success(...)
    const wrap = json.data;

    // если это наш SamoClient-формат: { ok, data, url }
    if (wrap && typeof wrap === "object" && "ok" in wrap) {
      if (!wrap.ok) throw new Error(wrap.error || "SAMO error");
      return wrap.data; // <-- ВАЖНО: возвращаем чистый payload SamoTour
    }

    // иначе вернули уже payload напрямую
    return wrap;
  }

  dropdown(".gtm-nights-select");
  dropdown(".gtm-persons-select");

  const townSelectElement = gtmSection.querySelector(".gtm-town-select");
  const stateSelectElement = gtmSection.querySelector(".gtm-state-select");
  const submitBtn = gtmSection.querySelector(".gtm-item__button");

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

  function updateTourLink(params = {}) {
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

  updateTourLink();

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

    const payload = await samoAjax("townfroms");
    const towns = payload?.SearchTour_TOWNFROMS || [];

    townSelect.setChoices(
      towns.map((t) => ({ value: String(t.id), label: t.name })),
      "value",
      "label",
      true
    );

    // если дефолтного города нет — берем первый
    const hasDefault = towns.some((t) => String(t.id) === String(searchParams.activeTown));
    if (!hasDefault && towns.length) {
      searchParams.activeTown = String(towns[0].id);
    }

    townSelect.setChoiceByValue(String(searchParams.activeTown));
  }

  async function loadStates(townId) {
    stateSelect.clearChoices();
    stateSelect.clearStore();

    const payload = await samoAjax("states", { TOWNFROMINC: String(townId) });
    const states = payload?.SearchTour_STATES || [];

    if (!states.length) {
      updateTourLink({ activeState: "" });
      return;
    }

    stateSelect.setChoices(
      states.map((s) => ({ value: String(s.id), label: s.name })),
      "value",
      "label",
      true
    );

    stateSelect.setChoiceByValue(String(states[0].id));
    updateTourLink({ activeState: String(states[0].id) });
  }

  townSelect.passedElement.element.addEventListener("choice", async (e) => {
    const townId = String(e.detail.value);

    setLoading(true);
    try {
      updateTourLink({ activeTown: townId, activeState: "" });
      await loadStates(townId);
    } finally {
      setLoading(false);
    }
  });

  stateSelect.passedElement.element.addEventListener("choice", (e) => {
    updateTourLink({ activeState: String(e.detail.value) });
  });

  submitBtn.addEventListener("click", () => {
    window.open(tourLink, "_blank");
  });

  createDayRange({
    gridSelector: ".day-grid",
    defaultStartDay: searchParams.nightsFrom,
    defaultEndDay: searchParams.nightsTill,
    onChange: ({ startDay, endDay }) => {
      updateTourLink({ nightsFrom: startDay, nightsTill: endDay });
      gtmSection.querySelector(".gtm-nights-select-value").textContent = `${startDay} - ${endDay} ночей`;
    },
  });

  peopleCounter({
    rootSelector: ".gtm-persons-select",
    outputSelector: ".gtm-people-total",
    maxAdults: 4,
    maxChildren: 3,
    onChange: ({ adults, children, ages }) => {
      const encodedAges = ages.length ? ages.join("%2C") : "";
      updateTourLink({
        adultsCount: String(adults),
        childCount: String(children),
        childAges: encodedAges,
      });
    },
  });

  const datepick = gtmSection.querySelector(".gtm-datepicker");
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
          updateTourLink({ checkInStart: selectedDates[0], checkInEnd: selectedDates[1] });
        }
      },
    });
  }

  // INIT
  setLoading(true);
  try {
    await loadTowns();
    await loadStates(searchParams.activeTown);
  } catch (e) {
    console.error(e);
  } finally {
    setLoading(false);
  }
};
