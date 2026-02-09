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

export const initEventToursFilters = () => {
  const root = document.querySelector("[data-event-tours-filter]");
  if (!root) return;

  const list = document.querySelector("[data-tours-list]");
  const count = document.querySelector("[data-tours-count]");
  if (!list) return;

  const eventToursTermId = parseInt(
    root.getAttribute("data-event-tours-term-id") || "0",
    10
  );
  if (!eventToursTermId) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const countrySelect = root.querySelector('select[name="country"]');
  const regionSelect = root.querySelector('select[name="region"]');
  const departureDateInput = root.querySelector('input[name="departure_date"]');
  const resetBtn = root.querySelector(".js-tours-reset");

  let datePickerInstance = null;

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  // Подсчет активных фильтров
  const countActiveFilters = () => {
    let count = 0;
    if (countrySelect && countrySelect.value) count++;
    if (regionSelect && regionSelect.value) count++;
    // Проверяем, выбраны ли даты в flatpickr
    if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length === 2) {
      count++;
    }
    return count;
  };

  // Обновление видимости кнопки сброса
  const updateResetButton = () => {
    const count = countActiveFilters();
    if (resetBtn) {
      resetBtn.style.display = count > 0 ? "block" : "none";
    }
  };

  const loadTours = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_filter");
      body.set("event_tours_term_id", String(eventToursTermId));

      if (countrySelect && countrySelect.value) {
        body.set("country", countrySelect.value);
      }

      if (regionSelect && regionSelect.value) {
        body.set("region", regionSelect.value);
      }

      // Отправляем диапазон дат
      if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length === 2) {
        const startDate = datePickerInstance.selectedDates[0].toISOString().split("T")[0];
        const endDate = datePickerInstance.selectedDates[1].toISOString().split("T")[0];
        body.set("date_from", startDate);
        body.set("date_to", endDate);
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
      if (count)
        count.textContent = `Найдено туров: ${json.data.total || 0}`;
      
      updateResetButton();
    } catch (e) {
      // Error handling without console output
    } finally {
      setLoading(false);
    }
  };

  // Инициализация Choices.js
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

  // Инициализация flatpickr для диапазона дат
  if (departureDateInput) {
    datePickerInstance = flatpickr(departureDateInput, {
      mode: "range",
      locale: Russian,
      dateFormat: "d.m.Y",
      minDate: "today",
      disableMobile: true,
      onChange: async (selectedDates) => {
        if (selectedDates.length === 2) {
          await loadCountries();
          await loadTours();
          updateResetButton();
        } else if (selectedDates.length === 0) {
          await loadCountries();
          await loadTours();
          updateResetButton();
        }
      },
    });
  }

  const loadRegions = async () => {
    if (!regionSelect || !countrySelect) return;

    const countryId = countrySelect.value || "";

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_regions");
      body.set("country_id", String(countryId));

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
      } else if (regionSelect) {
        // Если Choices.js не инициализирован, обновляем напрямую
        regionSelect.innerHTML = '<option value="">Все регионы</option>';
        if (json.data.items && json.data.items.length > 0) {
          json.data.items.forEach((it) => {
            const option = document.createElement("option");
            option.value = String(it.id);
            option.textContent = it.text;
            regionSelect.appendChild(option);
          });
        }
      }
    } catch (e) {
      // Error handling without console output
    }
  };

  const loadCountries = async () => {
    if (!countrySelect) return;

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_countries");
      body.set("event_tours_term_id", String(eventToursTermId));

      const regionId = regionSelect ? regionSelect.value || "" : "";
      if (regionId) body.set("region", regionId);

      if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length === 2) {
        const startDate = datePickerInstance.selectedDates[0].toISOString().split("T")[0];
        const endDate = datePickerInstance.selectedDates[1].toISOString().split("T")[0];
        body.set("date_from", startDate);
        body.set("date_to", endDate);
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
        const choices = [{ value: "", label: "Все страны", selected: !currentValue }];
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
      } else if (countrySelect) {
        const currentValue = countrySelect.value || "";
        countrySelect.innerHTML = '<option value="">Все страны</option>';
        if (json.data.items && json.data.items.length > 0) {
          json.data.items.forEach((it) => {
            const option = document.createElement("option");
            option.value = String(it.id);
            option.textContent = it.text;
            option.selected = currentValue === String(it.id);
            countrySelect.appendChild(option);
          });
        }
      }
    } catch (e) {
      // Error handling without console output
    }
  };

  // Функция сброса фильтров
  const resetFilters = async () => {
    // Сбрасываем все фильтры
    if (countryChoice) {
      countryChoice.setChoiceByValue("");
    } else if (countrySelect) {
      countrySelect.value = "";
    }

    if (regionChoice) {
      regionChoice.setChoiceByValue("");
    } else if (regionSelect) {
      regionSelect.value = "";
    }

    if (datePickerInstance) {
      datePickerInstance.clear();
    } else if (departureDateInput) {
      departureDateInput.value = "";
    }

    // Очищаем URL параметры
    const url = new URL(window.location.href);
    url.searchParams.delete("country");
    url.searchParams.delete("region");
    url.searchParams.delete("date_from");
    url.searchParams.delete("date_to");
    window.history.pushState({}, "", url);

    // Загружаем все регионы и страны (без фильтров)
    if (regionChoice && countrySelect) {
      await loadRegions();
    }
    await loadCountries();

    // Перезагружаем туры
    await loadTours();
  };

  // Обработчик кнопки сброса
  if (resetBtn) {
    resetBtn.addEventListener("click", resetFilters);
  }

  // Обработчики событий
  if (countrySelect) {
    countrySelect.addEventListener("change", async () => {
      await loadRegions();
      await loadTours();
      updateResetButton();
    });
  }

  if (regionSelect) {
    regionSelect.addEventListener("change", async () => {
      await loadCountries();
      await loadTours();
      updateResetButton();
    });
  }

  // Обработчик для flatpickr уже настроен в onChange при инициализации

  // Применение фильтров из URL
  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const country = params.get("country") ? String(params.get("country")) : "";
    const region = params.get("region") ? String(params.get("region")) : "";
    const dateFrom = params.get("date_from") ? String(params.get("date_from")) : "";
    const dateTo = params.get("date_to") ? String(params.get("date_to")) : "";

    if (country && countryChoice) {
      countryChoice.setChoiceByValue(country);
    } else if (country && countrySelect) {
      countrySelect.value = country;
    }

    if (country) {
      await loadRegions();
    }

    if (region && regionChoice) {
      regionChoice.setChoiceByValue(region);
    } else if (region && regionSelect) {
      regionSelect.value = region;
    }

    // Применяем диапазон дат из URL
    if (dateFrom && dateTo && datePickerInstance) {
      const startDate = new Date(dateFrom);
      const endDate = new Date(dateTo);
      datePickerInstance.setDate([startDate, endDate], false);
    }

    // Если в URL есть фильтры — применяем их сразу
    if (country || region || (dateFrom && dateTo)) {
      await loadTours();
    }
    
    updateResetButton();
  };

  applyFromUrl();
  updateResetButton();
};
