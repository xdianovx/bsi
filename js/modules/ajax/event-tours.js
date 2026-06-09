import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "../forms/dropdown.js";

const VIEW_STORAGE_KEY = "bsi_event_tours_view";

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  addItemText: (value) => `Нажмите Enter, чтобы добавить «${value}»`,
  maxItemText: (maxItemCount) => `Можно выбрать максимум: ${maxItemCount}`,
  searchPlaceholderValue: "Поиск...",
};

const debounce = (fn, ms) => {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
};

export const initEventToursFilters = async () => {
  const root = document.querySelector("[data-event-tours-filter]");
  if (!root) return;

  const initialPaged = Math.max(
    1,
    parseInt(root.dataset.initialPaged || "1", 10) || 1,
  );

  // Каталог в рамках страны (/country/{slug}/sobytiynye-tury/): страна «залочена»,
  // фильтр по стране не выводится, но во все запросы подставляем её id.
  const lockedCountry = (root.dataset.lockedCountry || "").trim();

  // Переопределение количества карточек на страницу (по умолчанию на бэке — 12).
  const perPage = (root.dataset.perPage || "").trim();

  const list = document.querySelector("[data-tours-list]");
  const counter = root.querySelector(".js-tours-counter");
  const paginationEl = document.querySelector("[data-event-tours-pagination]");
  if (!list) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const countrySelect = root.querySelector('select[name="country"]');
  const regionSelect = root.querySelector('select[name="region"]');
  const resortSelect = root.querySelector('select[name="resort"]');
  const tourTypeSelect = root.querySelector('select[name="tour_type"]');
  const searchInput = root.querySelector('input[name="event_search"]');
  const departureDateInput = root.querySelector(
    'input[name="departure_date"]',
  );
  const resetBtn = root.querySelector(".js-tours-reset");
  const sortEl = root.querySelector("[data-tours-sort]");
  const viewToggleEl = root.querySelector("[data-view-toggle]");

  let datePickerInstance = null;
  let availableDatesSet = new Set();
  let currentPage = 1;
  let sortValue = "date_asc";
  let viewValue = "tiles";

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  const appendFilterBody = (body) => {
    if (lockedCountry) body.set("country", lockedCountry);
    else if (countrySelect?.value) body.set("country", countrySelect.value);
    if (regionSelect?.value) body.set("region", regionSelect.value);
    if (resortSelect?.value) body.set("resort", resortSelect.value);
    if (tourTypeSelect?.value) body.set("tour_type", tourTypeSelect.value);
    const q = (searchInput?.value || "").trim();
    if (q) body.set("search", q);
    if (
      datePickerInstance &&
      datePickerInstance.selectedDates &&
      datePickerInstance.selectedDates.length === 2
    ) {
      const dates = datePickerInstance.selectedDates
        .map((d) => datePickerInstance.formatDate(d, "Y-m-d"))
        .sort();
      body.set("date_from", dates[0]);
      body.set("date_to", dates[1]);
    }
    body.set("sort", sortValue);
    body.set("view", viewValue);
    body.set("paged", String(currentPage));
    if (perPage) body.set("per_page", perPage);
  };

  const renderPagination = (total, maxPages, paged) => {
    if (!paginationEl) return;
    if (maxPages <= 1) {
      paginationEl.innerHTML = "";
      return;
    }
    const prevDisabled = paged <= 1;
    const nextDisabled = paged >= maxPages;
    paginationEl.innerHTML = `
      <div class="country-tours__pagination-inner">
        <button type="button" class="btn btn-gray country-tours__page-btn" data-et-prev ${prevDisabled ? "disabled" : ""}>Назад</button>
        <span class="country-tours__page-num numfont">${paged} / ${maxPages}</span>
        <span class="country-tours__page-total">всего ${total}</span>
        <button type="button" class="btn btn-gray country-tours__page-btn" data-et-next ${nextDisabled ? "disabled" : ""}>Вперёд</button>
      </div>`;
    paginationEl.querySelector("[data-et-prev]")?.addEventListener("click", async () => {
      if (currentPage > 1) {
        currentPage--;
        await loadTours();
      }
    });
    paginationEl.querySelector("[data-et-next]")?.addEventListener("click", async () => {
      if (currentPage < maxPages) {
        currentPage++;
        await loadTours();
      }
    });
  };

  const countActiveFilters = () => {
    let c = 0;
    if (countrySelect && countrySelect.value) c++;
    if (regionSelect && regionSelect.value) c++;
    if (resortSelect && resortSelect.value) c++;
    if (tourTypeSelect && tourTypeSelect.value) c++;
    if (searchInput && (searchInput.value || "").trim()) c++;
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

  const loadFacets = async () => {
    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_facets");
      appendFilterBody(body);

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

      const resorts = json.data.resorts || [];
      const tourTypes = json.data.tour_types || [];

      if (resortChoice && resortSelect) {
        const cur = resortSelect.value || "";
        let valid = !cur;
        resortChoice.clearStore();
        const rchoices = [{ value: "", label: "Все города", selected: true }];
        resorts.forEach((it) => {
          const sel = cur === String(it.id);
          if (sel) valid = true;
          rchoices.push({
            value: String(it.id),
            label: it.text,
            selected: sel,
          });
        });
        if (!valid && cur) {
          rchoices[0].selected = true;
        }
        resortChoice.setChoices(rchoices, "value", "label", true);
      }

      if (tourTypeChoice && tourTypeSelect) {
        const cur = tourTypeSelect.value || "";
        let valid = !cur;
        tourTypeChoice.clearStore();
        const tchoices = [{ value: "", label: "Все типы", selected: true }];
        tourTypes.forEach((it) => {
          const sel = cur === String(it.id);
          if (sel) valid = true;
          tchoices.push({
            value: String(it.id),
            label: it.text,
            selected: sel,
          });
        });
        if (!valid && cur) {
          tchoices[0].selected = true;
        }
        tourTypeChoice.setChoices(tchoices, "value", "label", true);
      }
    } catch (_e) {
      /* ignore */
    }
  };

  const loadAvailableDates = async () => {
    if (!datePickerInstance) return;
    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_available_dates");
      appendFilterBody(body);

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

      const dates = Array.isArray(json.data.dates) ? json.data.dates : [];
      // Существующие даты событий (event_dates / event_hero_date) для подсветки точкой
      // в onDayCreate. Календарь полностью кликабелен — даты не ограничиваем,
      // лишь помечаем те, где есть событие.
      availableDatesSet = new Set(dates);
      datePickerInstance.set("enable", [() => true]);
      datePickerInstance.set("minDate", null);
      datePickerInstance.set("maxDate", null);
      datePickerInstance.redraw();
    } catch (_e) {
      /* ignore */
    }
  };

  const loadTours = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_filter");
      appendFilterBody(body);

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
      const total = json.data.total ?? 0;
      const maxPages = json.data.max_pages ?? 0;
      const paged = json.data.paged ?? 1;
      currentPage = paged;

      if (counter) counter.textContent = `Найдено: ${total}`;
      renderPagination(total, maxPages, paged);
      updateResetButton();
      document.dispatchEvent(new CustomEvent("education:content-updated"));
    } catch (_e) {
      /* Error handling */
    } finally {
      setLoading(false);
    }
  };

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

  const resortChoice = resortSelect
    ? new Choices(resortSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  const tourTypeChoice = tourTypeSelect
    ? new Choices(tourTypeSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  if (departureDateInput) {
    datePickerInstance = flatpickr(departureDateInput, {
      mode: "range",
      locale: Russian,
      dateFormat: "d.m.Y",
      disableMobile: true,
      onDayCreate: (_dObj, _dStr, fp, dayElem) => {
        // Подсветка дней, у которых есть событие (event_dates / event_hero_date).
        const ymd = fp.formatDate(dayElem.dateObj, "Y-m-d");
        if (availableDatesSet.has(ymd)) {
          dayElem.classList.add("has-event");
        }
      },
      onChange: async (selectedDates) => {
        currentPage = 1;
        if (selectedDates.length === 2) {
          await loadFacets();
          await loadCountries();
          await loadTours();
        } else if (selectedDates.length === 0) {
          await loadFacets();
          await loadCountries();
          await loadTours();
        }
        updateResetButton();
      },
    });
  }

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
            })),
          );
        }
        regionChoice.setChoices(choices, "value", "label", true);
      }
    } catch (_e) {
      /* ignore */
    }
  };

  const loadCountries = async () => {
    if (!countrySelect) return;

    try {
      const body = new URLSearchParams();
      body.set("action", "event_tours_countries");
      appendFilterBody(body);

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
            })),
          );
        }
        countryChoice.setChoices(choices, "value", "label", true);
      }
    } catch (_e) {
      /* ignore */
    }
  };

  const resetFilters = async () => {
    if (countryChoice) countryChoice.setChoiceByValue("");
    else if (countrySelect) countrySelect.value = "";

    if (regionChoice) regionChoice.setChoiceByValue("");
    else if (regionSelect) regionSelect.value = "";

    if (resortChoice) resortChoice.setChoiceByValue("");
    else if (resortSelect) resortSelect.value = "";

    if (tourTypeChoice) tourTypeChoice.setChoiceByValue("");
    else if (tourTypeSelect) tourTypeSelect.value = "";

    if (searchInput) searchInput.value = "";

    if (datePickerInstance) datePickerInstance.clear();
    else if (departureDateInput) departureDateInput.value = "";

    currentPage = 1;

    if (regionChoice && countrySelect) await loadRegions();
    await loadFacets();
    await loadCountries();
    await loadAvailableDates();
    await loadTours();
  };

  if (resetBtn) {
    resetBtn.addEventListener("click", async (e) => {
      e.preventDefault();
      await resetFilters();
    });
  }

  const onFacetChange = async () => {
    currentPage = 1;
    await loadFacets();
    await loadCountries();
    await loadAvailableDates();
    await loadTours();
  };

  // Сортировка
  if (sortEl) {
    dropdown(sortEl);
    const sortText = sortEl.querySelector(".country-tours__sort-text");
    const sortOptions = sortEl.querySelectorAll(".country-tours__sort-option");
    sortOptions.forEach((opt) => {
      opt.addEventListener("click", async () => {
        const val = opt.dataset.value || "date_asc";
        if (val === sortValue) {
          sortEl.classList.remove("is-open");
          return;
        }
        sortValue = val;
        sortOptions.forEach((o) => o.classList.toggle("is-active", o === opt));
        if (sortText) sortText.textContent = opt.textContent.trim();
        sortEl.classList.remove("is-open");
        currentPage = 1;
        await loadTours();
      });
    });
  }

  // Переключатель выкладки плитки/список
  const applyViewClass = () => {
    if (!list) return;
    list.classList.toggle("is-tiles", viewValue === "tiles");
    list.classList.toggle("is-list", viewValue === "list");
  };
  if (viewToggleEl) {
    const savedView = localStorage.getItem(VIEW_STORAGE_KEY);
    if (savedView === "tiles" || savedView === "list") {
      viewValue = savedView;
    }
    const viewBtns = viewToggleEl.querySelectorAll("[data-view]");
    viewBtns.forEach((btn) => {
      btn.classList.toggle("is-active", btn.dataset.view === viewValue);
      btn.addEventListener("click", async () => {
        const val = btn.dataset.view === "list" ? "list" : "tiles";
        if (val === viewValue) return;
        viewValue = val;
        localStorage.setItem(VIEW_STORAGE_KEY, viewValue);
        viewBtns.forEach((b) => b.classList.toggle("is-active", b.dataset.view === viewValue));
        applyViewClass();
        await loadTours();
      });
    });
    applyViewClass();
  }

  if (countrySelect) {
    countrySelect.addEventListener("change", async () => {
      await loadRegions();
      await onFacetChange();
    });
  }

  if (regionSelect) {
    regionSelect.addEventListener("change", onFacetChange);
  }

  if (resortSelect) {
    resortSelect.addEventListener("change", onFacetChange);
  }

  if (tourTypeSelect) {
    tourTypeSelect.addEventListener("change", onFacetChange);
  }

  if (searchInput) {
    searchInput.addEventListener(
      "input",
      debounce(async () => {
        await onFacetChange();
      }, 400),
    );
  }

  const initFromServer = async () => {
    currentPage = initialPaged;
    await loadFacets();
    await loadCountries();
    await loadAvailableDates();
    await loadTours();
  };

  await initFromServer();
};
