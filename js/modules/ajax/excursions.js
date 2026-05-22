import Choices from "choices.js";
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

// Server-rendered HTML from wp_ajax (trusted: get_template_part + paginate_links)
// is parsed into a DocumentFragment instead of innerHTML assignment.
const replaceWithServerHtml = (target, html) => {
  if (!target) return;
  while (target.firstChild) {
    target.removeChild(target.firstChild);
  }
  if (!html) return;
  const range = document.createRange();
  range.selectNodeContents(target);
  const fragment = range.createContextualFragment(String(html));
  target.appendChild(fragment);
};

export const initExcursionsFilter = () => {
  const root = document.querySelector("[data-excursions-filter]");
  if (!root) return;

  const list = document.querySelector("[data-excursions-list]");
  const count = document.querySelector("[data-excursions-count]");
  const pagination = document.querySelector("[data-excursions-pagination]");
  if (!list) return;

  const countryId = parseInt(root.getAttribute("data-country-id") || "0", 10);
  if (!countryId) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const regionSelect = root.querySelector('select[name="region"]');
  const resortSelect = root.querySelector('select[name="resort"]');
  const typeSelect = root.querySelector('select[name="excursion_type"]');
  const languageSelect = root.querySelector('select[name="excursion_language"]');
  const sortContainer = root.querySelector(".country-excursions__sort");
  const sortTextEl = sortContainer?.querySelector(".country-excursions__sort-text");

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  let currentPage = 1;
  let currentSortValue = "price_asc";

  const loadExcursions = async (page = 1) => {
    setLoading(true);
    currentPage = page;

    try {
      const body = new URLSearchParams();
      body.set("action", "excursions_filter");
      body.set("country_id", String(countryId));
      body.set("paged", String(page));
      body.set("sort", currentSortValue);

      const regionId = regionSelect ? regionSelect.value || "" : "";
      if (regionId) body.set("region", regionId);

      const resortVal = resortSelect ? resortSelect.value || "" : "";
      if (resortVal) body.set("resort", resortVal);

      const typeVal = typeSelect ? typeSelect.value || "" : "";
      if (typeVal) body.set("excursion_type", typeVal);

      const langVal = languageSelect ? languageSelect.value || "" : "";
      if (langVal) body.set("excursion_language", langVal);

      const res = await fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        credentials: "same-origin",
      });

      const json = await res.json();
      if (!json || !json.success) throw new Error("AJAX error");

      replaceWithServerHtml(list, json.data.html || "");
      if (count) count.textContent = `Найдено экскурсий: ${json.data.total || 0}`;

      if (pagination) {
        if (json.data.pagination) {
          replaceWithServerHtml(pagination, json.data.pagination);
          pagination.style.display = "";
          initPaginationHandlers();
        } else {
          replaceWithServerHtml(pagination, "");
          pagination.style.display = "none";
        }
      }

      document.dispatchEvent(new CustomEvent("education:content-updated"));
    } catch (e) {
      // Silent
    } finally {
      setLoading(false);
    }
  };

  const initPaginationHandlers = () => {
    if (!pagination) return;

    const paginationLinks = pagination.querySelectorAll("a");
    paginationLinks.forEach((link) => {
      const newLink = link.cloneNode(true);
      link.parentNode.replaceChild(newLink, link);

      newLink.addEventListener("click", (e) => {
        e.preventDefault();
        const href = newLink.getAttribute("href");
        if (!href) return;

        let page = 1;
        const pageMatch = href.match(/[?&]paged=(\d+)/);
        if (pageMatch) {
          page = parseInt(pageMatch[1], 10);
        } else {
          const pageMatch2 = href.match(/\/page\/(\d+)\//);
          if (pageMatch2) {
            page = parseInt(pageMatch2[1], 10);
          }
        }

        if (page > 0) {
          loadExcursions(page);
          if (list) list.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    });
  };

  const regionChoice = regionSelect
    ? new Choices(regionSelect, { ...CHOICES_RU, searchEnabled: true, shouldSort: false })
    : null;

  const resortChoice = resortSelect
    ? new Choices(resortSelect, { ...CHOICES_RU, searchEnabled: true, shouldSort: false })
    : null;

  const typeChoice = typeSelect
    ? new Choices(typeSelect, { ...CHOICES_RU, searchEnabled: true, shouldSort: false })
    : null;

  const languageChoice = languageSelect
    ? new Choices(languageSelect, { ...CHOICES_RU, searchEnabled: true, shouldSort: false })
    : null;

  const loadResorts = async () => {
    if (!resortChoice) return;

    const regionId = regionSelect ? regionSelect.value || "" : "";

    try {
      const body = new URLSearchParams();
      body.set("action", "excursions_resorts");
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
      // Silent
    }
  };

  if (sortContainer) {
    const sortDropdown = dropdown(sortContainer);
    sortContainer.querySelectorAll(".country-excursions__sort-option").forEach((opt) => {
      opt.addEventListener("click", (e) => {
        e.preventDefault();
        currentSortValue = opt.dataset.value;
        if (sortTextEl) sortTextEl.textContent = opt.textContent.trim();
        sortContainer
          .querySelectorAll(".country-excursions__sort-option")
          .forEach((o) => o.classList.remove("is-active"));
        opt.classList.add("is-active");
        sortDropdown.close();
        loadExcursions(1);
      });
    });
  }

  const resetBtn = document.querySelector("[data-excursions-reset]");

  const updateResetVisibility = () => {
    if (!resetBtn) return;
    const hasAnyFilter =
      (regionSelect && regionSelect.value) ||
      (resortSelect && resortSelect.value) ||
      (typeSelect && typeSelect.value) ||
      (languageSelect && languageSelect.value);
    resetBtn.classList.toggle("is-hidden", !hasAnyFilter);
  };

  const resetFilters = async () => {
    const setEmpty = (choice, select) => {
      if (choice) choice.setChoiceByValue("");
      else if (select) select.value = "";
    };
    setEmpty(regionChoice, regionSelect);
    setEmpty(resortChoice, resortSelect);
    setEmpty(typeChoice, typeSelect);
    setEmpty(languageChoice, languageSelect);

    if (resortChoice) {
      await loadResorts();
    }
    updateResetVisibility();
    await loadExcursions(1);
  };

  if (regionSelect) {
    regionSelect.addEventListener("change", async () => {
      await loadResorts();
      updateResetVisibility();
      await loadExcursions(1);
    });
  }
  if (resortSelect) resortSelect.addEventListener("change", () => { updateResetVisibility(); loadExcursions(1); });
  if (typeSelect) typeSelect.addEventListener("change", () => { updateResetVisibility(); loadExcursions(1); });
  if (languageSelect) languageSelect.addEventListener("change", () => { updateResetVisibility(); loadExcursions(1); });

  if (resetBtn) {
    resetBtn.addEventListener("click", (e) => {
      e.preventDefault();
      resetFilters();
    });
  }

  if (pagination) {
    initPaginationHandlers();
  }

  updateResetVisibility();
};
