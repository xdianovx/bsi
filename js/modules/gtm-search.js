import Choices from "choices.js";
import { APIService } from "./api-service.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "./forms/dropdown.js";
import { createDayRange } from "./forms/day-range.js";
import { peopleCounter } from "./gtm-people-counter.js";

export const gtmSearch = async () => {
  const gtmSection = document.querySelector(".gtm-search__section");

  if (!gtmSection) return;

  const towns = await APIService.getTownFroms().then((data) => data.SearchTour_TOWNFROMS);
  const daysDropdown = dropdown(".gtm-nights-select");
  const personDropdown = dropdown(".gtm-persons-select");

  const townSelectElement = document.querySelector(".gtm-town-select");
  const stateSelectElement = document.querySelector(".gtm-state-select");
  const submitBtn = document.querySelector(".gtm-item__button");

  const searchParams = {
    activeTown: 2,
    activeState: "",
    nightsFrom: 5,
    nightsTill: 10,
    checkInStart: "20251210", // +2
    checkInEnd: "20251219", // +1
    adultsCount: "2", // def 2
    childCount: "3", // def 0
    childAges: "5%2C16%2C3", //
  };

  let tourLink = "";
  let hotelLink = "";
  let ticketLink = "";
  let excursionLink = "";

  // Функция для обновления одного или нескольких параметров
  function updateTourLink(params = {}) {
    // Обновляем только переданные параметры
    Object.assign(searchParams, params);

    // Форматируем childAges если передан массив
    if (Array.isArray(searchParams.childAges)) {
      searchParams.childAges = searchParams.childAges.join("%2C");
    }

    // Форматируем даты если переданы как Date объекты
    if (searchParams.checkInStart instanceof Date) {
      const date = searchParams.checkInStart;
      searchParams.checkInStart =
        date.getFullYear() + String(date.getMonth() + 1).padStart(2, "0") + String(date.getDate()).padStart(2, "0");
    }

    if (searchParams.checkInEnd instanceof Date) {
      const date = searchParams.checkInEnd;
      searchParams.checkInEnd = date.getFullYear() + String(date.getMonth() + 1).padStart(2, "0") + String(date.getDate()).padStart(2, "0");
    }

    // Формируем новую ссылку
    tourLink = `https://online.bsigroup.ru/default.php?page=search_tour&TOWNFROMINC=${searchParams.activeTown}&STATEINC=${searchParams.activeState}&CHECKIN_BEG=${searchParams.checkInStart}&CHECKIN_END=${searchParams.checkInEnd}&NIGHTS_FROM=${searchParams.nightsFrom}&NIGHTS_TILL=${searchParams.nightsTill}&ADULT=${searchParams.adultsCount}&CHILD=${searchParams.childCount}&AGES=${searchParams.childAges}&DOLOAD=1`;
  }

  function updateHotelLink(params = {}) {}

  function updateTicketLink(params = {}) {}

  function updateExcursionLink(params = {}) {}

  updateTourLink();

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
      const encodedAges = ages.length > 0 ? ages.join(",").replaceAll(",", "%2C") : "";

      updateTourLink({
        adultsCount: String(adults),
        childCount: String(children),
        childAges: encodedAges,
      });
    },
  });

  submitBtn.addEventListener("click", () => {
    window.open(tourLink, "_blank");
  });

  const townSelect = new Choices(townSelectElement, {
    searchEnabled: true,
    loadingText: "Загрузка...",
    noResultsText: "Ничего не найдено",
    itemSelectText: "",
    noChoicesText: "",
  });

  const stateSelect = new Choices(stateSelectElement, {
    itemSelectText: "",
    loadingText: "Загрузка...",
    noResultsText: "Ничего не найдено",
    noChoicesText: "",
    searchEnabled: false,
  });

  // Flatpickr для выбора дат
  const datepick = document.querySelector(".gtm-datepicker");
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
      onChange: function (selectedDates) {
        if (selectedDates.length === 2) {
          updateTourLink({
            checkInStart: selectedDates[0],
            checkInEnd: selectedDates[1],
          });
        }
      },
    });
  }

  async function getStatesFormTown(townId) {
    try {
      stateSelect.clearChoices();
      stateSelect.clearStore();

      const states = await APIService.getStates(townId).then((data) => data.SearchTour_STATES);

      if (states && states.length > 0) {
        stateSelect.setChoices(
          states.map((item) => ({ value: item.id, label: item.name })),
          "value",
          "label",
          true
        );
        stateSelect.setChoiceByValue(states[0].id);
        updateTourLink({ activeState: states[0].id });
      }

      stateSelect.enable();
    } catch (error) {
      console.error("Ошибка загрузки стран:", error);
      stateSelect.clearChoices();
    }
  }

  if (towns && towns.length > 0) {
    townSelect.clearChoices();
    townSelect.setChoices(towns.map((item) => ({ value: item.id, label: item.name })));
    townSelect.setChoiceByValue(searchParams.activeTown);
  }

  townSelect.passedElement.element.addEventListener("choice", (e) => {
    updateTourLink({ activeTown: e.detail.value });
    getStatesFormTown(e.detail.value);
  });

  stateSelect.passedElement.element.addEventListener("choice", (e) => {
    updateTourLink({ activeState: e.detail.value });
  });

  getStatesFormTown(searchParams.activeTown);

  const tabsContainers = document.querySelectorAll(".gtm-search__section");
  tabsContainers.forEach((container) => {
    const tabButtons = container.querySelectorAll(".gtm-search__tab-btn");
    const tabContents = container.querySelectorAll(".gtm-search__item");

    const switchTab = (index) => {
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      tabContents.forEach((content) => content.classList.remove("active"));

      tabButtons[index].classList.add("active");
      tabContents[index].classList.add("active");
    };

    tabButtons.forEach((button, index) => {
      button.addEventListener("click", () => {
        switchTab(index);
      });
    });
  });

  // https://online.bsigroup.ru/default.php?page=search_tour&TOWNFROMINC=25&CHECKIN_BEG=20251028&CHECKIN_END=20251105&NIGHTS_FROM=12&NIGHTS_TILL=7&ADULT=3
};
