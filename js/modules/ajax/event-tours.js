import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  addItemText: (value) => `Нажмите Enter, чтобы добавить «${value}»`,
  maxItemText: (maxItemCount) => `Можно выбрать максимум: ${maxItemCount}`,
  searchPlaceholderValue: "Поиск...",
};

export const initEventToursFilters = async () => {
  const root = document.querySelector("[data-event-tours-filter]");
  if (!root) return;

  const list = document.querySelector("[data-tours-list]");
  const count = document.querySelector("[data-tours-count]");
  if (!list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const countrySelect = root.querySelector('select[name="country"]');
  const regionSelect = root.querySelector('select[name="region"]');
  const tourTypeSelect = root.querySelector('select[name="tour_type"]');
  const departureDateInput = root.querySelector(
    'input[name="departure_date"]'
  );
  const resetBtn = root.querySelector(".js-tours-reset");

  let datePickerInstance = null;
  let availableDateStrings = []; // YYYY-MM-DD strings for enable

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  // Подсчет активных фильтров
  const countActiveFilters = () => {
    let c = 0;
    if (countrySelect && countrySelect.value) c++;
    if (regionSelect && regionSelect.value) c++;
    if (tourTypeSelect && tourTypeSelect.value) c++;
    if (
      datePickerInstance &&
      datePickerInstance.selectedDates &&
      datePickerInstance.selectedDates.length > 0
    )
      c++;
    return c;
  };

  const updateResetButton = () => {
    if (resetBtn) {
      resetBtn.style.display = countActiveFilters() > 0 ? "block" : "none";
    }
  };

  // --- Загрузка доступных дат ---
  const loadAvailableDates = async () => {
    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_available_dates");

      if (countrySelect && countrySelect.value)
        body.set("country", countrySelect.value);
      if (regionSelect && regionSelect.value)
        body.set("region", regionSelect.value);
      if (tourTypeSelect && tourTypeSelect.value)
        body.set("tour_type", tourTypeSelect.value);

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      availableDateStrings = json.data.dates || [];

      if (datePickerInstance) {
        datePickerInstance.set(
          "enable",
          availableDateStrings.length > 0 ? availableDateStrings : []
        );
        datePickerInstance.redraw();
      }
    } catch (e) {
      availableDateStrings = [];
    }
  };

  // --- Загрузка туров ---
  const loadTours = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_filter");

      if (countrySelect && countrySelect.value)
        body.set("country", countrySelect.value);
      if (regionSelect && regionSelect.value)
        body.set("region", regionSelect.value);
      if (tourTypeSelect && tourTypeSelect.value)
        body.set("tour_type", tourTypeSelect.value);

      if (
        datePickerInstance &&
        datePickerInstance.selectedDates &&
        datePickerInstance.selectedDates.length === 2
      ) {
        const dates = datePickerInstance.selectedDates
          .map((d) => d.toISOString().split("T")[0])
          .sort();
        body.set("date_from", dates[0]);
        body.set("date_to", dates[1]);
      }

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      list.innerHTML = json.data.html || "";
      if (count) count.textContent = `Найдено туров: ${json.data.total || 0}`;

      updateResetButton();
    } catch (e) {
      // Error handling
    } finally {
      setLoading(false);
    }
  };

  // --- Choices.js ---
  const countryChoice = countrySelect
    ? new Choices(countrySelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  const regionChoice = regionSelect
    ? new Choices(regionSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  const tourTypeChoice = tourTypeSelect
    ? new Choices(tourTypeSelect, {
        ...CHOICES_RU,
        searchEnabled: false,
        shouldSort: false,
      })
    : null;

  // --- Flatpickr (range, только доступные даты) ---
  if (departureDateInput) {
    datePickerInstance = flatpickr(departureDateInput, {
      mode: "range",
      locale: Russian,
      dateFormat: "d.m.Y",
      minDate: "today",
      disableMobile: true,
      enable: [], // пусто до загрузки
      onChange: async (selectedDates) => {
        if (selectedDates.length === 2) {
          await loadTours();
        }
        updateResetButton();
      },
    });
  }

  // --- Загрузка регионов ---
  const loadRegions = async () => {
    if (!regionSelect || !countrySelect) return;

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_regions");
      body.set("country_id", countrySelect.value || "");

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      if (regionChoice) {
        regionChoice.clearStore();
        const choices = [{ value: "", label: "Все регионы", selected: true }];
        if (json.data.items && json.data.items.length > 0) {
          choices.push(
            ...json.data.items.map((it) => ({
              value: String(it.id),
              label: it.text,
            }))
          );
        }
        regionChoice.setChoices(choices, "value", "label", true);
      }
    } catch (e) {
      // Error handling
    }
  };

  // --- Загрузка стран ---
  const loadCountries = async () => {
    if (!countrySelect) return;

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_countries");

      const regionId = regionSelect ? regionSelect.value || "" : "";
      if (regionId) body.set("region", regionId);

      if (
        datePickerInstance &&
        datePickerInstance.selectedDates &&
        datePickerInstance.selectedDates.length === 2
      ) {
        const dates = datePickerInstance.selectedDates
          .map((d) => d.toISOString().split("T")[0])
          .sort();
        body.set("date_from", dates[0]);
        body.set("date_to", dates[1]);
      }

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      if (countryChoice) {
        const currentValue = countrySelect.value || "";
        countryChoice.clearStore();
        const choices = [
          { value: "", label: "Все страны", selected: !currentValue },
        ];
        if (json.data.items && json.data.items.length > 0) {
          choices.push(
            ...json.data.items.map((it) => ({
              value: String(it.id),
              label: it.text,
              selected: currentValue === String(it.id),
            }))
          );
        }
        countryChoice.setChoices(choices, "value", "label", true);
      }
    } catch (e) {
      // Error handling
    }
  };

  // --- Сброс ---
  const resetFilters = async () => {
    if (countryChoice) countryChoice.setChoiceByValue("");
    else if (countrySelect) countrySelect.value = "";

    if (regionChoice) regionChoice.setChoiceByValue("");
    else if (regionSelect) regionSelect.value = "";

    if (tourTypeChoice) tourTypeChoice.setChoiceByValue("");
    else if (tourTypeSelect) tourTypeSelect.value = "";

    if (datePickerInstance) datePickerInstance.clear();
    else if (departureDateInput) departureDateInput.value = "";

    if (regionChoice && countrySelect) await loadRegions();
    await loadAvailableDates();
    await loadCountries();
    await loadTours();
  };

  if (resetBtn) resetBtn.addEventListener("click", resetFilters);

  // --- Обработчики ---
  if (countrySelect) {
    countrySelect.addEventListener("change", async () => {
      await loadRegions();
      await loadAvailableDates();
      await loadTours();
      updateResetButton();
    });
  }

  if (regionSelect) {
    regionSelect.addEventListener("change", async () => {
      await loadAvailableDates();
      await loadCountries();
      await loadTours();
      updateResetButton();
    });
  }

  if (tourTypeSelect) {
    tourTypeSelect.addEventListener("change", async () => {
      await loadAvailableDates();
      await loadTours();
      updateResetButton();
    });
  }

  // --- URL фильтры ---
  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const country = params.get("country") || "";
    const region = params.get("region") || "";
    const tourType = params.get("tour_type") || "";

    if (country && countryChoice) countryChoice.setChoiceByValue(country);
    if (country) await loadRegions();
    if (region && regionChoice) regionChoice.setChoiceByValue(region);
    if (tourType && tourTypeChoice) tourTypeChoice.setChoiceByValue(tourType);

    if (country || region || tourType) await loadTours();

    updateResetButton();
  };

  // Загружаем доступные даты при инициализации
  await loadAvailableDates();
  await applyFromUrl();
  updateResetButton();
};
