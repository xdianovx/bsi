import Choices from "choices.js";

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
  if (!list) return;

  const countryId = parseInt(root.getAttribute("data-country-id") || "0", 10);
  if (!countryId) return;

  const ajaxUrl = window.ajax?.url || window.ajaxurl;
  if (!ajaxUrl) return;

  const regionSelect = root.querySelector('select[name="region"]');
  const resortSelect = root.querySelector('select[name="resort[]"]');
  const typeSelect = root.querySelector('select[name="tour_type[]"]');

  // region (single)
  if (regionSelect) {
    new Choices(regionSelect, {
      ...CHOICES_RU,
      searchEnabled: true,
      shouldSort: false,
    });
  }

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
  if (typeSelect) {
    new Choices(typeSelect, {
      ...CHOICES_RU,
      removeItemButton: true,
      searchEnabled: true,
      shouldSort: false,
      placeholder: true,
    });
  }

  const setLoading = (on) => list.classList.toggle("is-loading", !!on);

  const getValues = (sel) => {
    if (!sel) return [];
    return Array.from(sel.selectedOptions)
      .map((o) => o.value)
      .filter(Boolean);
  };

  const loadTours = async () => {
    setLoading(true);

    try {
      const body = new URLSearchParams();
      body.set("action", "country_tours_filter");
      body.set("country_id", String(countryId));

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
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

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
      console.error(e);
    }
  };

  if (regionSelect) {
    regionSelect.addEventListener("change", async () => {
      await loadResorts();
      await loadTours();
    });
  }
  if (resortSelect) resortSelect.addEventListener("change", loadTours);
  if (typeSelect) typeSelect.addEventListener("change", loadTours);
};
