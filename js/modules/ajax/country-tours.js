import Choices from "choices.js";

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

  if (regionSelect) new Choices(regionSelect, { searchEnabled: true, shouldSort: false, itemSelectText: "" });
  const resortChoice = resortSelect
    ? new Choices(resortSelect, { removeItemButton: true, searchEnabled: true, shouldSort: false, itemSelectText: "" })
    : null;
  if (typeSelect) new Choices(typeSelect, { removeItemButton: true, searchEnabled: true, shouldSort: false, itemSelectText: "" });

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
        (json.data.items || []).map((it) => ({ value: String(it.id), label: it.text })),
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
