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

const debounce = (fn, ms) => {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
};

/** Параметры в query string: без префикса совпадают с public query_vars CPT/таксономий WP → ломается главный запрос при F5. */
const ET_URL_LEGACY = [
  "country",
  "region",
  "resort",
  "tour_type",
  "search",
  "date_from",
  "date_to",
];
const ET_URL = {
  country: "et_country",
  region: "et_region",
  resort: "et_resort",
  tour_type: "et_tour_type",
  search: "et_search",
  date_from: "et_date_from",
  date_to: "et_date_to",
  page: "event_page",
};

const clearEventToursUrlParams = (url) => {
  [...ET_URL_LEGACY, ...Object.values(ET_URL)].forEach((k) =>
    url.searchParams.delete(k),
  );
};

export const initEventToursFilters = async () => {
  const root = document.querySelector("[data-event-tours-filter]");
  if (!root) return;

  const list = document.querySelector("[data-tours-list]");
  const count = document.querySelector("[data-tours-count]");
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

  let datePickerInstance = null;
  let currentPage = 1;

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  const appendFilterBody = (body) => {
    if (countrySelect?.value) body.set("country", countrySelect.value);
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
        .map((d) => d.toISOString().split("T")[0])
        .sort();
      body.set("date_from", dates[0]);
      body.set("date_to", dates[1]);
    }
    body.set("paged", String(currentPage));
  };

  const syncUrl = () => {
    const url = new URL(window.location.href);
    clearEventToursUrlParams(url);

    if (countrySelect?.value)
      url.searchParams.set(ET_URL.country, countrySelect.value);
    if (regionSelect?.value)
      url.searchParams.set(ET_URL.region, regionSelect.value);
    if (resortSelect?.value)
      url.searchParams.set(ET_URL.resort, resortSelect.value);
    if (tourTypeSelect?.value)
      url.searchParams.set(ET_URL.tour_type, tourTypeSelect.value);
    const q = (searchInput?.value || "").trim();
    if (q) url.searchParams.set(ET_URL.search, q);
    if (
      datePickerInstance &&
      datePickerInstance.selectedDates &&
      datePickerInstance.selectedDates.length === 2
    ) {
      const dates = datePickerInstance.selectedDates
        .map((d) => d.toISOString().split("T")[0])
        .sort();
      url.searchParams.set(ET_URL.date_from, dates[0]);
      url.searchParams.set(ET_URL.date_to, dates[1]);
    }
    if (currentPage > 1)
      url.searchParams.set(ET_URL.page, String(currentPage));
    window.history.replaceState({}, "", url.toString());
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

  const loadAvailableDates = async () => {
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

      const availableDateStrings = json.data.dates || [];

      if (datePickerInstance) {
        datePickerInstance.set(
          "enable",
          availableDateStrings.length > 0 ? availableDateStrings : [],
        );
        datePickerInstance.redraw();
      }
    } catch (_e) {
      if (datePickerInstance) {
        datePickerInstance.set("enable", []);
        datePickerInstance.redraw();
      }
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

      if (count) count.textContent = `Найдено туров: ${total}`;
      renderPagination(total, maxPages, paged);
      syncUrl();
      updateResetButton();
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
      enable: [],
      onChange: async (selectedDates) => {
        if (selectedDates.length === 2) {
          currentPage = 1;
          await loadFacets();
          await loadAvailableDates();
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
    await loadAvailableDates();
    await loadCountries();
    await loadTours();
  };

  if (resetBtn) resetBtn.addEventListener("click", resetFilters);

  const onFacetChange = async () => {
    currentPage = 1;
    await loadFacets();
    await loadAvailableDates();
    await loadCountries();
    await loadTours();
  };

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

  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const country =
      params.get(ET_URL.country) || params.get("country") || "";
    const region = params.get(ET_URL.region) || params.get("region") || "";
    const resort = params.get(ET_URL.resort) || params.get("resort") || "";
    const tourType =
      params.get(ET_URL.tour_type) || params.get("tour_type") || "";
    const search =
      params.get(ET_URL.search) || params.get("search") || "";
    const dateFrom =
      params.get(ET_URL.date_from) || params.get("date_from") || "";
    const dateTo =
      params.get(ET_URL.date_to) || params.get("date_to") || "";
    const ep = params.get(ET_URL.page) || "";
    currentPage = ep ? Math.max(1, parseInt(ep, 10) || 1) : 1;

    if (search && searchInput) searchInput.value = search;
    if (country && countryChoice) countryChoice.setChoiceByValue(country);
    if (country) await loadRegions();
    if (region && regionChoice) regionChoice.setChoiceByValue(region);

    await loadFacets();
    if (resort && resortChoice) resortChoice.setChoiceByValue(resort);
    if (tourType && tourTypeChoice) tourTypeChoice.setChoiceByValue(tourType);

    if (dateFrom && dateTo && datePickerInstance) {
      datePickerInstance.setDate([dateFrom, dateTo], false);
    }

    await loadAvailableDates();
    await loadCountries();
    await loadTours();
    updateResetButton();
  };

  await applyFromUrl();
};
