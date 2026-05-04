import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "../forms/dropdown.js";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  searchPlaceholderValue: "Поиск...",
};

const initEducationProgramAccordion = () => {
  const accordions = document.querySelectorAll(".js-education-program-accordion");
  const ANIM_MS = 300;
  
  accordions.forEach((accordion) => {
    const toggle = accordion.querySelector(".js-education-program-toggle");
    const content = accordion.querySelector(".js-education-program-content");
    
    if (!toggle || !content) return;
    
    const open = () => {
      accordion.classList.add("is-open");
      toggle.setAttribute("aria-expanded", "true");
      
      content.hidden = false;
      content.style.overflow = "hidden";
      content.style.willChange = "height";
      content.style.transition = `height ${ANIM_MS}ms ease`;
      
      const start = 0;
      const target = content.scrollHeight;
      
      content.style.height = `${start}px`;
      content.offsetHeight;
      
      content.style.height = `${target}px`;
      
      const onEnd = (e) => {
        if (e.target !== content) return;
        content.removeEventListener("transitionend", onEnd);
        content.style.height = "";
        content.style.overflow = "";
        content.style.willChange = "";
        content.style.transition = "";
      };
      
      content.addEventListener("transitionend", onEnd);
    };
    
    const close = () => {
      accordion.classList.remove("is-open");
      toggle.setAttribute("aria-expanded", "false");
      
      const start = content.scrollHeight;
      content.style.overflow = "hidden";
      content.style.willChange = "height";
      content.style.transition = `height ${ANIM_MS}ms ease`;
      content.style.height = `${start}px`;
      
      content.offsetHeight;
      content.style.height = "0px";
      
      const onEnd = (e) => {
        if (e.target !== content) return;
        content.removeEventListener("transitionend", onEnd);
        content.hidden = true;
        content.style.height = "";
        content.style.overflow = "";
        content.style.willChange = "";
        content.style.transition = "";
      };
      
      content.addEventListener("transitionend", onEnd);
    };
    
    toggle.addEventListener("click", (e) => {
      e.preventDefault();
      const isExpanded = toggle.getAttribute("aria-expanded") === "true";

      if (isExpanded) {
        close();
      } else {
        open();
      }
    });

    const datesBtn = accordion.querySelector(".js-dates-toggle");
    const datesExtra = accordion.querySelector(".education-program-card__dates-extra");

    if (datesBtn && datesExtra) {
      const labelMore = datesBtn.textContent.trim();

      datesBtn.addEventListener("click", () => {
        const isOpen = datesBtn.classList.contains("is-open");

        if (isOpen) {
          datesExtra.classList.remove("is-visible");
          datesBtn.classList.remove("is-open");
          datesBtn.textContent = labelMore;
        } else {
          datesExtra.classList.add("is-visible");
          datesBtn.classList.add("is-open");
          datesBtn.textContent = "скрыть";
        }
      });
    }
  });
};

