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

  const initialPaged = Math.max(1, parseInt(root.dataset.initialPaged || "1", 10) || 1);

  // Каталог в рамках страны (/country/{slug}/sobytiynye-tury/): страна «залочена»,
  // фильтр по стране не выводится, но во все запросы подставляем её id.
  const lockedCountry = (root.dataset.lockedCountry || "").trim();

  // Переопределение количества карточек на страницу (по умолчанию на бэке — 12).
  const perPage = (root.dataset.perPage || "").trim();

  // Базовый URL раздела. Есть только там, где сервер умеет отдавать
  // /page/N/ (каталог страны) — тогда пагинация рисуется ссылками,
  // и страницы каталога доступны краулеру и по прямому адресу.
  const pageBase = (root.dataset.pageBase || "").trim();
  const pageUrl = (n) => {
    const base = n > 1 ? `${pageBase}page/${n}/` : pageBase;
    const qs = buildQuery();
    return qs ? `${base}?${qs}` : base;
  };

  // Выбранное состояние живёт в адресной строке: ссылку можно скопировать,
  // а перезагрузка/«назад» возвращают тот же набор фильтров.
  const urlState = new URLSearchParams(window.location.search);
  const SORT_VALUES = ["date_asc", "date_desc", "price_asc", "price_desc"];

  /* Значения селектов из URL применяются не сразу: списки стран/городов/типов
     приходят по AJAX, поэтому держим их «в ожидании», пока опции не отрисованы. */
  const pending = {
    country: urlState.get("et_country") || "",
    region: urlState.get("et_region") || "",
    resort: urlState.get("et_resort") || "",
    tour_type: urlState.get("et_tour_type") || "",
  };

  const valOf = (select, key) => (select ? select.value : "") || pending[key] || "";

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
  const departureDateInput = root.querySelector('input[name="departure_date"]');
  const resetBtn = root.querySelector(".js-tours-reset");
  const sortEl = root.querySelector("[data-tours-sort]");
  const viewToggleEl = root.querySelector("[data-view-toggle]");

  let datePickerInstance = null;
  let availableDatesSet = new Set();
  let currentPage = 1;
  let sortValue = SORT_VALUES.includes(urlState.get("et_sort")) ? urlState.get("et_sort") : "date_asc";
  let viewValue = "tiles";

  /* Ровно то, что выбрал пользователь. Значения по умолчанию в URL не пишем,
     чтобы адрес чистого каталога оставался без «хвоста».
     Префикс et_ обязателен: голые country/region/resort — публичные query vars
     WordPress, с ними страница уходит на другой шаблон. */
  const buildQuery = () => {
    const p = new URLSearchParams();
    if (!lockedCountry && valOf(countrySelect, "country")) p.set("et_country", valOf(countrySelect, "country"));
    if (valOf(regionSelect, "region")) p.set("et_region", valOf(regionSelect, "region"));
    if (valOf(resortSelect, "resort")) p.set("et_resort", valOf(resortSelect, "resort"));
    if (valOf(tourTypeSelect, "tour_type")) p.set("et_tour_type", valOf(tourTypeSelect, "tour_type"));
    const q = (searchInput?.value || "").trim();
    if (q) p.set("et_q", q);
    const range = selectedRange();
    if (range) {
      p.set("et_date_from", range.from);
      p.set("et_date_to", range.to);
    }
    if (sortValue !== "date_asc") p.set("et_sort", sortValue);
    // Там, где номер страницы живёт в пути (/page/N/), в query его не дублируем.
    if (!pageBase && currentPage > 1) p.set("et_page", String(currentPage));
    return p.toString();
  };

  const syncUrl = () => {
    const qs = buildQuery();
    const path = window.location.pathname;
    window.history.replaceState(window.history.state, "", qs ? `${path}?${qs}` : path);
  };

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  /* Диапазон дат: из календаря, а до его инициализации — из URL. */
  const selectedRange = () => {
    if (datePickerInstance) {
      const picked = datePickerInstance.selectedDates || [];
      if (picked.length !== 2) return null;
      const dates = picked.map((d) => datePickerInstance.formatDate(d, "Y-m-d")).sort();
      return { from: dates[0], to: dates[1] };
    }
    if (!departureDateInput) return null;
    const iso = /^\d{4}-\d{2}-\d{2}$/;
    const from = urlState.get("et_date_from") || "";
    const to = urlState.get("et_date_to") || "";
    return iso.test(from) && iso.test(to) ? { from, to } : null;
  };

  const appendFilterBody = (body) => {
    if (lockedCountry) body.set("country", lockedCountry);
    else if (valOf(countrySelect, "country")) body.set("country", valOf(countrySelect, "country"));
    if (valOf(regionSelect, "region")) body.set("region", valOf(regionSelect, "region"));
    if (valOf(resortSelect, "resort")) body.set("resort", valOf(resortSelect, "resort"));
    if (valOf(tourTypeSelect, "tour_type")) body.set("tour_type", valOf(tourTypeSelect, "tour_type"));
    const q = (searchInput?.value || "").trim();
    if (q) body.set("search", q);
    const range = selectedRange();
    if (range) {
      body.set("date_from", range.from);
      body.set("date_to", range.to);
    }
    body.set("sort", sortValue);
    body.set("view", viewValue);
    body.set("paged", String(currentPage));
    if (perPage) body.set("per_page", perPage);
  };

  /* Номера страниц как у paginate_links: первая, последняя, ±2 вокруг текущей, между ними «…». */
  const buildPageList = (paged, maxPages) => {
    const midSize = 2;
    const pages = [];
    let lastShown = 0;

    for (let i = 1; i <= maxPages; i++) {
      const isEdge = i === 1 || i === maxPages;
      const isNear = Math.abs(i - paged) <= midSize;
      if (!isEdge && !isNear) continue;
      if (lastShown && i - lastShown > 1) pages.push("dots");
      pages.push(i);
      lastShown = i;
    }

    return pages;
  };

  const renderPagination = (total, maxPages, paged) => {
    if (!paginationEl) return;
    if (maxPages <= 1) {
      paginationEl.innerHTML = "";
      return;
    }

    /* Со ссылками страница каталога открывается и без JS, и по прямому
       адресу; без pageBase сервер /page/N/ не отдаёт — остаются кнопки. */
    const link = (n, cls, text) =>
      pageBase
        ? '<a class="' + cls + '" href="' + pageUrl(n) + '" data-et-page="' + n + '">' + text + "</a>"
        : '<button type="button" class="' + cls + '" data-et-page="' + n + '">' + text + "</button>";

    const parts = [];
    if (paged > 1) {
      parts.push(link(paged - 1, "prev page-numbers", "Назад"));
    }

    buildPageList(paged, maxPages).forEach((item) => {
      if (item === "dots") {
        parts.push('<span class="page-numbers dots">&hellip;</span>');
        return;
      }
      parts.push(
        item === paged
          ? '<span aria-current="page" class="page-numbers current">' + item + "</span>"
          : link(item, "page-numbers", String(item))
      );
    });

    if (paged < maxPages) {
      parts.push(link(paged + 1, "next page-numbers", "Вперёд"));
    }

    paginationEl.innerHTML = parts.join("");

    paginationEl.querySelectorAll("[data-et-page]").forEach((btn) => {
      btn.addEventListener("click", async (e) => {
        const target = parseInt(btn.dataset.etPage, 10);
        if (!target || target === currentPage || target < 1 || target > maxPages) return;
        e.preventDefault();
        currentPage = target;
        // Адрес в строке браузера соответствует показанной странице.
        if (pageBase) {
          window.history.pushState({ etPage: target }, "", pageUrl(target));
        }
        await loadTours();
        // После подгрузки возвращаем пользователя к началу списка.
        if (list) list.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  };

  const countActiveFilters = () => {
    let c = 0;
    if (countrySelect && countrySelect.value) c++;
    if (regionSelect && regionSelect.value) c++;
    if (resortSelect && resortSelect.value) c++;
    if (tourTypeSelect && tourTypeSelect.value) c++;
    if (searchInput && (searchInput.value || "").trim()) c++;
    if (datePickerInstance && datePickerInstance.selectedDates && datePickerInstance.selectedDates.length > 0) c++;
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
        const cur = valOf(resortSelect, "resort");
        pending.resort = "";
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
        const cur = valOf(tourTypeSelect, "tour_type");
        pending.tour_type = "";
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
      syncUrl();
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

  if (searchInput && urlState.get("et_q")) {
    searchInput.value = urlState.get("et_q");
  }

  if (departureDateInput) {
    // Даты в URL — в ISO (Y-m-d), а инпут показывает d.m.Y, поэтому передаём объекты Date.
    const toDate = (s) => {
      if (!/^\d{4}-\d{2}-\d{2}$/.test(s || "")) return null;
      const d = new Date(`${s}T00:00:00`);
      return Number.isNaN(d.getTime()) ? null : d;
    };
    const urlFrom = toDate(urlState.get("et_date_from"));
    const urlTo = toDate(urlState.get("et_date_to"));
    datePickerInstance = flatpickr(departureDateInput, {
      mode: "range",
      locale: Russian,
      dateFormat: "d.m.Y",
      disableMobile: true,
      defaultDate: urlFrom && urlTo ? [urlFrom, urlTo] : null,
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
      body.set("country_id", valOf(countrySelect, "country"));

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
        const cur = valOf(regionSelect, "region");
        pending.region = "";
        regionChoice.clearStore();
        let valid = !cur;
        const choices = [{ value: "", label: "Все регионы", selected: !cur }];
        if (json.data.items && json.data.items.length > 0) {
          choices.push(
            ...json.data.items.map((it) => {
              const sel = cur === String(it.id);
              if (sel) valid = true;
              return { value: String(it.id), label: it.text, selected: sel };
            })
          );
        }
        // Регион из прежней страны в новом списке не существует — сбрасываем.
        if (!valid) choices[0].selected = true;
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
        const currentValue = valOf(countrySelect, "country");
        pending.country = "";
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

    Object.keys(pending).forEach((k) => {
      pending[k] = "";
    });
    urlState.delete("et_date_from");
    urlState.delete("et_date_to");

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

    // Сортировка из URL — подсветить активный пункт и подпись триггера.
    sortOptions.forEach((opt) => {
      const active = (opt.dataset.value || "date_asc") === sortValue;
      opt.classList.toggle("is-active", active);
      if (active && sortText) sortText.textContent = opt.textContent.trim();
    });

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
      }, 400)
    );
  }

  // Кнопка «назад» после pushState должна возвращать прежнюю страницу
  // каталога, а не уводить с раздела.
  if (pageBase) {
    window.addEventListener("popstate", async () => {
      const m = window.location.pathname.match(/\/page\/(\d+)\/?$/);
      const target = m ? parseInt(m[1], 10) : 1;
      if (!target || target === currentPage) return;
      currentPage = target;
      await loadTours();
    });
  }

  const initFromServer = async () => {
    // Номер страницы: в каталоге страны — из пути (/page/N/), иначе — из ?pg=.
    currentPage = pageBase ? initialPaged : Math.max(1, parseInt(urlState.get("et_page") || "1", 10) || 1);
    if (regionSelect && countrySelect) await loadRegions();
    await loadFacets();
    await loadCountries();
    await loadAvailableDates();
    await loadTours();
  };

  await initFromServer();
};
