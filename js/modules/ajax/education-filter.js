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

export const initEducationFilter = () => {
  const root = document.querySelector(".js-education-archive");
  if (!root) return;

  const form = root.querySelector(".js-education-filter");
  const list = root.querySelector(".js-education-list");
  const counter = root.querySelector(".js-education-counter");
  const pagination = root.querySelector(".js-education-pagination");
  if (!form || !list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const programSelect = form.querySelector('select[name="program[]"]');
  const languageSelect = form.querySelector('select[name="language[]"]');
  const countrySelect = form.querySelector('select[name="country"]');
  const typeSelect = form.querySelector('select[name="type[]"]');
  const accommodationSelect = form.querySelector('select[name="accommodation[]"]');
  const ageMinInput = form.querySelector('input[name="age_min"]');
  const ageMaxInput = form.querySelector('input[name="age_max"]');
  const durationMinInput = form.querySelector('input[name="duration_min"]');
  const durationMaxInput = form.querySelector('input[name="duration_max"]');
  const dateRangeInput = form.querySelector('input[name="date_range"]');
  const dateFromInput = form.querySelector('input[name="date_from"]');
  const dateToInput = form.querySelector('input[name="date_to"]');
  const sortSelect = root.querySelector('.js-education-sort');
  const resetBtn = root.querySelector('.js-education-reset');
  const activeFiltersEl = root.querySelector('.js-education-active-filters');
  const activeFiltersCount = activeFiltersEl?.querySelector('.education-archive__active-filters-count');
  const loadMoreWrap = root.querySelector('.js-education-load-more');
  const loadMoreButton = loadMoreWrap?.querySelector('.education-archive__load-more-btn');

  let datePickerInstance = null;
  let currentPage = 1;
  let totalPages = 1;
  let isLoadingMore = false;

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);
  
  const countActiveFilters = () => {
    let count = 0;
    
    if (countrySelect?.value) count++;
    if (getValues(programSelect).length) count += getValues(programSelect).length;
    if (getValues(languageSelect).length) count += getValues(languageSelect).length;
    if (getValues(typeSelect).length) count += getValues(typeSelect).length;
    if (getValues(accommodationSelect).length) count += getValues(accommodationSelect).length;
    if (ageMinInput?.value) count++;
    if (ageMaxInput?.value) count++;
    if (durationMinInput?.value) count++;
    if (durationMaxInput?.value) count++;
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
      .filter(Boolean);
  };

  const loadEducation = async (page = 1) => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "education_filter");
      body.set("paged", String(page));
      
      if (sortSelect && sortSelect.value) {
        body.set("sort", sortSelect.value);
      }

      getValues(programSelect).forEach((v) => body.append("program[]", v));
      getValues(languageSelect).forEach((v) => body.append("language[]", v));
      getValues(typeSelect).forEach((v) => body.append("type[]", v));
      getValues(accommodationSelect).forEach((v) => body.append("accommodation[]", v));

      if (countrySelect && countrySelect.value) {
        body.set("country", countrySelect.value);
      }

      if (ageMinInput && ageMinInput.value) {
        body.set("age_min", ageMinInput.value);
      }
      if (ageMaxInput && ageMaxInput.value) {
        body.set("age_max", ageMaxInput.value);
      }

      if (durationMinInput && durationMinInput.value) {
        body.set("duration_min", durationMinInput.value);
      }
      if (durationMaxInput && durationMaxInput.value) {
        body.set("duration_max", durationMaxInput.value);
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
        // Добавляем новые карточки к существующим
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
      
      // Показываем/скрываем кнопку "Показать еще"
      if (loadMoreWrap) {
        if (currentPage < totalPages) {
          loadMoreWrap.style.display = "block";
          isLoadingMore = false;
        } else {
          loadMoreWrap.style.display = "none";
        }
      }
      
      updateResetButton();
    } catch (e) {
      // Error handling without console output
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
    
    // Прокрутка к первой новой карточке
    const items = list.querySelectorAll(".education-archive__item");
    if (items.length > 0) {
      const firstNewItem = items[items.length - (totalPages > currentPage ? 12 : items.length)];
      if (firstNewItem) {
        firstNewItem.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    }
  };

  const programChoice = programSelect
    ? new Choices(programSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
      })
    : null;

  const languageChoice = languageSelect
    ? new Choices(languageSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
      })
    : null;

  const typeChoice = typeSelect
    ? new Choices(typeSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
      })
    : null;

  const accommodationChoice = accommodationSelect
    ? new Choices(accommodationSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
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
          updateUrl();
          updateResetButton();
          loadEducation(1);
        } else if (selectedDates.length === 0) {
          if (dateFromInput) dateFromInput.value = "";
          if (dateToInput) dateToInput.value = "";
          updateUrl();
          updateResetButton();
          loadEducation(1);
        }
      },
    });
  }

  const updateFilterOptions = async (countryId) => {
    try {
      const body = new URLSearchParams();
      body.set("action", "education_filter_options");
      // Отправляем country_id даже если он пустой, чтобы получить все опции
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
        const currentValues = getValues(programSelect);
        programChoice.clearStore();
        const programOptions = (data.programs || []).map((p) => ({
          value: String(p.id),
          label: p.name,
          selected: currentValues.includes(String(p.id)),
        }));
        if (programOptions.length > 0) {
          programChoice.setChoices(programOptions, "value", "label", true);
        }
      }

      if (languageChoice && languageSelect) {
        const currentValues = getValues(languageSelect);
        languageChoice.clearStore();
        const languageOptions = (data.languages || []).map((l) => ({
          value: String(l.id),
          label: l.name,
          selected: currentValues.includes(String(l.id)),
        }));
        if (languageOptions.length > 0) {
          languageChoice.setChoices(languageOptions, "value", "label", true);
        }
      }

      if (typeChoice && typeSelect) {
        const currentValues = getValues(typeSelect);
        typeChoice.clearStore();
        const typeOptions = (data.types || []).map((t) => ({
          value: String(t.id),
          label: t.name,
          selected: currentValues.includes(String(t.id)),
        }));
        if (typeOptions.length > 0) {
          typeChoice.setChoices(typeOptions, "value", "label", true);
        }
      }

      if (accommodationChoice && accommodationSelect) {
        const currentValues = getValues(accommodationSelect);
        accommodationChoice.clearStore();
        const accommodationOptions = (data.accommodations || []).map((a) => ({
          value: String(a.id),
          label: a.name,
          selected: currentValues.includes(String(a.id)),
        }));
        if (accommodationOptions.length > 0) {
          accommodationChoice.setChoices(accommodationOptions, "value", "label", true);
        }
      }
    } catch (e) {
      // Error handling without console output
    }
  };

  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const programs = params.getAll("program[]");
    const languages = params.getAll("language[]");
    const types = params.getAll("type[]");
    const accommodations = params.getAll("accommodation[]");
    const country = params.get("country");
    const ageMin = params.get("age_min");
    const ageMax = params.get("age_max");
    const durationMin = params.get("duration_min");
    const durationMax = params.get("duration_max");
    const dateFrom = params.get("date_from");
    const dateTo = params.get("date_to");
    const sort = params.get("sort");

    // Если страна выбрана - обновляем опции фильтров для этой страны
    // Если страна не выбрана - обновляем опции для всех школ
    const countryId = country || "";
    await updateFilterOptions(countryId);

    if (country && countryChoice) {
      countryChoice.setChoiceByValue(country);
    }

    if (programs.length && programChoice) {
      programChoice.removeActiveItems();
      programChoice.setChoiceByValue(programs);
    }

    if (languages.length && languageChoice) {
      languageChoice.removeActiveItems();
      languageChoice.setChoiceByValue(languages);
    }

    if (types.length && typeChoice) {
      typeChoice.removeActiveItems();
      typeChoice.setChoiceByValue(types);
    }

    if (accommodations.length && accommodationChoice) {
      accommodationChoice.removeActiveItems();
      accommodationChoice.setChoiceByValue(accommodations);
    }

    if (ageMin && ageMinInput) {
      ageMinInput.value = ageMin;
    }
    if (ageMax && ageMaxInput) {
      ageMaxInput.value = ageMax;
    }

    if (durationMin && durationMinInput) {
      durationMinInput.value = durationMin;
    }
    if (durationMax && durationMaxInput) {
      durationMaxInput.value = durationMax;
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

    const hasFilters = programs.length || languages.length || types.length || accommodations.length || country || ageMin || ageMax || durationMin || durationMax || dateFrom || dateTo;
    
    updateResetButton();

    if (hasFilters) {
      await loadEducation(1);
    }
  };

  const updateUrl = () => {
    const params = new URLSearchParams();

    getValues(programSelect).forEach((v) => params.append("program[]", v));
    getValues(languageSelect).forEach((v) => params.append("language[]", v));
    getValues(typeSelect).forEach((v) => params.append("type[]", v));
    getValues(accommodationSelect).forEach((v) => params.append("accommodation[]", v));

    if (countrySelect && countrySelect.value) {
      params.set("country", countrySelect.value);
    }

    if (ageMinInput && ageMinInput.value) {
      params.set("age_min", ageMinInput.value);
    }
    if (ageMaxInput && ageMaxInput.value) {
      params.set("age_max", ageMaxInput.value);
    }

    if (durationMinInput && durationMinInput.value) {
      params.set("duration_min", durationMinInput.value);
    }
    if (durationMaxInput && durationMaxInput.value) {
      params.set("duration_max", durationMaxInput.value);
    }

    if (dateFromInput && dateFromInput.value) {
      params.set("date_from", dateFromInput.value);
    }
    if (dateToInput && dateToInput.value) {
      params.set("date_to", dateToInput.value);
    }
    
    if (sortSelect && sortSelect.value && sortSelect.value !== "title_asc") {
      params.set("sort", sortSelect.value);
    }

    const newUrl = window.location.pathname + (params.toString() ? "?" + params.toString() : "");
    window.history.pushState({}, "", newUrl);
  };

  if (programChoice) {
    programSelect.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }
  if (languageChoice) {
    languageSelect.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }
  if (typeChoice) {
    typeSelect.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }
  if (accommodationChoice) {
    accommodationSelect.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }
  if (countryChoice) {
    countrySelect.addEventListener("change", async () => {
      currentPage = 1;
      const countryId = countrySelect.value || "";
      // Очищаем выбранные значения в других фильтрах при смене страны
      if (programChoice) programChoice.removeActiveItems();
      if (languageChoice) languageChoice.removeActiveItems();
      if (typeChoice) typeChoice.removeActiveItems();
      if (accommodationChoice) accommodationChoice.removeActiveItems();
      
      await updateFilterOptions(countryId);
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }
  
  if (sortSelect) {
    sortSelect.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      loadEducation(1);
    });
  }
  
  if (resetBtn) {
    resetBtn.addEventListener("click", () => {
      currentPage = 1;
      // Сбрасываем все фильтры
      if (countryChoice) countryChoice.setChoiceByValue("");
      if (programChoice) programChoice.removeActiveItems();
      if (languageChoice) languageChoice.removeActiveItems();
      if (typeChoice) typeChoice.removeActiveItems();
      if (accommodationChoice) accommodationChoice.removeActiveItems();
      
      if (ageMinInput) ageMinInput.value = "";
      if (ageMaxInput) ageMaxInput.value = "";
      if (durationMinInput) durationMinInput.value = "";
      if (durationMaxInput) durationMaxInput.value = "";
      
      if (datePickerInstance) {
        datePickerInstance.clear();
      }
      if (dateFromInput) dateFromInput.value = "";
      if (dateToInput) dateToInput.value = "";
      
      if (sortSelect) sortSelect.value = "title_asc";
      
      updateFilterOptions("");
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }

  // При загрузке страницы обновляем опции фильтров (для всех школ, если страна не выбрана)
  updateFilterOptions("");
  updateResetButton();
  
  // Обработчик кнопки "Показать еще"
  if (loadMoreButton) {
    loadMoreButton.addEventListener("click", handleLoadMore);
  }

  if (ageMinInput) {
    ageMinInput.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }
  if (ageMaxInput) {
    ageMaxInput.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }

  if (durationMinInput) {
    durationMinInput.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }
  if (durationMaxInput) {
    durationMaxInput.addEventListener("change", () => {
      currentPage = 1;
      updateUrl();
      updateResetButton();
      loadEducation(1);
    });
  }


  applyFromUrl();
};

