import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";

// Сервер отдаёт готовый HTML (get_template_part + paginate_links) —
// вставляем фрагментом, а не через innerHTML.
const replaceWithServerHtml = (target, html) => {
  if (!target) return;
  while (target.firstChild) {
    target.removeChild(target.firstChild);
  }
  if (!html) return;
  const range = document.createRange();
  range.selectNodeContents(target);
  target.appendChild(range.createContextualFragment(String(html)));
};

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  addItemText: (value) => `Нажмите Enter, чтобы добавить «${value}»`,
  maxItemText: (maxItemCount) => `Можно выбрать максимум: ${maxItemCount}`,
  searchPlaceholderValue: "Поиск...",
};

export const initCountryEducationFilters = () => {
  const root = document.querySelector("[data-education-filter]");
  if (!root) return;

  const form = root.querySelector("[data-education-form]");
  const list = document.querySelector("[data-education-list]");
  const count = document.querySelector("[data-education-count]");
  const pagination = document.querySelector("[data-education-pagination]");
  const resetBtn = document.querySelector("[data-education-reset]");
  if (!form || !list) return;

  const countryId = parseInt(root.getAttribute("data-country-id") || "0", 10);
  if (!countryId) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const programSelect = form.querySelector('select[name="program[]"]');
  const languageSelect = form.querySelector('select[name="language[]"]');
  const typeSelect = form.querySelector('select[name="type[]"]');
  const accommodationSelect = form.querySelector('select[name="accommodation[]"]');
  const ageMinInput = form.querySelector('input[name="age_min"]');
  const ageMaxInput = form.querySelector('input[name="age_max"]');
  const durationMinInput = form.querySelector('input[name="duration_min"]');
  const durationMaxInput = form.querySelector('input[name="duration_max"]');
  const dateRangeInput = form.querySelector('input[name="date_range"]');
  const dateFromInput = form.querySelector('input[name="date_from"]');
  const dateToInput = form.querySelector('input[name="date_to"]');

  let datePickerInstance = null;

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

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
      body.set("action", "country_education_filter");
      body.set("country_id", String(countryId));
      body.set("paged", String(page));

      getValues(programSelect).forEach((v) => body.append("program[]", v));
      getValues(languageSelect).forEach((v) => body.append("language[]", v));
      getValues(typeSelect).forEach((v) => body.append("type[]", v));
      getValues(accommodationSelect).forEach((v) => body.append("accommodation[]", v));

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

      replaceWithServerHtml(list, json.data.html || "");
      if (count) {
        count.textContent = `Найдено школ: ${json.data.total || 0}`;
      }

      if (pagination) {
        replaceWithServerHtml(pagination, json.data.pagination || "");
        initPaginationHandlers();
      }

      document.dispatchEvent(new CustomEvent("education:content-updated"));
    } catch (e) {
      // Error handling without console output
    } finally {
      setLoading(false);
    }
  };

  const updateResetVisibility = () => {
    if (!resetBtn) return;
    const hasAnyFilter =
      getValues(programSelect).length ||
      getValues(languageSelect).length ||
      getValues(typeSelect).length ||
      getValues(accommodationSelect).length ||
      [ageMinInput, ageMaxInput, durationMinInput, durationMaxInput].some((i) => i && i.value) ||
      (dateFromInput && dateFromInput.value) ||
      (dateToInput && dateToInput.value);
    resetBtn.classList.toggle("is-hidden", !hasAnyFilter);
  };

  const initPaginationHandlers = () => {
    if (!pagination) return;

    pagination.querySelectorAll("a").forEach((link) => {
      const fresh = link.cloneNode(true);
      link.parentNode.replaceChild(fresh, link);

      fresh.addEventListener("click", (e) => {
        e.preventDefault();
        const href = fresh.getAttribute("href") || "";
        const byQuery = href.match(/[?&]paged=(\d+)/);
        const byPath = href.match(/\/page\/(\d+)\//);
        const page = parseInt((byQuery && byQuery[1]) || (byPath && byPath[1]) || "1", 10);

        if (page > 0) {
          loadEducation(page);
          list.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    });
  };

  const programChoice = programSelect
    ? new Choices(programSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Любая",
      })
    : null;

  const languageChoice = languageSelect
    ? new Choices(languageSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Любой",
      })
    : null;

  const typeChoice = typeSelect
    ? new Choices(typeSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Любой",
      })
    : null;

  const accommodationChoice = accommodationSelect
    ? new Choices(accommodationSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
        placeholderValue: "Любое",
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
        if (selectedDates.length === 2) {
          const startDate = selectedDates[0].toISOString().split("T")[0];
          const endDate = selectedDates[1].toISOString().split("T")[0];
          if (dateFromInput) dateFromInput.value = startDate;
          if (dateToInput) dateToInput.value = endDate;
          updateResetVisibility();
          loadEducation(1);
        } else if (selectedDates.length === 0) {
          if (dateFromInput) dateFromInput.value = "";
          if (dateToInput) dateToInput.value = "";
          updateResetVisibility();
          loadEducation(1);
        }
      },
    });
  }

  const getAllParams = (params, key) => {
    const a = params.getAll(key) || [];
    const b = params.getAll(key.replace("[]", "")) || [];
    return [...a, ...b].map((v) => String(v)).filter(Boolean);
  };

  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const programs = getAllParams(params, "program[]");
    const languages = getAllParams(params, "language[]");
    const types = getAllParams(params, "type[]");
    const accommodations = getAllParams(params, "accommodation[]");
    const ageMin = params.get("age_min");
    const ageMax = params.get("age_max");
    const durationMin = params.get("duration_min");
    const durationMax = params.get("duration_max");
    const dateFrom = params.get("date_from");
    const dateTo = params.get("date_to");

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

    const hasFilters = programs.length || languages.length || types.length || accommodations.length || ageMin || ageMax || durationMin || durationMax || dateFrom || dateTo;

    if (hasFilters) {
      await loadEducation(1);
    }
  };

  if (programChoice) {
    programSelect.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }
  if (languageChoice) {
    languageSelect.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }
  if (typeChoice) {
    typeSelect.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }
  if (accommodationChoice) {
    accommodationSelect.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }

  if (ageMinInput) {
    ageMinInput.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }
  if (ageMaxInput) {
    ageMaxInput.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }

  if (durationMinInput) {
    durationMinInput.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }
  if (durationMaxInput) {
    durationMaxInput.addEventListener("change", () => { updateResetVisibility(); loadEducation(1); });
  }


  if (resetBtn) {
    resetBtn.addEventListener("click", (e) => {
      e.preventDefault();

      // Choices хранит своё состояние отдельно от нативного select:
      // чистим оба, иначе сброшенный фильтр продолжает уезжать в запрос.
      [
        [programChoice, programSelect],
        [languageChoice, languageSelect],
        [typeChoice, typeSelect],
        [accommodationChoice, accommodationSelect],
      ].forEach(([choice, select]) => {
        if (choice) choice.removeActiveItems();
        if (select) {
          [...select.options].forEach((o) => {
            o.selected = false;
          });
        }
      });
      [ageMinInput, ageMaxInput, durationMinInput, durationMaxInput].forEach((input) => {
        if (input) input.value = "";
      });
      if (datePickerInstance) datePickerInstance.clear();
      if (dateRangeInput) dateRangeInput.value = "";
      if (dateFromInput) dateFromInput.value = "";
      if (dateToInput) dateToInput.value = "";

      updateResetVisibility();
      loadEducation(1);
    });
  }

  if (pagination) {
    initPaginationHandlers();
  }

  updateResetVisibility();
  applyFromUrl();
};

