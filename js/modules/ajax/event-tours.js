import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { displayTourPrices } from "../services/priceLoader.js";

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
  let availableDates = [];

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  // Функции для работы с датами (из tour-prices.js)
  function formatDateYYYYMMDD(date) {
    if (!date) return "";
    const d = date instanceof Date ? date : new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${year}${month}${day}`;
  }

  function parseDateFromYYYYMMDD(dateStr) {
    if (!dateStr || dateStr.length !== 8) return null;
    const year = parseInt(dateStr.substring(0, 4));
    const month = parseInt(dateStr.substring(4, 6)) - 1;
    const day = parseInt(dateStr.substring(6, 8));
    return new Date(year, month, day);
  }

  // Загрузка доступных дат из самотура
  const loadAvailableDates = async () => {
    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_available_dates");
      body.set("event_tours_term_id", String(eventToursTermId));

      if (countrySelect && countrySelect.value) {
        body.set("country", countrySelect.value);
      }

      if (regionSelect && regionSelect.value) {
        body.set("region", regionSelect.value);
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

      // Сохраняем даты в формате YYYY-MM-DD (строки) для использования в enable
      const dateStrings = json.data.dates || [];
      availableDates = dateStrings;

      // Преобразуем также в Date объекты для других операций
      const dateObjects = dateStrings.map((dateStr) => {
        const parts = dateStr.split("-");
        if (parts.length === 3) {
          return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        }
        return null;
      }).filter(Boolean);

      // Обновляем flatpickr с доступными датами
      // Согласно документации Flatpickr, используем enable с массивом дат
      // ISO Date Strings (формат YYYY-MM-DD) всегда принимаются
      if (datePickerInstance) {
        if (dateStrings.length > 0) {
          // Используем enable с массивом строк дат в формате YYYY-MM-DD
          // Все остальные даты автоматически будут отключены
          datePickerInstance.set("enable", dateStrings);
        } else {
          // Если нет доступных дат, очищаем enable (все даты будут отключены)
          datePickerInstance.set("enable", []);
        }
        // Принудительно обновляем календарь
        datePickerInstance.redraw();
      }
    } catch (e) {
      // Error handling without console output
      availableDates = [];
    }
  };

  // Подсчет активных фильтров
  const countActiveFilters = () => {
    let count = 0;
    if (countrySelect && countrySelect.value) count++;
    if (regionSelect && regionSelect.value) count++;
    // Проверяем, выбраны ли даты в flatpickr
    if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length > 0) {
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

      // Отправляем выбранные даты (используем минимальную и максимальную из выбранных)
      if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length > 0) {
        const dates = datePickerInstance.selectedDates.map(d => d.toISOString().split("T")[0]).sort();
        const startDate = dates[0];
        const endDate = dates[dates.length - 1];
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

      // Загружаем цены для туров после отображения карточек
      const priceParams = {};
      if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length > 0) {
        const dates = datePickerInstance.selectedDates.map(d => d.toISOString().split("T")[0]).sort();
        priceParams.dateFrom = dates[0];
        priceParams.dateTo = dates[dates.length - 1];
      }
      await displayTourPrices(list, priceParams);
      
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

  // Инициализация flatpickr для выбора конкретных дат (не диапазон)
  if (departureDateInput) {
    datePickerInstance = flatpickr(departureDateInput, {
      mode: "multiple", // Множественный выбор конкретных дат
      locale: Russian,
      dateFormat: "d.m.Y",
      minDate: "today",
      disableMobile: true,
      enable: [], // Пустой массив отключит все даты до загрузки доступных
      onChange: async (selectedDates) => {
        if (selectedDates.length > 0) {
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

    // Загружаем доступные даты после инициализации (асинхронно)
    loadAvailableDates();
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

      // Отправляем выбранные даты (используем минимальную и максимальную из выбранных)
      if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length > 0) {
        const dates = datePickerInstance.selectedDates.map(d => d.toISOString().split("T")[0]).sort();
        const startDate = dates[0];
        const endDate = dates[dates.length - 1];
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
    await loadAvailableDates();
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
      await loadAvailableDates();
      await loadRegions();
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

    // Применяем даты из URL (если указан диапазон, выбираем обе даты)
    if (dateFrom && dateTo && datePickerInstance) {
      const startDate = new Date(dateFrom);
      const endDate = new Date(dateTo);
      // В режиме multiple выбираем обе даты, если они доступны
      datePickerInstance.setDate([startDate, endDate], false);
    }

    // Если в URL есть фильтры — применяем их сразу
    if (country || region || (dateFrom && dateTo)) {
      await loadTours();
    }
    
    updateResetButton();
  };

  // Загружаем доступные даты при инициализации (асинхронно, не блокируем)
  loadAvailableDates();
  
  await applyFromUrl();
  updateResetButton();

  // Загружаем цены для начальных туров на странице (до AJAX)
  if (list) {
    console.log('Загрузка цен для начальных туров...');
    await displayTourPrices(list);
  }
};
