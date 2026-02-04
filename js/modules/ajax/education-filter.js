import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "../forms/dropdown.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  addItemText: (value) => `Нажмите Enter, чтобы добавить «${value}»`,
  maxItemText: (maxItemCount) => `Можно выбрать максимум: ${maxItemCount}`,
  searchPlaceholderValue: "Поиск...",
};

export const initEducationFilter = () => {
  const root = document.querySelector(".js-education-page");
  if (!root) return;

  const form = root.querySelector(".js-education-filter");
  const list = root.querySelector(".js-education-list");
  const counter = root.querySelector(".js-education-counter");
  const pagination = root.querySelector(".js-education-pagination");
  if (!form || !list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const programSelect = form.querySelector('select[name="program"]');
  const languageSelect = form.querySelector('select[name="language"]');
  const countrySelect = form.querySelector('select[name="country"]');
  const typeSelect = form.querySelector('select[name="type"]');
  const accommodationSelect = form.querySelector('select[name="accommodation"]');
  const ageSelect = form.querySelector('select[name="age"]');
  const durationSelect = form.querySelector('select[name="duration"]');
  const dateRangeInput = form.querySelector('input[name="date_range"]');
  const dateFromInput = form.querySelector('input[name="date_from"]');
  const dateToInput = form.querySelector('input[name="date_to"]');
  const sortContainer = root.querySelector(".education-page__sort");
  const perPageContainer = root.querySelector(".education-page__per-page");
  const resetBtn = root.querySelector(".js-education-reset");
  const activeFiltersEl = root.querySelector(".js-education-active-filters");
  const activeFiltersCount = activeFiltersEl?.querySelector(".education-page__active-filters-count");
  const loadMoreWrap = root.querySelector(".js-education-load-more");
  const loadMoreButton = loadMoreWrap?.querySelector(".education-page__load-more-btn");

  let datePickerInstance = null;
  let sortDropdown = null;
  let perPageDropdown = null;
  let currentSortValue = 'title_asc';
  let currentPerPage = 12;
  let currentPage = parseInt(root.getAttribute('data-current-page') || '1', 10);
  let totalPages = parseInt(root.getAttribute('data-total-pages') || '1', 10);
  let isLoadingMore = false;

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  const countActiveFilters = () => {
    let count = 0;

    if (countrySelect?.value) count++;
    if (programSelect?.value) count++;
    if (languageSelect?.value) count++;
    if (typeSelect?.value) count++;
    if (accommodationSelect?.value) count++;
    if (ageSelect?.value) count++;
    if (durationSelect?.value) count++;
    if (dateFromInput?.value) count++;
    if (dateToInput?.value) count++;

    return count;
  };

  const hasActiveFilters = () => {
    return countActiveFilters() > 0;
  };

  const updateResetButton = () => {
    const count = countActiveFilters();

    if (resetBtn) {
      resetBtn.style.display = count > 0 ? "block" : "none";
    }

    if (activeFiltersEl && activeFiltersCount) {
      if (count > 0) {
        activeFiltersCount.textContent = count;
        activeFiltersEl.style.display = "block";
      } else {
        activeFiltersEl.style.display = "none";
      }
    }
  };

  const getValues = (sel) => {
    if (!sel) return [];
    return Array.from(sel.selectedOptions)
      .map((o) => o.value)
      .filter(Boolean)
      .filter((v) => v !== ''); // Исключаем пустое значение "Показать все"
  };

  const loadEducation = async (page = 1) => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "education_filter");
      body.set("paged", String(page));
      body.set("per_page", String(currentPerPage));

      if (currentSortValue) {
        body.set("sort", currentSortValue);
      }

      if (programSelect && programSelect.value) {
        body.set("program", programSelect.value);
      }
      if (languageSelect && languageSelect.value) {
        body.set("language", languageSelect.value);
      }
      if (typeSelect && typeSelect.value) {
        body.set("type", typeSelect.value);
      }
      if (accommodationSelect && accommodationSelect.value) {
        body.set("accommodation", accommodationSelect.value);
      }

      if (countrySelect && countrySelect.value) {
        body.set("country", countrySelect.value);
      }

      if (ageSelect && ageSelect.value) {
        body.set("age", ageSelect.value);
      }

      if (durationSelect && durationSelect.value) {
        body.set("duration", durationSelect.value);
      }

      if (dateFromInput && dateFromInput.value) {
        body.set("date_from", dateFromInput.value);
      }
      if (dateToInput && dateToInput.value) {
        body.set("date_to", dateToInput.value);
      }

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      if (page === 1) {
        list.innerHTML = json.data.html || "";
      } else {
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = json.data.html || "";
        while (tempDiv.firstChild) {
          list.appendChild(tempDiv.firstChild);
        }
      }

      if (counter) {
        counter.textContent = `Найдено школ: ${json.data.total || 0}`;
      }

      totalPages = json.data.pages || 1;
      currentPage = page;

      if (loadMoreWrap) {
        if (currentPage < totalPages) {
          loadMoreWrap.style.display = "block";
          isLoadingMore = false;
        } else {
          loadMoreWrap.style.display = "none";
        }
      }

      // Обновляем опции фильтров из отфильтрованных результатов
      if (json.data.filter_options && page === 1) {
        updateFilterOptionsFromResults(json.data.filter_options);
      }

      updateResetButton();
    } catch (e) {
    } finally {
      setLoading(false);
    }
  };

  const handleLoadMore = async () => {
    if (isLoadingMore || currentPage >= totalPages) return;

    isLoadingMore = true;
    if (loadMoreButton) {
      loadMoreButton.disabled = true;
      loadMoreButton.textContent = "Загрузка...";
    }

    await loadEducation(currentPage + 1);

    if (loadMoreButton) {
      loadMoreButton.disabled = false;
      loadMoreButton.textContent = "Показать еще";
    }

    // Убираем автоматический скролл - пользователь сам решит, нужно ли прокручивать страницу
  };

  const programChoice = programSelect
    ? new Choices(programSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Показать все",
      })
    : null;

  const languageChoice = languageSelect
    ? new Choices(languageSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Все языки",
      })
    : null;

  const ageChoice = ageSelect
    ? new Choices(ageSelect, {
        ...CHOICES_RU,
        searchEnabled: false,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Показать все",
      })
    : null;

  const durationChoice = durationSelect
    ? new Choices(durationSelect, {
        ...CHOICES_RU,
        searchEnabled: false,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Показать все",
      })
    : null;

  const typeChoice = typeSelect
    ? new Choices(typeSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Показать все",
      })
    : null;

  const accommodationChoice = accommodationSelect
    ? new Choices(accommodationSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Показать все",
      })
    : null;

  const countryChoice = countrySelect
    ? new Choices(countrySelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  if (dateRangeInput) {
    datePickerInstance = flatpickr(dateRangeInput, {
      mode: "range",
      locale: Russian,
      dateFormat: "d.m.Y",
      minDate: "today",
      disableMobile: true,
      onChange: (selectedDates) => {
        currentPage = 1;
        if (selectedDates.length === 2) {
          const startDate = selectedDates[0].toISOString().split("T")[0];
          const endDate = selectedDates[1].toISOString().split("T")[0];
          if (dateFromInput) dateFromInput.value = startDate;
          if (dateToInput) dateToInput.value = endDate;
          updateResetButton();
          loadEducation(1);
        } else if (selectedDates.length === 0) {
          if (dateFromInput) dateFromInput.value = "";
          if (dateToInput) dateToInput.value = "";
          updateResetButton();
          loadEducation(1);
        }
      },
    });
  }

  // Обновление опций фильтров на основе отфильтрованных результатов
  const updateFilterOptionsFromResults = (options) => {
    // Обновляем программы
    if (options.programs && programChoice && programSelect) {
      const currentValue = programSelect.value;
      programChoice.clearStore();
      const programOptions = [
        { value: '', label: 'Показать все' },
        ...(options.programs || []).map((p) => ({
          value: String(p.id),
          label: p.name,
        }))
      ];
      programChoice.setChoices(programOptions, "value", "label", true);
      // Проверяем, существует ли текущее значение в новых опциях
      const currentProgramExists = options.programs.some(
        (p) => String(p.id) === currentValue
      );
      if (currentValue && currentProgramExists) {
        programSelect.value = currentValue;
        programChoice.setChoiceByValue(currentValue);
      } else {
        programSelect.value = '';
        programChoice.setChoiceByValue('');
      }
    }

    // Обновляем языки
    if (options.languages && languageChoice && languageSelect) {
      const currentValue = languageSelect.value;
      languageChoice.clearStore();
      const languageOptions = [
        { value: '', label: 'Все языки' },
        ...(options.languages || []).map((l) => ({
          value: String(l.id),
          label: l.name,
        }))
      ];
      languageChoice.setChoices(languageOptions, "value", "label", true);
      const currentLangExists = options.languages.some(
        (l) => String(l.id) === currentValue
      );
      if (currentValue && currentLangExists) {
        languageSelect.value = currentValue;
        languageChoice.setChoiceByValue(currentValue);
      } else {
        languageSelect.value = '';
        languageChoice.setChoiceByValue('');
      }
    }

    // Обновляем типы
    if (options.types && typeChoice && typeSelect) {
      const currentValue = typeSelect.value;
      typeChoice.clearStore();
      const typeOptions = [
        { value: '', label: 'Показать все' },
        ...(options.types || []).map((t) => ({
          value: String(t.id),
          label: t.name,
        }))
      ];
      typeChoice.setChoices(typeOptions, "value", "label", true);
      const currentTypeExists = options.types.some(
        (t) => String(t.id) === currentValue
      );
      if (currentValue && currentTypeExists) {
        typeSelect.value = currentValue;
        typeChoice.setChoiceByValue(currentValue);
      } else {
        typeSelect.value = '';
        typeChoice.setChoiceByValue('');
      }
    }

    // Обновляем размещение
    if (options.accommodations && accommodationChoice && accommodationSelect) {
      const currentValue = accommodationSelect.value;
      accommodationChoice.clearStore();
      const accommodationOptions = [
        { value: '', label: 'Показать все' },
        ...(options.accommodations || []).map((a) => ({
          value: String(a.id),
          label: a.name,
        }))
      ];
      accommodationChoice.setChoices(accommodationOptions, "value", "label", true);
      const currentAccommodationExists = options.accommodations.some(
        (a) => String(a.id) === currentValue
      );
      if (currentValue && currentAccommodationExists) {
        accommodationSelect.value = currentValue;
        accommodationChoice.setChoiceByValue(currentValue);
      } else {
        accommodationSelect.value = '';
        accommodationChoice.setChoiceByValue('');
      }
    }

    // Обновляем возраста
    if (options.ages && ageChoice && ageSelect) {
      const currentValue = ageSelect.value;
      ageChoice.clearStore();
      const ageOptions = [
        { value: '', label: 'Показать все' },
        ...(options.ages || []).map((age) => ({
          value: String(age),
          label: `${age} лет`,
        }))
      ];
      ageChoice.setChoices(ageOptions, "value", "label", true);
      const currentAgeExists = options.ages.includes(Number(currentValue));
      if (currentValue && currentAgeExists) {
        ageSelect.value = currentValue;
        ageChoice.setChoiceByValue(currentValue);
      } else {
        ageSelect.value = '';
        ageChoice.setChoiceByValue('');
      }
    }

    // Обновляем длительность
    if (options.durations && durationChoice && durationSelect) {
      const currentValue = durationSelect.value;
      durationChoice.clearStore();
      const durationOptions = [
        { value: '', label: 'Показать все' },
        ...(options.durations || []).map((dur) => ({
          value: String(dur),
          label: `${dur} ${dur === 1 ? 'неделя' : dur < 5 ? 'недели' : 'недель'}`,
        }))
      ];
      durationChoice.setChoices(durationOptions, "value", "label", true);
      const currentDurationExists = options.durations.includes(Number(currentValue));
      if (currentValue && currentDurationExists) {
        durationSelect.value = currentValue;
        durationChoice.setChoiceByValue(currentValue);
      } else {
        durationSelect.value = '';
        durationChoice.setChoiceByValue('');
      }
    }

    // Обновляем страны (если нужно)
    if (options.countries && countryChoice && countrySelect) {
      const currentValue = countrySelect.value;
      countryChoice.clearStore();
      const countryOptions = [
        { value: '', label: 'Все страны' },
        ...(options.countries || []).map((c) => ({
          value: String(c.id),
          label: c.name,
        }))
      ];
      countryChoice.setChoices(countryOptions, "value", "label", true);
      const currentCountryExists = options.countries.some(
        (c) => String(c.id) === currentValue
      );
      if (currentValue && currentCountryExists) {
        countrySelect.value = currentValue;
        countryChoice.setChoiceByValue(currentValue);
      } else {
        countrySelect.value = '';
        countryChoice.setChoiceByValue('');
      }
    }
  };

  const updateFilterOptions = async (countryId) => {
    try {
      const body = new URLSearchParams();
      body.set("action", "education_filter_options");
      body.set("country_id", countryId || "0");

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      const data = json.data || {};

      if (programChoice && programSelect) {
        const currentValue = programSelect.value;
        programChoice.clearStore();
        const programOptions = [
          { value: '', label: 'Показать все' },
          ...(data.programs || []).map((p) => ({
            value: String(p.id),
            label: p.name,
          }))
        ];
        programChoice.setChoices(programOptions, "value", "label", true);
        if (currentValue) {
          programSelect.value = currentValue;
          programChoice.setChoiceByValue(currentValue);
        }
      }

      if (languageChoice && languageSelect) {
        const currentValue = languageSelect.value;
        languageChoice.clearStore();
        const languageOptions = [
          { value: '', label: 'Все языки' },
          ...(data.languages || []).map((l) => ({
            value: String(l.id),
            label: l.name,
          }))
        ];
        languageChoice.setChoices(languageOptions, "value", "label", true);
        if (currentValue) {
          languageSelect.value = currentValue;
          languageChoice.setChoiceByValue(currentValue);
        }
      }

      if (typeChoice && typeSelect) {
        const currentValue = typeSelect.value;
        typeChoice.clearStore();
        const typeOptions = [
          { value: '', label: 'Показать все' },
          ...(data.types || []).map((t) => ({
            value: String(t.id),
            label: t.name,
          }))
        ];
        typeChoice.setChoices(typeOptions, "value", "label", true);
        if (currentValue) {
          typeSelect.value = currentValue;
          typeChoice.setChoiceByValue(currentValue);
        }
      }

      if (accommodationChoice && accommodationSelect) {
        const currentValue = accommodationSelect.value;
        accommodationChoice.clearStore();
        const accommodationOptions = [
          { value: '', label: 'Показать все' },
          ...(data.accommodations || []).map((a) => ({
            value: String(a.id),
            label: a.name,
          }))
        ];
        accommodationChoice.setChoices(accommodationOptions, "value", "label", true);
        if (currentValue) {
          accommodationSelect.value = currentValue;
          accommodationChoice.setChoiceByValue(currentValue);
        }
      }
    } catch (e) {}
  };

  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const program = params.get("program");
    const language = params.get("language");
    const type = params.get("type");
    const accommodation = params.get("accommodation");
    const country = params.get("country");
    const age = params.get("age");
    const duration = params.get("duration");
    const dateFrom = params.get("date_from");
    const dateTo = params.get("date_to");
    const sort = params.get("sort");

    const countryId = country || "";
    await updateFilterOptions(countryId);

    if (country && countryChoice) {
      countryChoice.setChoiceByValue(country);
    }

    if (program && programChoice) {
      programSelect.value = program;
      programChoice.setChoiceByValue(program);
    }

    if (language && languageChoice) {
      languageSelect.value = language;
      languageChoice.setChoiceByValue(language);
    }

    if (age && ageChoice) {
      ageSelect.value = age;
      ageChoice.setChoiceByValue(age);
    }

    if (duration && durationChoice) {
      durationSelect.value = duration;
      durationChoice.setChoiceByValue(duration);
    }

    if (type && typeChoice) {
      typeSelect.value = type;
      typeChoice.setChoiceByValue(type);
    }

    if (accommodation && accommodationChoice) {
      accommodationSelect.value = accommodation;
      accommodationChoice.setChoiceByValue(accommodation);
    }

    if (dateFrom && dateTo && dateFromInput && dateToInput && datePickerInstance) {
      dateFromInput.value = dateFrom;
      dateToInput.value = dateTo;
      const startDate = new Date(dateFrom);
      const endDate = new Date(dateTo);
      datePickerInstance.setDate([startDate, endDate], false);
    } else if (dateFrom && dateTo && dateFromInput && dateToInput) {
      dateFromInput.value = dateFrom;
      dateToInput.value = dateTo;
    }

    if (sort && sortContainer) {
      currentSortValue = sort;
      const sortText = sortContainer.querySelector('.education-page__sort-text');
      const sortOptions = sortContainer.querySelectorAll('.education-page__sort-option');
      const selectedOption = sortContainer.querySelector(`.education-page__sort-option[data-value="${sort}"]`);
      
      if (selectedOption) {
        const text = selectedOption.textContent.trim();
        if (sortText) {
          sortText.textContent = text;
        }
        sortOptions.forEach((opt) => opt.classList.remove('is-active'));
        selectedOption.classList.add('is-active');
      }
    }

    const hasFilters =
      program ||
      language ||
      type ||
      accommodation ||
      country ||
      age ||
      duration ||
      dateFrom ||
      dateTo ||
      sort;

    updateResetButton();

    if (hasFilters) {
      await loadEducation(1);
    }
  };

  if (programChoice) {
    programSelect.addEventListener("change", () => {
      currentPage = 1;
      updateResetButton();
      loadEducation(1);
    });
  }
  if (languageChoice) {
    languageSelect.addEventListener("change", () => {
      currentPage = 1;
      updateResetButton();
      loadEducation(1);
    });
  }
  if (ageChoice) {
    ageSelect.addEventListener("change", () => {
      currentPage = 1;
      updateResetButton();
      loadEducation(1);
    });
  }
  if (durationChoice) {
    durationSelect.addEventListener("change", () => {
      currentPage = 1;
      updateResetButton();
      loadEducation(1);
    });
  }
  if (typeChoice) {
    typeSelect.addEventListener("change", () => {
      currentPage = 1;
      updateResetButton();
      loadEducation(1);
    });
  }
  if (accommodationChoice) {
    accommodationSelect.addEventListener("change", () => {
      currentPage = 1;
      updateResetButton();
      loadEducation(1);
    });
  }
  if (countryChoice) {
    countrySelect.addEventListener("change", async () => {
      currentPage = 1;
      const countryId = countrySelect.value || "";
      if (programChoice) {
        programSelect.value = "";
        programChoice.setChoiceByValue("");
      }
      if (languageChoice) {
        languageSelect.value = "";
        languageChoice.setChoiceByValue("");
      }
      if (typeChoice) {
        typeSelect.value = "";
        typeChoice.setChoiceByValue("");
      }
      if (accommodationChoice) {
        accommodationSelect.value = "";
        accommodationChoice.setChoiceByValue("");
      }

      await updateFilterOptions(countryId);
      updateResetButton();
      loadEducation(1);
    });
  }

  // Инициализация dropdown для сортировки
  if (sortContainer) {
    sortDropdown = dropdown(sortContainer);
    const sortTrigger = sortContainer.querySelector('.education-page__sort-trigger');
    const sortText = sortContainer.querySelector('.education-page__sort-text');
    const sortOptions = sortContainer.querySelectorAll('.education-page__sort-option');

    sortOptions.forEach((option) => {
      option.addEventListener('click', (e) => {
        e.preventDefault();
        const value = option.getAttribute('data-value');
        const text = option.textContent.trim();
        
        currentSortValue = value;
        if (sortText) {
          sortText.textContent = text;
        }
        
        // Убираем активное состояние со всех опций
        sortOptions.forEach((opt) => opt.classList.remove('is-active'));
        // Добавляем активное состояние выбранной опции
        option.classList.add('is-active');
        
        if (sortDropdown && sortDropdown.close) {
          sortDropdown.close();
        }
        
        currentPage = 1;
        updateResetButton();
        loadEducation(1);
      });
    });

    // Устанавливаем активное состояние для дефолтной опции
    const defaultOption = sortContainer.querySelector('.education-page__sort-option[data-value="title_asc"]');
    if (defaultOption) {
      defaultOption.classList.add('is-active');
    }
  }

  // Инициализация dropdown для выбора количества элементов
  if (perPageContainer) {
    perPageDropdown = dropdown(perPageContainer);
    const perPageTrigger = perPageContainer.querySelector('.education-page__per-page-trigger');
    const perPageText = perPageContainer.querySelector('.education-page__per-page-text');
    const perPageOptions = perPageContainer.querySelectorAll('.education-page__per-page-option');

    perPageOptions.forEach((option) => {
      option.addEventListener('click', (e) => {
        e.preventDefault();
        const value = parseInt(option.getAttribute('data-value'), 10);
        const text = `Показать: ${value}`;
        
        currentPerPage = value;
        if (perPageText) {
          perPageText.textContent = text;
        }
        
        // Убираем активное состояние со всех опций
        perPageOptions.forEach((opt) => opt.classList.remove('is-active'));
        // Добавляем активное состояние выбранной опции
        option.classList.add('is-active');
        
        if (perPageDropdown && perPageDropdown.close) {
          perPageDropdown.close();
        }
        
        currentPage = 1;
        loadEducation(1);
      });
    });

    // Устанавливаем активное состояние для дефолтной опции (12)
    const defaultPerPageOption = perPageContainer.querySelector('.education-page__per-page-option[data-value="12"]');
    if (defaultPerPageOption) {
      defaultPerPageOption.classList.add('is-active');
    }
  }

  if (resetBtn) {
    resetBtn.addEventListener("click", () => {
      currentPage = 1;
      // Сбрасываем все фильтры
      if (countryChoice) countryChoice.setChoiceByValue("");
      if (programChoice) {
        programSelect.value = "";
        programChoice.setChoiceByValue("");
      }
      if (languageChoice) {
        languageSelect.value = "";
        languageChoice.setChoiceByValue("");
      }
      if (typeChoice) {
        typeSelect.value = "";
        typeChoice.setChoiceByValue("");
      }
      if (accommodationChoice) {
        accommodationSelect.value = "";
        accommodationChoice.setChoiceByValue("");
      }

      if (ageChoice) {
        ageSelect.value = "";
        ageChoice.setChoiceByValue("");
      }

      if (durationChoice) {
        durationSelect.value = "";
        durationChoice.setChoiceByValue("");
      }

      if (datePickerInstance) {
        datePickerInstance.clear();
      }
      if (dateFromInput) dateFromInput.value = "";
      if (dateToInput) dateToInput.value = "";

      // Сбрасываем сортировку
      currentSortValue = 'title_asc';
      if (sortContainer) {
        const sortText = sortContainer.querySelector('.education-page__sort-text');
        const sortOptions = sortContainer.querySelectorAll('.education-page__sort-option');
        if (sortText) {
          sortText.textContent = 'По названию (А-Я)';
        }
        sortOptions.forEach((opt) => opt.classList.remove('is-active'));
        const defaultOption = sortContainer.querySelector('.education-page__sort-option[data-value="title_asc"]');
        if (defaultOption) {
          defaultOption.classList.add('is-active');
        }
      }

      updateFilterOptions("");
      updateResetButton();
      loadEducation(1);
    });
  }

  // При загрузке страницы обновляем опции фильтров (для всех школ, если страна не выбрана)
  updateFilterOptions("");
  updateResetButton();

  // Обновляем видимость кнопки "Загрузить еще" при инициализации
  if (loadMoreWrap) {
    if (currentPage < totalPages) {
      loadMoreWrap.style.display = "block";
    } else {
      loadMoreWrap.style.display = "none";
    }
  }

  // Обработчик кнопки "Показать еще"
  if (loadMoreButton) {
    loadMoreButton.addEventListener("click", handleLoadMore);
  }



  applyFromUrl();
};
