import Choices from "choices.js";
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
  const resortSelect = root.querySelector('select[name="resort[]"]');
  const typeSelect = root.querySelector('select[name="tour_type[]"]');

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  // Загружаем цены для карточек, отрендеренных сервером при начальной загрузке
  displayTourPrices(list);

  let currentPage = 1;

  const getValues = (sel) => {
    if (!sel) return [];
    return Array.from(sel.selectedOptions)
      .map((o) => o.value)
      .filter(Boolean);
  };

  const loadTours = async (page = 1) => {
    setLoading(true);
    currentPage = page;

    try {
      const body = new URLSearchParams();
      body.set("action", "country_tours_filter");
      body.set("country_id", String(countryId));
      body.set("paged", String(page));

      const regionId = regionSelect ? regionSelect.value || "" : "";
      if (regionId) body.set("region", regionId);

      getValues(resortSelect).forEach((v) => body.append("resort[]", v));
      getValues(typeSelect).forEach((v) => body.append("tour_type[]", v));

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

  // resorts (multiple)
  const resortChoice = resortSelect
    ? new Choices(resortSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
      })
    : null;

  // types (multiple)
  const typeChoice = typeSelect
    ? new Choices(typeSelect, {
        ...CHOICES_RU,
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        placeholder: true,
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

  const getAllParams = (params, key) => {
    const a = params.getAll(key) || [];
    const b = params.getAll(key.replace("[]", "")) || [];
    return [...a, ...b].map((v) => String(v)).filter(Boolean);
  };

  const applyFromUrl = async () => {
    const params = new URLSearchParams(window.location.search);

    const region = params.get("region") ? String(params.get("region")) : "";
    const resorts = getAllParams(params, "resort[]");
    const types = getAllParams(params, "tour_type[]");
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

    if (resorts.length && resortChoice) {
      resortChoice.removeActiveItems();
      resortChoice.setChoiceByValue(resorts);
    }

    if (types.length && typeChoice) {
      typeChoice.removeActiveItems();
      typeChoice.setChoiceByValue(types);
    }

    // если в URL есть фильтры — применяем их сразу с учетом пагинации
    if (region || resorts.length || types.length) {
      await loadTours(pageFromUrl);
    }
  };

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

  // ✅ самое важное: проставляем значения из URL (например, tour_type[])
  applyFromUrl();
};
