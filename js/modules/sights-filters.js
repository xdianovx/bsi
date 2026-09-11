import Choices from "choices.js";

/**
 * Фильтры каталога достопримечательностей (country-sights.php).
 *
 * Селекты — Choices.js, состояние живёт в URL (GET-параметры region/resort/sight_type).
 * При выборе форма не сабмитится напрямую: URL собирается вручную, чтобы пустые
 * параметры не оставались в адресе (?region=&resort=). Кнопка «Показать» нужна
 * только без JS — после инициализации она скрывается.
 */

const CHOICES_RU = {
  itemSelectText: "",
  loadingText: "Загрузка...",
  noResultsText: "Ничего не найдено",
  noChoicesText: "Нет вариантов",
  searchPlaceholderValue: "Поиск...",
};

/* Поиск внутри выпадашки включаем только там, где вариантов реально много */
const SEARCH_THRESHOLD = 8;

export const initSightsFilters = () => {
  const form = document.querySelector("[data-sights-filters]");
  if (!form) return;

  const selects = Array.from(form.querySelectorAll("select"));
  if (!selects.length) return;

  const baseUrl = form.getAttribute("action") || window.location.pathname;
  const chipsBox = form.querySelector("[data-sights-chips]");

  form.classList.add("is-js");

  const buildUrl = (overrides = {}) => {
    const params = new URLSearchParams();

    selects.forEach((select) => {
      const name = select.name;
      const value = Object.prototype.hasOwnProperty.call(overrides, name) ? overrides[name] : select.value;

      if (value) params.set(name, value);
    });

    const qs = params.toString();
    return qs ? `${baseUrl}?${qs}` : baseUrl;
  };

  const go = (overrides) => {
    form.classList.add("is-loading");
    window.location.href = buildUrl(overrides);
  };

  const renderChips = () => {
    if (!chipsBox) return;

    const active = selects.filter((select) => select.value);
    chipsBox.innerHTML = "";
    chipsBox.hidden = active.length === 0;

    active.forEach((select) => {
      const option = select.options[select.selectedIndex];
      const chip = document.createElement("button");
      chip.type = "button";
      chip.className = "country-sights-chip";
      chip.textContent = (option ? option.text : "").trim();
      chip.addEventListener("click", () => go({ [select.name]: "" }));
      chipsBox.appendChild(chip);
    });
  };

  selects.forEach((select) => {
    new Choices(select, {
      ...CHOICES_RU,
      searchEnabled: select.options.length > SEARCH_THRESHOLD,
      shouldSort: false,
    });

    select.addEventListener("change", () => go());
  });

  renderChips();
};