export const initSingleEducationPrograms = () => {
  const root = document.querySelector(".js-education-programs");
  if (!root) return;

  const filters = root.querySelector(".js-education-programs-filters");
  const list = root.querySelector(".js-education-programs-list");
  if (!filters || !list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  // Получаем education_id из data-атрибута блока программ
  const educationId = parseInt(root.getAttribute("data-education-id") || "0", 10);
  
  if (!educationId) {
    return;
  }

  const ageSelect = filters.querySelector('select[name="program_age"]');
  const durationSelect = filters.querySelector('select[name="program_duration"]');
  const languageSelect = filters.querySelector('select[name="program_language"]');
  const sortContainer = root.querySelector('.single-education__programs-sort');
  const dateInput = filters.querySelector('input[name="program_date"]');

  let ageChoice = null;
  let durationChoice = null;
  let languageChoice = null;
  let sortDropdown = null;
  let currentSortValue = 'price_asc';

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  const toLocalYmd = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  };

  const YMD_RE = /^\d{4}-\d{2}-\d{2}$/;

  const programDatesMin = (root.getAttribute("data-program-dates-min") || "").trim();
  const programDatesMax = (root.getAttribute("data-program-dates-max") || "").trim();

  let datePickerInstance = null;

  // Функция проверки, есть ли активные фильтры
  const hasActiveFilters = () => {
    // Проверяем возраст (по умолчанию пусто)
    if (ageSelect && ageSelect.value) {
      return true;
    }

    // Проверяем длительность (по умолчанию пусто)
    if (durationSelect && durationSelect.value) {
      return true;
    }

    // Проверяем язык (по умолчанию пусто)
    if (languageSelect && languageSelect.value) {
      return true;
    }

    // Проверяем диапазон дат - любая выбранная дата считается активным фильтром
    if (datePickerInstance && datePickerInstance.selectedDates.length > 0) {
      return true;
    } else if (dateInput && dateInput.value) {
      // Fallback: проверяем значение в input
      // Если есть значение, значит даты были выбраны пользователем
      return true;
    }

    // Проверяем сортировку (по умолчанию "price_asc")
    if (currentSortValue !== 'price_asc') {
      return true;
    }

    return false;
  };

  // Функция обновления видимости кнопки сброса
  const updateResetButtonVisibility = () => {
    const resetButton = root.querySelector('.js-education-programs-reset');
    if (resetButton) {
      if (hasActiveFilters()) {
        resetButton.style.display = 'inline-flex';
      } else {
        resetButton.style.display = 'none';
      }
    }
  };

  const loadPrograms = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "education_programs_by_school");
      body.set("education_id", String(educationId));
      if (currentSortValue) {
        body.set("program_sort", currentSortValue);
      }

      if (ageSelect && ageSelect.value) {
        body.set("program_age", ageSelect.value);
      }

      if (durationSelect && durationSelect.value) {
        body.set("program_duration", durationSelect.value);
      }

      // Отправляем один выбранный язык
      if (languageSelect && languageSelect.value) {
        body.set("program_language", languageSelect.value);
      }

      // Отправляем диапазон дат (от и до)
      if (datePickerInstance && datePickerInstance.selectedDates.length > 0) {
        const selectedDates = datePickerInstance.selectedDates;
        if (selectedDates.length >= 1) {
          const dateFrom = toLocalYmd(selectedDates[0]);
          body.set("program_date_from", dateFrom);
          
          // Если выбраны обе даты (диапазон), отправляем date_to
          if (selectedDates.length >= 2) {
            const dateTo = toLocalYmd(selectedDates[1]);
            body.set("program_date_to", dateTo);
          }
        }
      } else if (dateInput && dateInput.value) {
        // Fallback для случая, когда дата введена вручную
        const dateValue = dateInput.value;
        if (dateValue.includes(' to ')) {
          const [from, to] = dateValue.split(' to ');
          body.set("program_date_from", from.trim());
          body.set("program_date_to", to.trim());
        } else {
          body.set("program_date_from", dateValue);
        }
      }

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      list.innerHTML = json.data.html || "";
      document.dispatchEvent(new CustomEvent('education:content-updated'));
      initEducationProgramAccordion();

      // Обновляем опции фильтров, если они пришли с сервера
      if (json.data.filter_options) {
        updateFilterOptions(json.data.filter_options);
      }
    } catch (e) {
      // Error handling without console output
    } finally {
      setLoading(false);
      // Обновляем видимость кнопки сброса после загрузки программ
      updateResetButtonVisibility();
    }
  };

  // Инициализация Choices.js для select полей
  if (ageSelect) {
    ageChoice = new Choices(ageSelect, {
      ...CHOICES_RU,
      shouldSort: false,
      placeholder: true,
      placeholderValue: "Показать все",
      searchEnabled: false, // Убираем поиск для возраста
    });
    ageSelect.addEventListener("change", loadPrograms);
  }

  if (durationSelect) {
    durationChoice = new Choices(durationSelect, {
      ...CHOICES_RU,
      shouldSort: false,
      placeholder: true,
      placeholderValue: "Показать все",
      searchEnabled: false, // Убираем поиск для длительности
    });
    durationSelect.addEventListener("change", loadPrograms);
  }

  if (languageSelect) {
    languageChoice = new Choices(languageSelect, {
      ...CHOICES_RU,
      shouldSort: false,
      placeholder: true,
      placeholderValue: "Показать все",
      searchEnabled: false, // Убираем поиск для языка
    });
    languageSelect.addEventListener("change", loadPrograms);
  }

  // Инициализация dropdown для сортировки
  if (sortContainer) {
    sortDropdown = dropdown(sortContainer);
    const sortTrigger = sortContainer.querySelector('.single-education__programs-sort-trigger');
    const sortText = sortContainer.querySelector('.single-education__programs-sort-text');
    const sortOptions = sortContainer.querySelectorAll('.single-education__programs-sort-option');

    sortOptions.forEach((option) => {
      option.addEventListener('click', (e) => {
        e.preventDefault();
        const value = option.getAttribute('data-value');
        const text = option.textContent.trim();
        
        currentSortValue = value;
        if (sortText) {
          sortText.textContent = text;
        }
        
        if (sortDropdown && sortDropdown.close) {
          sortDropdown.close();
        }
        
        loadPrograms();
      });
    });

    // Устанавливаем активное состояние для дефолтной опции
    const defaultOption = sortContainer.querySelector('.single-education__programs-sort-option[data-value="price_asc"]');
    if (defaultOption) {
      defaultOption.classList.add('is-active');
    }
  }

  // Функция для обновления опций фильтров
  const updateFilterOptions = (options) => {
    // Обновляем опции возраста - полный список всех доступных возрастов
    if (options.ages && ageChoice) {
      const currentValue = ageSelect.value;
      ageChoice.clearStore();
      // Добавляем опцию "Показать все" в начало списка
      const ageOptions = [
        { value: '', label: 'Показать все' },
        ...options.ages.map((age) => ({
          value: String(age),
          label: `${age} лет`,
        }))
      ];
      ageChoice.setChoices(ageOptions, "value", "label", true);
      // Проверяем, существует ли текущее значение в новых опциях
      if (currentValue && options.ages.includes(Number(currentValue))) {
        ageSelect.value = currentValue;
        ageChoice.setChoiceByValue(currentValue);
      } else {
        // Если текущее значение отсутствует в новых опциях, сбрасываем
        ageSelect.value = '';
        ageChoice.setChoiceByValue('');
      }
    }

    if (options.durations && durationChoice) {
      const currentValue = durationSelect.value;
      durationChoice.clearStore();
      // Добавляем опцию "Показать все" в начало списка
      const durationOptions = [
        { value: '', label: 'Показать все' },
        ...options.durations.map((dur) => ({
          value: String(dur),
          label: `${dur} ${dur === 1 ? 'неделя' : dur < 5 ? 'недели' : 'недель'}`,
        }))
      ];
      durationChoice.setChoices(durationOptions, "value", "label", true);
      // Проверяем, существует ли текущее значение в новых опциях
      if (currentValue && options.durations.includes(Number(currentValue))) {
        durationSelect.value = currentValue;
        durationChoice.setChoiceByValue(currentValue);
      } else {
        // Если текущее значение отсутствует в новых опциях, сбрасываем
        durationSelect.value = '';
        durationChoice.setChoiceByValue('');
      }
    }

    // Обновляем опции языков
    if (options.languages && languageChoice) {
      const currentValue = languageSelect.value;
      languageChoice.clearStore();
      // Добавляем опцию "Показать все" в начало списка
      const languageOptions = [
        { value: '', label: 'Показать все' },
        ...options.languages.map((lang) => ({
          value: String(lang.term_id),
          label: lang.name,
        }))
      ];
      languageChoice.setChoices(languageOptions, "value", "label", true);
      // Проверяем, существует ли текущее значение в новых опциях
      const currentLangExists = options.languages.some(
        (lang) => String(lang.term_id) === currentValue
      );
      if (currentValue && currentLangExists) {
        languageSelect.value = currentValue;
        languageChoice.setChoiceByValue(currentValue);
      } else {
        // Если текущее значение отсутствует в новых опциях, сбрасываем
        languageSelect.value = '';
        languageChoice.setChoiceByValue('');
      }
    }
  };

  // Загружаем начальные опции фильтров
  const loadInitialFilterOptions = async () => {
    try {
      const body = new URLSearchParams();
      body.set("action", "education_programs_filter_options");
      body.set("education_id", String(educationId));

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (json && json.success && json.data) {
        updateFilterOptions(json.data);
      }
    } catch (e) {
      // Error handling
    }
  };

  // Функция сброса фильтров
  const resetFilters = () => {
    // Сбрасываем возраст
    if (ageChoice && ageSelect) {
      ageChoice.setChoiceByValue('');
      ageSelect.value = '';
    }

    // Сбрасываем длительность
    if (durationChoice && durationSelect) {
      durationChoice.setChoiceByValue('');
      durationSelect.value = '';
    }

    // Сбрасываем язык
    if (languageChoice && languageSelect) {
      languageChoice.setChoiceByValue('');
      languageSelect.value = '';
    }

    // Очищаем диапазон дат
    if (datePickerInstance) {
      datePickerInstance.clear();
    }

    // Сбрасываем сортировку на "Цена: по возрастанию"
    if (sortContainer) {
      const sortText = sortContainer.querySelector('.single-education__programs-sort-text');
      const defaultOption = sortContainer.querySelector('.single-education__programs-sort-option[data-value="price_asc"]');
      if (sortText && defaultOption) {
        currentSortValue = 'price_asc';
        sortText.textContent = defaultOption.textContent.trim();
      }
    }

    // Загружаем программы с сброшенными фильтрами
    loadPrograms();
  };

  // Инициализация кнопки сброса фильтров
  const resetButton = root.querySelector('.js-education-programs-reset');
  if (resetButton) {
    resetButton.addEventListener('click', (e) => {
      e.preventDefault();
      resetFilters();
    });

    // Скрываем кнопку по умолчанию
    resetButton.style.display = 'none';
  }

  // Обновляем видимость при изменении каждого фильтра
  if (ageSelect) {
    ageSelect.addEventListener("change", () => {
      setTimeout(updateResetButtonVisibility, 0);
    });
  }
  if (durationSelect) {
    durationSelect.addEventListener("change", () => {
      setTimeout(updateResetButtonVisibility, 0);
    });
  }
  if (languageSelect) {
    languageSelect.addEventListener("change", () => {
      setTimeout(updateResetButtonVisibility, 0);
    });
  }
  if (dateInput) {
    dateInput.addEventListener("change", () => {
      setTimeout(updateResetButtonVisibility, 0);
    });
  }

  // Обновляем видимость при изменении сортировки
  if (sortContainer) {
    const sortOptions = sortContainer.querySelectorAll('.single-education__programs-sort-option');
    sortOptions.forEach((option) => {
      option.addEventListener('click', () => {
        setTimeout(updateResetButtonVisibility, 0);
      });
    });
  }

  // Инициализируем видимость кнопки при загрузке
  setTimeout(updateResetButtonVisibility, 100);

  // Инициализация flatpickr для даты заселения (режим диапазона)
  if (dateInput) {
    const flatpickrOptions = {
      locale: Russian,
      dateFormat: "Y-m-d",
      altInput: true,
      altFormat: "d.m",
      mode: "range",
      disableMobile: true,
      conjunction: " - ",
      altInputPlaceholder: "Выберите даты",
      onChange: () => {
        loadPrograms();
      },
    };

    if (YMD_RE.test(programDatesMin) && YMD_RE.test(programDatesMax) && programDatesMin <= programDatesMax) {
      flatpickrOptions.minDate = programDatesMin;
      flatpickrOptions.maxDate = programDatesMax;
    } else {
      flatpickrOptions.minDate = "today";
    }

    datePickerInstance = flatpickr(dateInput, flatpickrOptions);
    loadInitialFilterOptions();
  } else {
    loadInitialFilterOptions();
  }
  
  initEducationProgramAccordion();
};

