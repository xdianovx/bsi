import Choices from "choices.js";
import flatpickr from "flatpickr";
import { Russian } from "flatpickr/dist/l10n/ru.js";
import { dropdown } from "../forms/dropdown.js";
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

export const initCountryToursFilters = () => {
  const root = document.querySelector("[data-tours-filter]");
  if (!root) return;

  const list = document.querySelector("[data-tours-list]");
  const count = document.querySelector("[data-tours-count]");
  const pagination = document.querySelector("[data-tours-pagination]");
  if (!list) return;

  const countryId = parseInt(root.getAttribute("data-country-id") || "0", 10);
  if (!countryId) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const regionSelect = root.querySelector('select[name="region"]');
  const resortSelect = root.querySelector('select[name="resort"]');
  const typeSelect = root.querySelector('select[name="tour_type"]');
  const sortContainer = root.querySelector('.country-tours__sort');
  const sortTextEl = sortContainer?.querySelector('.country-tours__sort-text');
  const dateRangeInput = root.querySelector('input[name="date_range"]');
  const dateFromInput = root.querySelector('input[name="date_from"]');
  const dateToInput = root.querySelector('input[name="date_to"]');

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  // Загружаем цены для карточек, отрендеренных сервером при начальной загрузке
  displayTourPrices(list);

  let currentPage = 1;
  let currentSortValue = 'price_asc';

  const loadTours = async (page = 1) => {
    setLoading(true);
    currentPage = page;

    try {
      const body = new URLSearchParams();
      body.set("action", "country_tours_filter");
      body.set("country_id", String(countryId));
      body.set("paged", String(page));
      body.set("sort", currentSortValue);

      const regionId = regionSelect ? regionSelect.value || "" : "";
      if (regionId) body.set("region", regionId);

      const resortVal = resortSelect ? resortSelect.value || "" : "";
      if (resortVal) body.set("resort", resortVal);
      const typeVal = typeSelect ? typeSelect.value || "" : "";
      if (typeVal) body.set("tour_type", typeVal);

      if (dateFromInput?.value) body.set("date_from", dateFromInput.value);
      if (dateToInput?.value) body.set("date_to", dateToInput.value);

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      list.innerHTML = json.data.html || "";
      if (count) count.textContent = `Найдено туров: ${json.data.total || 0}`;

      // Обновляем пагинацию
      if (pagination) {
        if (json.data.pagination) {
          pagination.innerHTML = json.data.pagination;
          pagination.style.display = "";
          initPaginationHandlers();
        } else {
          pagination.innerHTML = "";
          pagination.style.display = "none";
        }
      }

      // Загружаем цены для туров после отображения карточек
      await displayTourPrices(list);
      
    } catch (e) {
      // Error handling without console output
    } finally {
      setLoading(false);
    }
  };

  const initPaginationHandlers = () => {
    if (!pagination) return;

    const paginationLinks = pagination.querySelectorAll("a");
    paginationLinks.forEach((link) => {
      // Удаляем старые обработчики, если они есть
      const newLink = link.cloneNode(true);
      link.parentNode.replaceChild(newLink, link);

      newLink.addEventListener("click", (e) => {
        e.preventDefault();
        const href = newLink.getAttribute("href");
        if (!href) return;

        let page = 1;
        // Проверяем формат ?paged=2
        const pageMatch = href.match(/[?&]paged=(\d+)/);
        if (pageMatch) {
          page = parseInt(pageMatch[1], 10);
        } else {
          // Проверяем формат /page/2/
          const pageMatch2 = href.match(/\/page\/(\d+)\//);
          if (pageMatch2) {
            page = parseInt(pageMatch2[1], 10);
          }
        }

        if (page > 0) {
          loadTours(page);

          // Прокрутка к началу списка туров
          if (list) {
            list.scrollIntoView({ behavior: "smooth", block: "start" });
          }
        }
      });
    });
  };

  // region (single)
  const regionChoice = regionSelect
    ? new Choices(regionSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  // resorts (single)
  const resortChoice = resortSelect
    ? new Choices(resortSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  // types (single)
  const typeChoice = typeSelect
    ? new Choices(typeSelect, {
        ...CHOICES_RU,
        searchEnabled: true,
        shouldSort: false,
      })
    : null;

  const loadResorts = async () => {
    if (!resortChoice) return;

    const regionId = regionSelect ? regionSelect.value || "" : "";

    try {
      const body = new URLSearchParams();
      body.set("action", "country_tours_resorts");
      body.set("country_id", String(countryId));
      body.set("region", String(regionId));

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      resortChoice.clearStore();
      resortChoice.setChoices(
        [{ value: "", label: "Все курорты", selected: true }].concat(
          (json.data.items || []).map((it) => ({ value: String(it.id), label: it.text }))
        ),
        "value",
        "label",
        true
      );
    } catch (e) {
      // Error handling without console output
    }
  };

  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const region = params.get("region") ? String(params.get("region")) : "";
    const resort = params.get("resort") ? String(params.get("resort")) : "";
    const type = params.get("tour_type") ? String(params.get("tour_type")) : "";
    const urlPage = params.get("paged") ? parseInt(params.get("paged"), 10) : 1;
    const pageFromUrl = urlPage > 0 ? urlPage : 1;

    if (region && regionChoice) {
      regionChoice.setChoiceByValue(region);
    } else if (region && regionSelect) {
      regionSelect.value = region;
    }

    // если регион есть — сначала обновим список курортов под регион
    if (region) {
      await loadResorts();
    }

    if (resort && resortChoice) {
      resortChoice.setChoiceByValue(resort);
    } else if (resort && resortSelect) {
      resortSelect.value = resort;
    }

    if (type && typeChoice) {
      typeChoice.setChoiceByValue(type);
    } else if (type && typeSelect) {
      typeSelect.value = type;
    }

    // если в URL есть фильтры — применяем их сразу с учетом пагинации
    if (region || resort || type) {
      await loadTours(pageFromUrl);
    }
  };

  // Sort dropdown initialization
  if (sortContainer) {
    const sortDropdown = dropdown(sortContainer);
    sortContainer.querySelectorAll('.country-tours__sort-option').forEach(opt => {
      opt.addEventListener('click', e => {
        e.preventDefault();
        currentSortValue = opt.dataset.value;
        if (sortTextEl) sortTextEl.textContent = opt.textContent.trim();
        sortContainer.querySelectorAll('.country-tours__sort-option').forEach(o => o.classList.remove('is-active'));
        opt.classList.add('is-active');
        sortDropdown.close();
        loadTours(1);
      });
    });
  }

  // Helper to convert date to ISO format (local timezone safe)
  const toLocalIso = (d) => {
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  };

  // Flatpickr datepicker initialization
  if (dateRangeInput) {
    flatpickr(dateRangeInput, {
      mode: 'range',
      locale: Russian,
      dateFormat: 'd.m',
      disableMobile: true,
      minDate: 'today',
      onChange(selectedDates) {
        if (selectedDates.length === 2) {
          dateFromInput.value = toLocalIso(selectedDates[0]);
          dateToInput.value = toLocalIso(selectedDates[1]);
          loadTours(1);
        } else if (selectedDates.length === 0) {
          dateFromInput.value = '';
          dateToInput.value = '';
          loadTours(1);
        }
      },
    });
  }

  if (regionSelect) {
    regionSelect.addEventListener("change", async () => {
      await loadResorts();
      await loadTours(1);
    });
  }
  if (resortSelect) resortSelect.addEventListener("change", () => loadTours(1));
  if (typeSelect) typeSelect.addEventListener("change", () => loadTours(1));

  // Инициализируем обработчики пагинации при загрузке страницы
  if (pagination) {
    initPaginationHandlers();
  }

  // Перехват кликов по типам туров в сайдбаре (без перезагрузки страницы)
  const countryAside = document.querySelector('[data-country-aside]');
  if (countryAside) {
    countryAside.addEventListener('click', (e) => {
      const link = e.target.closest('[data-sidebar-tour-type]');
      if (!link) return;

      e.preventDefault();

      const typeId = link.getAttribute('data-sidebar-tour-type');

      if (typeChoice) {
        typeChoice.setChoiceByValue(typeId);
      } else if (typeSelect) {
        typeSelect.value = typeId;
      }

      countryAside.querySelectorAll('[data-sidebar-tour-type]').forEach((el) => el.classList.remove('active'));
      link.classList.add('active');

      loadTours(1);
    });
  }

  // ✅ самое важное: проставляем значения из URL (например, tour_type)
  applyFromUrl();
};
